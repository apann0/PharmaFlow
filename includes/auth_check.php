<?php
/**
 * Authentication Guard
 * 
 * Include this file at the top of any page that requires login.
 * Redirects unauthenticated users to the login page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any partial session data
    session_unset();
    session_destroy();

    // Redirect to login
    header('Location: /modules/auth/login.php');
    exit;
}

// Session timeout: 30 minutes of inactivity
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header('Location: /modules/auth/login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 600) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
