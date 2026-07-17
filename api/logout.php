<?php
/**
 * Logout Handler - ensures session and cookie are properly removed
 */

require_once __DIR__ . '/../config/config.php';

// If user not authenticated, redirect to login
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/auth/login.html');
    exit;
}

$user_id = $_SESSION['user_id'];

// Remove session from database
$session_id = session_id();
$stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? AND user_id = ?");
$stmt->bind_param("si", $session_id, $user_id);
$stmt->execute();
$stmt->close();

// Log activity
logActivity($user_id, 'logout', 'User logged out');

// Properly destroy session and cookie
destroySession();

// Redirect to login
header('Location: ' . APP_URL . '/auth/login.html');
exit;
?>