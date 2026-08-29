<?php
/**
 * User Logout
 * Food-Mania - Destroys user session and redirects to home
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Destroy user session data
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

// Destroy entire session if no admin session exists
if (!isset($_SESSION['admin_id'])) {
    session_destroy();
}

// Redirect to home page
header("Location: " . SITE_URL . "/");
exit();
