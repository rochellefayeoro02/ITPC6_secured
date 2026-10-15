<?php
declare(strict_types=1);

// Hide PHP errors from visitors (they still go to the server log).
ini_set("display_errors", "0");

const SESSION_IDLE_SECONDS = 1800; // log out after 30 min of inactivity

if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");
    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "secure"   => !empty($_SERVER["HTTPS"]),
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: same-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'");

/** Escape for HTML output (prevents XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION["csrf_token"])
        && hash_equals($_SESSION["csrf_token"], $token);
}

function is_logged_in(): bool
{
    return !empty($_SESSION["user_id"]);
}

function destroy_session(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", [
            "expires"  => time() - 42000,
            "path"     => $p["path"],
            "domain"   => $p["domain"],
            "secure"   => $p["secure"],
            "httponly" => $p["httponly"],
            "samesite" => "Lax",
        ]);
    }
    session_destroy();
}

/** Call at the top of any page that needs a logged-in user. */
function require_login(): void
{
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
    if (time() - ($_SESSION["last_activity"] ?? 0) > SESSION_IDLE_SECONDS) {
        destroy_session();
        header("Location: login.php?timeout=1");
        exit;
    }
    $_SESSION["last_activity"] = time();
}
