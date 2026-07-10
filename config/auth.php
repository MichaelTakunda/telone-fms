<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($allowedRoles) {
    requireLogin();

    if (!in_array($_SESSION["role"], $allowedRoles)) {
        echo "Access denied. You do not have permission to view this page.";
        exit;
    }
}
?>