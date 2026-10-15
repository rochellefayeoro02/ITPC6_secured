<?php
declare(strict_types=1);

// Prefer environment variables; the fallbacks are for local development only.
$host   = getenv("DB_HOST") ?: "localhost";
$user   = getenv("DB_USER") ?: "root";      // use a limited user in production (see schema.sql)
$pass   = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "sec";

// Throw exceptions instead of printing raw MySQL errors to visitors.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Something went wrong. Please try again later.");
}
