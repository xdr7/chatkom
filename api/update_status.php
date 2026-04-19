<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$status = $data['status'] ?? 'online';

$validStatuses = ['online', 'offline', 'away'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

$userId = $_SESSION['user_id'];
updateUserStatus($userId, $status);

// Broadcast status change (untuk realtime bisa pakai WebSocket)
echo json_encode(['success' => true, 'status' => $status]);
?>