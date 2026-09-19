<?php
/**
 * Admin Authentication Guard
 * Ensures only logged-in administrators can access admin pages
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
