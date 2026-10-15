<?php
declare(strict_types=1);
require __DIR__ . "/bootstrap.php";

// POST + CSRF token, so another site can't log you out by embedding a link or image.
if ($_SERVER["REQUEST_METHOD"] === "POST" && csrf_check($_POST["csrf_token"] ?? null)) {
    destroy_session();
    header("Location: login.php?loggedout=1");
    exit;
}

header("Location: " . (is_logged_in() ? "welcome.php" : "login.php"));
exit;
