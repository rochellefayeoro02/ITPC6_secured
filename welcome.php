<?php
declare(strict_types=1);
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

require_login();

// Pages for logged-in users shouldn't be cached (stops the Back button showing them after logout).
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

$stmt = $conn->prepare("SELECT firstName, middleName, lastName, email, mobile, birthDate FROM form WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) { // account was deleted while logged in
    destroy_session();
    header("Location: login.php");
    exit;
}

$fullName = trim($user["firstName"] . " " . $user["middleName"] . " " . $user["lastName"]);
$fullName = preg_replace('/\s+/', " ", $fullName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="frame">
    <h2>Welcome, <?= e($user["firstName"]) ?>!</h2>

    <dl class="profile">
        <div><dt>Name</dt><dd><?= e($fullName) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($user["email"]) ?></dd></div>
        <div><dt>Mobile</dt><dd><?= e($user["mobile"]) ?></dd></div>
        <div><dt>Birth date</dt><dd><?= e(date("F j, Y", strtotime($user["birthDate"]))) ?></dd></div>
    </dl>

    <form method="POST" action="logout.php">
        <?= csrf_field() ?>
        <button type="submit" class="btn">Log out</button>
    </form>
</div>
</body>
</html>
