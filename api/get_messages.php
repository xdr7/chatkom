<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$otherUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50;
$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0;

if (!$otherUserId) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$messages = getMessages($userId, $otherUserId, $limit, $offset);

// Mark messages as read
$stmt = $pdo->prepare("
    UPDATE messages 
    SET is_read = TRUE 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
");
$stmt->execute([$otherUserId, $userId]);

echo json_encode($messages);
?>