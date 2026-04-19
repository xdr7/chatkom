<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teman - ChatKom</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/friends.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="friends-container">
        <!-- Header -->
        <div class="friends-header">
            <button class="back-btn" onclick="location.href='chat.php'">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
            <h1><i class="fas fa-user-friends"></i> Teman</h1>
            <button class="notification-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </button>
        </div>
        
        <!-- Search Bar -->
        <div class="search-section">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari pengguna..." onkeyup="searchUsers()">
            </div>
            <div class="search-results" id="searchResults" style="display: none;"></div>
        </div>
        
        <!-- Tabs -->
        <div class="friends-tabs">
            <button class="tab-btn active" onclick="switchTab('friends')">
                <i class="fas fa-users"></i> Teman Saya
            </button>
            <button class="tab-btn" onclick="switchTab('requests')">
                <i class="fas fa-user-plus"></i> Permintaan
                <span class="tab-badge" id="requestsBadge">0</span>
            </button>
        </div>
        
        <!-- Friends List -->
        <div class="tab-content" id="friendsTab">
            <div class="friends-list" id="friendsList">
                <div class="loading">Memuat daftar teman...</div>
            </div>
        </div>
        
        <!-- Friend Requests -->
        <div class="tab-content" id="requestsTab" style="display: none;">
            <div class="requests-list" id="requestsList">
                <div class="loading">Memuat permintaan...</div>
            </div>
        </div>
        
        <!-- Notifications Panel -->
        <div class="notifications-panel" id="notificationsPanel" style="display: none;">
            <div class="panel-header">
                <h3>Notifikasi</h3>
                <button onclick="markAllNotificationsRead()">Tandai Semua Dibaca</button>
                <button class="close-panel" onclick="toggleNotifications()">&times;</button>
            </div>
            <div class="notifications-list" id="notificationsList"></div>
        </div>
    </div>
    
    <!-- Incoming Call Modal -->
    <div class="call-modal" id="callModal" style="display: none;">
        <div class="call-content">
            <img src="" alt="Caller" id="callerAvatar" class="caller-avatar">
            <h3 id="callerName"></h3>
            <p id="callType"></p>
            <div class="call-actions">
                <button class="btn-decline" onclick="rejectCall()">
                    <i class="fas fa-phone-slash"></i> Tolak
                </button>
                <button class="btn-accept" onclick="acceptCall()">
                    <i class="fas fa-phone"></i> Jawab
                </button>
            </div>
        </div>
    </div>
    
    <!-- Active Call Modal -->
    <div class="active-call-modal" id="activeCallModal" style="display: none;">
        <div class="active-call-content">
            <div class="call-header">
                <span id="callDuration">00:00</span>
            </div>
            <img src="" alt="Contact" id="activeCallAvatar" class="active-call-avatar">
            <h3 id="activeCallName"></h3>
            <p id="callStatus">Connecting...</p>
            <div class="call-controls">
                <button class="control-btn" onclick="toggleMute()" id="muteBtn">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="control-btn" onclick="toggleSpeaker()" id="speakerBtn">
                    <i class="fas fa-volume-up"></i>
                </button>
                <?php if (isset($isVideoCall)): ?>
                <button class="control-btn" onclick="toggleVideo()" id="videoBtn">
                    <i class="fas fa-video"></i>
                </button>
                <?php endif; ?>
                <button class="control-btn end-call" onclick="endCall()">
                    <i class="fas fa-phone-slash"></i>
                </button>
            </div>
            <video id="localVideo" autoplay muted></video>
            <video id="remoteVideo" autoplay></video>
        </div>
    </div>
    
    <script src="assets/js/webrtc.js"></script>
    <script src="assets/js/friends.js"></script>
</body>
</html>