<?php
/**
 * Admin Logout
 * Food-Mania - Destroys admin session and redirects to admin login
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

// Destroy the entire session to ensure complete logout
session_destroy();

header("Location: " . SITE_URL . "/admin/login.php");
exit();
