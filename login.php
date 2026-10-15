<?php
declare(strict_types=1);
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

if (is_logged_in()) { header("Location: welcome.php"); exit; }

const MAX_ATTEMPTS = 5;   // wrong passwords before the account is locked
const LOCK_MINUTES = 15;

$error  = "";
$notice = "";
if (isset($_GET["registered"]))    $notice = "Registration successful! Please log in.";
elseif (isset($_GET["loggedout"])) $notice = "You have been logged out.";
elseif (isset($_GET["timeout"]))   $notice = "Your session expired. Please log in again.";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if (!csrf_check($_POST["csrf_token"] ?? null)) {
        $error = "Your session expired. Please try again.";
    } elseif ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {
        try {
            $stmt = $conn->prepare(
                "SELECT id, password, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked
                 FROM form WHERE email = ? LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && $user["is_locked"]) {
                $error = "Too many failed attempts. Try again in " . LOCK_MINUTES . " minutes.";
            } else {
                // Always run password_verify so response time doesn't reveal whether the email exists.
                $hash  = $user["password"] ?? password_hash("dummy-password", PASSWORD_DEFAULT);
                $valid = password_verify($password, $hash);

                if ($user && $valid) {
                    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                        $new = password_hash($password, PASSWORD_DEFAULT);
                        $up = $conn->prepare("UPDATE form SET password = ? WHERE id = ?");
                        $up->bind_param("si", $new, $user["id"]);
                        $up->execute();
                        $up->close();
                    }
                    $reset = $conn->prepare("UPDATE form SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
                    $reset->bind_param("i", $user["id"]);
                    $reset->execute();
                    $reset->close();

                    session_regenerate_id(true);
                    $_SESSION["user_id"]       = (int)$user["id"];
                    $_SESSION["last_activity"] = time();
                    unset($_SESSION["csrf_token"]);

                    header("Location: welcome.php");
                    exit;
                }

                if ($user) {
                    $max = MAX_ATTEMPTS; $mins = LOCK_MINUTES;
                    $fail = $conn->prepare(
                        "UPDATE form SET
                            locked_until    = IF(failed_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until),
                            failed_attempts = IF(failed_attempts + 1 >= ?, 0, failed_attempts + 1)
                         WHERE id = ?"
                    );
                    $fail->bind_param("iiii", $max, $mins, $max, $user["id"]);
                    $fail->execute();
                    $fail->close();
                }
                // Same message for "no such email" and "wrong password".
                $error = "Invalid email or password.";
            }
        } catch (mysqli_sql_exception $ex) {
            error_log("Login failed: " . $ex->getMessage());
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="frame">
    <h2>Login</h2>

    <?php if ($notice): ?><div class="msg ok" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error):  ?><div class="msg err" role="alert"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" action="login.php">
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($_POST["email"] ?? "") ?>" required autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <input type="submit" value="Log in">
    </form>

    <div class="link">No account? <a href="index.php">Register</a></div>
</div>
</body>
</html>
