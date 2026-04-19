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
    case 'send':
        // Kirim permintaan pertemanan
        $receiverId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        if (!$receiverId || $receiverId == $userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid user']);
            exit;
        }
        
        // Cek apakah sudah teman
        $stmt = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$userId, $receiverId, $receiverId, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Already friends']);
            exit;
        }
        
        // Cek apakah sudah ada permintaan pending
        $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) AND status = 'pending'");
        $stmt->execute([$userId, $receiverId, $receiverId, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Friend request already exists']);
            exit;
        }
        
        // Kirim permintaan
        $stmt = $pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
        if ($stmt->execute([$userId, $receiverId])) {
            // Buat notifikasi
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, content, reference_id) VALUES (?, 'friend_request', 'Permintaan Pertemanan', ?, ?)");
            $senderName = getUserById($userId)['full_name'] ?? getUserById($userId)['username'];
            $stmt->execute([$receiverId, "$senderName ingin berteman dengan Anda", $userId]);
            
            logActivity($userId, 'send_friend_request', "Sent friend request to user $receiverId");
            echo json_encode(['success' => true, 'message' => 'Friend request sent']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send request']);
        }
        break;
        
    case 'accept':
        // Terima permintaan pertemanan
        $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$requestId, $userId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            echo json_encode(['success' => false, 'error' => 'Request not found']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update request status
            $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$requestId]);
            
            // Add to friends table (both directions)
            $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)");
            $stmt->execute([$request['sender_id'], $request['receiver_id'], $request['receiver_id'], $request['sender_id']]);
            
            // Buat notifikasi
            $receiverName = getUserById($userId)['full_name'] ?? getUserById($userId)['username'];
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, content) VALUES (?, 'friend_request', 'Permintaan Diterima', ?)");
            $stmt->execute([$request['sender_id'], "$receiverName menerima permintaan pertemanan Anda"]);
            
            $pdo->commit();
            
            logActivity($userId, 'accept_friend_request', "Accepted friend request from user {$request['sender_id']}");
            echo json_encode(['success' => true, 'message' => 'Friend added']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'reject':
        // Tolak permintaan pertemanan
        $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'rejected' WHERE id = ? AND receiver_id = ?");
        if ($stmt->execute([$requestId, $userId])) {
            echo json_encode(['success' => true, 'message' => 'Request rejected']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to reject request']);
        }
        break;
        
    case 'remove':
        // Hapus teman
        $friendId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        if ($stmt->execute([$userId, $friendId, $friendId, $userId])) {
            logActivity($userId, 'remove_friend', "Removed friend $friendId");
            echo json_encode(['success' => true, 'message' => 'Friend removed']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove friend']);
        }
        break;
        
    case 'list':
        // Dapatkan daftar teman
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.full_name, u.avatar, u.status, u.last_seen
            FROM friends f
            JOIN users u ON u.id = f.friend_id
            WHERE f.user_id = ?
            ORDER BY u.status DESC, u.full_name ASC
        ");
        $stmt->execute([$userId]);
        $friends = $stmt->fetchAll();
        echo json_encode(['success' => true, 'friends' => $friends]);
        break;
        
    case 'pending':
        // Dapatkan permintaan pertemanan pending
        $stmt = $pdo->prepare("
            SELECT fr.id, fr.created_at, u.id as user_id, u.username, u.full_name, u.avatar
            FROM friend_requests fr
            JOIN users u ON u.id = fr.sender_id
            WHERE fr.receiver_id = ? AND fr.status = 'pending'
            ORDER BY fr.created_at DESC
        ");
        $stmt->execute([$userId]);
        $requests = $stmt->fetchAll();
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;
        
    case 'search':
        // Cari pengguna
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            echo json_encode(['success' => false, 'error' => 'Query too short']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, avatar, status
            FROM users
            WHERE (username LIKE ? OR full_name LIKE ?) AND id != ?
            LIMIT 20
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $userId]);
        $users = $stmt->fetchAll();
        
        // Cek status pertemanan
        foreach ($users as &$user) {
            $stmt = $pdo->prepare("
                SELECT status FROM friend_requests 
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ");
            $stmt->execute([$userId, $user['id'], $user['id'], $userId]);
            $request = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT id FROM friends WHERE user_id = ? AND friend_id = ?");
            $stmt->execute([$userId, $user['id']]);
            
            $user['is_friend'] = $stmt->fetch() ? true : false;
            $user['request_status'] = $request ? $request['status'] : null;
        }
        
        echo json_encode(['success' => true, 'users' => $users]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>