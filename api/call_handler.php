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
    case 'initiate':
        // Mulai panggilan
        $receiverId = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
        $callType = $_POST['call_type'] ?? 'voice';
        
        if (!$receiverId || $receiverId == $userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid receiver']);
            exit;
        }
        
        // Cek apakah receiver online
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$receiverId]);
        $receiver = $stmt->fetch();
        
        if ($receiver['status'] !== 'online') {
            echo json_encode(['success' => false, 'error' => 'User is offline']);
            exit;
        }
        
        // Buat record panggilan
        $stmt = $pdo->prepare("INSERT INTO calls (caller_id, receiver_id, call_type, status, started_at) VALUES (?, ?, ?, 'missed', NOW())");
        $stmt->execute([$userId, $receiverId, $callType]);
        $callId = $pdo->lastInsertId();
        
        // Buat notifikasi
        $callerName = getUserById($userId)['full_name'] ?? getUserById($userId)['username'];
        $callTypeText = $callType === 'video' ? 'Video Call' : 'Voice Call';
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, content, reference_id) VALUES (?, 'call', ?, ?, ?)");
        $stmt->execute([$receiverId, "Incoming $callTypeText", "$callerName memanggil Anda", $callId]);
        
        logActivity($userId, 'initiate_call', "Initiated $callType call to user $receiverId");
        
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'message' => 'Call initiated'
        ]);
        break;
        
    case 'answer':
        // Jawab panggilan
        $callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("UPDATE calls SET status = 'answered', started_at = NOW() WHERE id = ? AND receiver_id = ?");
        if ($stmt->execute([$callId, $userId])) {
            echo json_encode(['success' => true, 'message' => 'Call answered']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to answer call']);
        }
        break;
        
    case 'reject':
        // Tolak panggilan
        $callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("UPDATE calls SET status = 'rejected' WHERE id = ? AND receiver_id = ?");
        if ($stmt->execute([$callId, $userId])) {
            echo json_encode(['success' => true, 'message' => 'Call rejected']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to reject call']);
        }
        break;
        
    case 'end':
        // Akhiri panggilan
        $callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT);
        $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT) ?? 0;
        
        $stmt = $pdo->prepare("UPDATE calls SET status = 'answered', ended_at = NOW(), duration = ? WHERE id = ? AND (caller_id = ? OR receiver_id = ?)");
        if ($stmt->execute([$duration, $callId, $userId, $userId])) {
            echo json_encode(['success' => true, 'message' => 'Call ended']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to end call']);
        }
        break;
        
    case 'missed':
        // Panggilan tak terjawab (dipanggil oleh client saat timeout)
        $callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("UPDATE calls SET status = 'missed' WHERE id = ?");
        $stmt->execute([$callId]);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>