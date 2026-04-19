<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    updateUserStatus($userId, 'offline');
    logActivity($userId, 'logout', 'User logged out');
}

session_destroy();
header('Location: login.php');
exit;
?>