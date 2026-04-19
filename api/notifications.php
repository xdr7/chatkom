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
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Dapatkan notifikasi
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?? 20;
        $unreadOnly = filter_input(INPUT_GET, 'unread', FILTER_VALIDATE_BOOLEAN) ?? false;
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $limit]);
        $notifications = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'notifications' => $notifications]);
        break;
        
    case 'count':
        // Hitung notifikasi belum dibaca
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        echo json_encode(['success' => true, 'count' => $result['total']]);
        break;
        
    case 'mark_read':
        // Tandai sudah dibaca
        $notificationId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($notificationId) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
        } else {
            // Mark all as read
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>