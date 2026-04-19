<?php
// includes/functions.php - Utility functions ONLY
// DO NOT put authentication functions here!

function getUserById($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function updateUserStatus($userId, $status) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET status = ?, last_seen = NOW() WHERE id = ?");
    return $stmt->execute([$status, $userId]);
}

function getConversations($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.id as user_id,
            u.username,
            u.full_name,
            u.avatar,
            u.status,
            u.last_seen,
            m.message as last_message,
            m.created_at as last_message_time,
            m.message_type as last_message_type,
            (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count
        FROM conversations c
        JOIN users u ON (u.id = CASE 
            WHEN c.user1_id = ? THEN c.user2_id 
            ELSE c.user1_id 
        END)
        LEFT JOIN messages m ON m.id = c.last_message_id
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    return $stmt->fetchAll();
}

function getMessages($userId, $otherUserId, $limit = 50, $offset = 0) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.*, 
               s.username as sender_name,
               s.avatar as sender_avatar
        FROM messages m
        JOIN users s ON s.id = m.sender_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $limit, $offset]);
    return array_reverse($stmt->fetchAll());
}

function sendMessage($senderId, $receiverId, $message, $type = 'text', $filePath = null, $fileName = null, $fileSize = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, message_type, file_path, file_name, file_size)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$senderId, $receiverId, $message, $type, $filePath, $fileName, $fileSize]);
        $messageId = $pdo->lastInsertId();
        
        // Update or create conversation
        $user1 = min($senderId, $receiverId);
        $user2 = max($senderId, $receiverId);
        
        $stmt = $pdo->prepare("
            INSERT INTO conversations (user1_id, user2_id, last_message_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                last_message_id = ?,
                updated_at = NOW()
        ");
        $stmt->execute([$user1, $user2, $messageId, $messageId]);
        
        $pdo->commit();
        return $messageId;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function uploadFile($file) {
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
    $maxSize = MAX_FILE_SIZE;
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    if ($fileSize > $maxSize) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadPath = UPLOAD_DIR . $newFileName;
    
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        return [
            'success' => true,
            'path' => 'uploads/' . $newFileName,
            'name' => $fileName,
            'size' => $fileSize
        ];
    }
    
    return ['success' => false, 'error' => 'Upload failed'];
}

function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $setting = $stmt->fetch();
    
    if (!$setting) return null;
    
    switch ($setting['setting_type']) {
        case 'json':
            return json_decode($setting['setting_value'], true);
        case 'boolean':
            return $setting['setting_value'] === 'true';
        case 'integer':
            return (int)$setting['setting_value'];
        default:
            return $setting['setting_value'];
    }
}

function logActivity($userId, $action, $details = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function timeAgo($timestamp) {
    if (!$timestamp) return 'Never';
    
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('d M Y', $time);
    }
}

function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
?>