<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$receiverId = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
$message = trim($_POST['message'] ?? '');
$hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

if (!$receiverId) {
    echo json_encode(['success' => false, 'error' => 'Invalid receiver']);
    exit;
}

$senderId = $_SESSION['user_id'];
$messageType = 'text';
$filePath = null;
$fileName = null;
$fileSize = null;

if ($hasFile) {
    $uploadResult = uploadFile($_FILES['file']);
    
    if (!$uploadResult['success']) {
        echo json_encode(['success' => false, 'error' => $uploadResult['error']]);
        exit;
    }
    
    $filePath = $uploadResult['path'];
    $fileName = $uploadResult['name'];
    $fileSize = $uploadResult['size'];
    
    // Determine message type
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $messageType = 'image';
    } else {
        $messageType = 'file';
    }
}

$messageId = sendMessage($senderId, $receiverId, $message, $messageType, $filePath, $fileName, $fileSize);

if ($messageId) {
    logActivity($senderId, 'send_message', "Sent message to user {$receiverId}");
    echo json_encode(['success' => true, 'message_id' => $messageId]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
?>