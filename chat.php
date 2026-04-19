<?php

require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$openUserId = isset($_GET['user']) ? (int)$_GET['user'] : null;
$userId = $_SESSION['user_id'];
$currentUser = getUserById($userId);
$conversations = getConversations($userId);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ChatKom - Created by Sasskom.app</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="user-profile" onclick="location.href='profile.php'">
                    <img src="uploads/<?php echo htmlspecialchars($currentUser['avatar'] ?? 'default-avatar.png'); ?>" alt="Avatar" class="avatar">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']); ?></h3>
                        <span class="user-status"><?php echo $currentUser['status'] ?? 'offline'; ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="icon-btn" onclick="location.href='friends.php'" title="Teman">
                        <i class="fas fa-user-friends"></i>
                    </button>
                    <button class="icon-btn" onclick="location.href='profile.php'" title="Pengaturan">
                        <i class="fas fa-cog"></i>
                    </button>
                    <?php if (isAdmin()): ?>
                    <button class="icon-btn" onclick="location.href='admin/dashboard.php'" title="Admin Panel">
                        <i class="fas fa-shield-alt"></i>
                    </button>
                    <?php endif; ?>
                    <button class="icon-btn" onclick="confirmLogout()" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
            
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari percakapan..." id="searchConversation">
            </div>
            
            <div class="conversations-list" id="conversationsList">
                <?php if (empty($conversations)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #65676b;">
                    <i class="fas fa-comments" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>Belum ada percakapan</p>
                    <p style="font-size: 13px; margin-top: 8px;">Cari teman untuk mulai chatting</p>
                </div>
                <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <div class="conversation-item <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>" 
                     onclick="openConversation(<?php echo $conv['user_id']; ?>)"
                     data-user-id="<?php echo $conv['user_id']; ?>">
                    <img src="uploads/<?php echo htmlspecialchars($conv['avatar'] ?? 'default-avatar.png'); ?>" alt="Avatar" class="avatar">
                    <div class="conversation-info">
                        <div class="conversation-header">
                            <h4><?php echo htmlspecialchars($conv['full_name'] ?: $conv['username']); ?></h4>
                            <span class="time"><?php echo timeAgo($conv['last_message_time']); ?></span>
                        </div>
                        <div class="conversation-preview">
                            <p><?php echo htmlspecialchars($conv['last_message'] ?? 'Mulai percakapan'); ?></p>
                            <?php if ($conv['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="status-indicator <?php echo $conv['status'] ?? 'offline'; ?>"></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <div class="chat-main">
            <div class="chat-header" id="chatHeader">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="chat-user-info" id="chatUserInfo">
                    <img src="assets/img/default-avatar.png" alt="Avatar" class="avatar" id="chatAvatar">
                    <div class="user-details">
                        <h3 id="chatUserName">Pilih Percakapan</h3>
                        <span class="user-status-text" id="chatUserStatus"></span>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="icon-btn" onclick="voiceCall()">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="icon-btn" onclick="videoCall()">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="icon-btn" onclick="showUserInfo()">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </div>
            
            <div class="messages-container" id="messagesContainer">
                <div class="welcome-message">
                    <i class="fas fa-comments"></i>
                    <h2>Selamat Datang di ChatKom</h2>
                    <p>Pilih percakapan untuk mulai chatting</p>
                    <p class="credit">Created by Sasskom.app</p>
                </div>
                <div id="messagesList"></div>
            </div>
            
            <div class="message-input-container" id="messageInputContainer" style="display: none;">
                <div class="file-preview" id="filePreview" style="display: none;">
                    <span id="fileName"></span>
                    <button onclick="clearFile()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="message-input-wrapper">
                    <button class="icon-btn" onclick="toggleEmojiPicker()">
                        <i class="far fa-smile"></i>
                    </button>
                    <button class="icon-btn" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="file" id="fileInput" style="display: none;" onchange="handleFileSelect(this)">
                    <textarea id="messageInput" placeholder="Ketik pesan..." rows="1"></textarea>
                    <button class="icon-btn" onclick="sendMessage()" id="sendButton">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                
                <div class="emoji-picker" id="emojiPicker" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <script>
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;
        const siteUrl = '<?php echo SITE_URL; ?>';
    </script>
    <script src="assets/js/emoji-picker.js"></script>
    <script src="assets/js/chat.js"></script>
    
    <script>
        // Simple additional functions
        function confirmLogout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                updateUserStatus('offline');
                window.location.href = siteUrl + '/logout.php';
            }
        }
        
        function voiceCall() {
            if (!currentConversation) {
                alert('Pilih kontak terlebih dahulu');
                return;
            }
            alert('Fitur voice call segera hadir!');
        }
        
        function videoCall() {
            if (!currentConversation) {
                alert('Pilih kontak terlebih dahulu');
                return;
            }
            alert('Fitur video call segera hadir!');
        }
        
        function showUserInfo() {
            if (!currentConversation) {
                alert('Pilih kontak terlebih dahulu');
                return;
            }
            alert('Info kontak: ' + document.getElementById('chatUserName').textContent);
        }
    </script>
</body>
</html>