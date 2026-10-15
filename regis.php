<?php
declare(strict_types=1);
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["register"])) {
    header("Location: index.php");
    exit;
}

/** Returns a list of problems with the password (empty list = OK). */
function password_problems(string $password, string $email, string $firstName, string $lastName): array
{
    $problems = [];

    if (strlen($password) < 8) {
        $problems[] = "Password must be at least 8 characters long.";
    }
    if (strlen($password) > 72) { // bcrypt ignores everything after 72 bytes
        $problems[] = "Password must be 72 characters or fewer.";
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        $problems[] = "Password must contain at least one letter.";
    }
    if (!preg_match('/\d/', $password)) {
        $problems[] = "Password must contain at least one number.";
    }

    $lower = strtolower($password);
    $common = ["password", "password1", "password123", "passw0rd", "qwerty123", "qwertyuiop",
               "abc12345", "abcd1234", "abc123456", "12345678", "123456789", "1234567890",
               "iloveyou1", "admin123", "welcome1", "letmein123", "user1234", "test1234"];
    if (in_array($lower, $common, true)) {
        $problems[] = "That password is too common. Choose something harder to guess.";
    }
    if (preg_match('/^(.)\1+$/', $password)) {
        $problems[] = "Password cannot be a single repeated character.";
    }

    $local = strtolower(explode("@", $email)[0] ?? "");
    foreach ([$local, strtolower($firstName), strtolower($lastName)] as $part) {
        if (strlen($part) >= 4 && str_contains($lower, $part)) {
            $problems[] = "Password must not contain your name or email.";
            break;
        }
    }
    return $problems;
}

function fail(array $errors, array $old): never
{
    $_SESSION["reg_errors"] = $errors;
    $_SESSION["reg_old"]    = $old; // passwords are never stored
    header("Location: index.php");
    exit;
}

$firstName  = trim($_POST["firstName"] ?? "");
$middleName = trim($_POST["middleName"] ?? "");
$lastName   = trim($_POST["lastName"] ?? "");
$birthDate  = trim($_POST["birthDate"] ?? "");
$address    = trim($_POST["address"] ?? "");
$email      = strtolower(trim($_POST["email"] ?? ""));
$mobile     = preg_replace('/[\s\-()]/', "", trim($_POST["mobile"] ?? ""));
$password   = $_POST["password"] ?? "";
$confirm    = $_POST["confirm_password"] ?? "";

$old = compact("firstName", "middleName", "lastName", "birthDate", "address", "email", "mobile");

if (!csrf_check($_POST["csrf_token"] ?? null)) {
    fail(["Your session expired. Please try again."], $old);
}

$errors = [];
$namePattern = '/^[\p{L}\p{M}\s\'.\-]{1,50}$/u';

if ($firstName === "" || !preg_match($namePattern, $firstName)) $errors[] = "Enter a valid first name.";
if ($middleName !== "" && !preg_match($namePattern, $middleName)) $errors[] = "Enter a valid middle name or leave it blank.";
if ($lastName === "" || !preg_match($namePattern, $lastName)) $errors[] = "Enter a valid last name.";

$dt = DateTime::createFromFormat("!Y-m-d", $birthDate);
if (!$dt || $dt->format("Y-m-d") !== $birthDate || $dt > new DateTime("today") || $dt < new DateTime("1900-01-01")) {
    $errors[] = "Enter a valid birth date.";
}

if ($address === "" || mb_strlen($address) > 255) $errors[] = "Enter an address (up to 255 characters).";
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $errors[] = "Enter a valid email address.";
if (!preg_match('/^\+?[0-9]{7,15}$/', $mobile)) $errors[] = "Enter a valid mobile number (7-15 digits).";

if ($password !== $confirm) {
    $errors[] = "Passwords do not match.";
}
$errors = array_merge($errors, password_problems($password, $email, $firstName, $lastName));

if ($errors) {
    fail($errors, $old);
}

try {
    $check = $conn->prepare("SELECT id FROM form WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        fail(["That email is already registered. Try logging in instead."], $old);
    }
    $check->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO form (firstName, middleName, lastName, birthDate, address, email, mobile, password)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssssss", $firstName, $middleName, $lastName, $birthDate, $address, $email, $mobile, $hash);
    $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    if ($ex->getCode() === 1062) { // duplicate key (two sign-ups at the same moment)
        fail(["That email is already registered. Try logging in instead."], $old);
    }
    error_log("Registration failed: " . $ex->getMessage());
    fail(["Something went wrong. Please try again."], $old);
}

$conn->close();
header("Location: login.php?registered=1");
exit;
