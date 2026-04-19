// Friends Management
let currentTab = 'friends';
let searchTimeout = null;
let notificationCheckInterval = null;
let incomingCallData = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadFriends();
    loadFriendRequests();
    checkNotifications();
    
    // Start polling for notifications
    notificationCheckInterval = setInterval(checkNotifications, 5000);
    
    // Start status update
    updateUserStatus('online');
    setInterval(() => updateUserStatus('online'), 60000);
});

// Window unload
window.addEventListener('beforeunload', function() {
    updateUserStatus('offline');
    if (notificationCheckInterval) clearInterval(notificationCheckInterval);
});

// Update user status
function updateUserStatus(status) {
    fetch('api/update_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({status: status})
    });
}

// Switch tabs
function switchTab(tab) {
    currentTab = tab;
    
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.tab-btn').classList.add('active');
    
    document.getElementById('friendsTab').style.display = tab === 'friends' ? 'block' : 'none';
    document.getElementById('requestsTab').style.display = tab === 'requests' ? 'block' : 'none';
    
    if (tab === 'friends') loadFriends();
    else loadFriendRequests();
}

// Load friends list
async function loadFriends() {
    try {
        const response = await fetch('api/friend_request.php?action=list');
        const data = await response.json();
        
        const container = document.getElementById('friendsList');
        
        if (data.friends && data.friends.length > 0) {
            container.innerHTML = data.friends.map(friend => `
                <div class="friend-item" onclick="openChat(${friend.id})">
                    <img src="uploads/${friend.avatar || 'default-avatar.png'}" alt="Avatar" class="friend-avatar">
                    <div class="friend-info">
                        <h4>${friend.full_name || friend.username}</h4>
                        <span class="friend-status ${friend.status}">${friend.status}</span>
                    </div>
                    <div class="friend-actions">
                        <button class="icon-btn" onclick="event.stopPropagation(); startCall(${friend.id}, 'voice')" title="Voice Call">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button class="icon-btn" onclick="event.stopPropagation(); startCall(${friend.id}, 'video')" title="Video Call">
                            <i class="fas fa-video"></i>
                        </button>
                        <button class="icon-btn" onclick="event.stopPropagation(); removeFriend(${friend.id})" title="Hapus Teman">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <p>Belum ada teman</p>
                    <p class="empty-hint">Cari dan tambahkan teman untuk mulai chatting!</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading friends:', error);
    }
}

// Load friend requests
async function loadFriendRequests() {
    try {
        const response = await fetch('api/friend_request.php?action=pending');
        const data = await response.json();
        
        const container = document.getElementById('requestsList');
        const badge = document.getElementById('requestsBadge');
        
        if (data.requests && data.requests.length > 0) {
            badge.textContent = data.requests.length;
            badge.style.display = 'inline-block';
            
            container.innerHTML = data.requests.map(request => `
                <div class="request-item">
                    <img src="uploads/${request.avatar || 'default-avatar.png'}" alt="Avatar" class="request-avatar">
                    <div class="request-info">
                        <h4>${request.full_name || request.username}</h4>
                        <p>Ingin berteman dengan Anda</p>
                        <span class="request-time">${timeAgo(request.created_at)}</span>
                    </div>
                    <div class="request-actions">
                        <button class="btn-accept" onclick="acceptRequest(${request.id})">
                            <i class="fas fa-check"></i> Terima
                        </button>
                        <button class="btn-reject" onclick="rejectRequest(${request.id})">
                            <i class="fas fa-times"></i> Tolak
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            badge.style.display = 'none';
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-user-plus"></i>
                    <p>Tidak ada permintaan pertemanan</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading requests:', error);
    }
}

// Search users
async function searchUsers() {
    const query = document.getElementById('searchInput').value.trim();
    const resultsDiv = document.getElementById('searchResults');
    
    if (query.length < 2) {
        resultsDiv.style.display = 'none';
        return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`api/friend_request.php?action=search&q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.users && data.users.length > 0) {
                resultsDiv.innerHTML = data.users.map(user => `
                    <div class="search-item">
                        <img src="uploads/${user.avatar || 'default-avatar.png'}" alt="Avatar" class="search-avatar">
                        <div class="search-info">
                            <h4>${user.full_name || user.username}</h4>
                            <span class="user-status ${user.status}">${user.status}</span>
                        </div>
                        <div class="search-action">
                            ${getActionButton(user)}
                        </div>
                    </div>
                `).join('');
                resultsDiv.style.display = 'block';
            } else {
                resultsDiv.innerHTML = '<p class="no-results">Tidak ada pengguna ditemukan</p>';
                resultsDiv.style.display = 'block';
            }
        } catch (error) {
            console.error('Error searching users:', error);
        }
    }, 300);
}

// Get action button for search result
function getActionButton(user) {
    if (user.is_friend) {
        return `<button class="btn-friend" disabled><i class="fas fa-check"></i> Teman</button>`;
    } else if (user.request_status === 'pending') {
        return `<button class="btn-pending" disabled><i class="fas fa-clock"></i> Menunggu</button>`;
    } else {
        return `<button class="btn-add" onclick="sendFriendRequest(${user.id})"><i class="fas fa-user-plus"></i> Tambah</button>`;
    }
}

// Send friend request
async function sendFriendRequest(userId) {
    try {
        const response = await fetch('api/friend_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=send&user_id=${userId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Permintaan pertemanan terkirim!');
            searchUsers();
        } else {
            alert(data.error || 'Gagal mengirim permintaan');
        }
    } catch (error) {
        console.error('Error sending friend request:', error);
    }
}

// Accept friend request
async function acceptRequest(requestId) {
    try {
        const response = await fetch('api/friend_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=accept&request_id=${requestId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadFriendRequests();
            loadFriends();
        } else {
            alert(data.error || 'Gagal menerima permintaan');
        }
    } catch (error) {
        console.error('Error accepting request:', error);
    }
}

// Reject friend request
async function rejectRequest(requestId) {
    if (!confirm('Tolak permintaan pertemanan ini?')) return;
    
    try {
        const response = await fetch('api/friend_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=reject&request_id=${requestId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadFriendRequests();
        }
    } catch (error) {
        console.error('Error rejecting request:', error);
    }
}

// Remove friend
async function removeFriend(friendId) {
    if (!confirm('Hapus teman ini dari daftar?')) return;
    
    try {
        const response = await fetch('api/friend_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=remove&user_id=${friendId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadFriends();
        }
    } catch (error) {
        console.error('Error removing friend:', error);
    }
}

// Open chat with friend
function openChat(userId) {
    window.location.href = `chat.php?user=${userId}`;
}

// Check notifications
async function checkNotifications() {
    try {
        const response = await fetch('api/notifications.php?action=count');
        const data = await response.json();
        
        const badge = document.getElementById('notificationBadge');
        if (data.count > 0) {
            badge.textContent = data.count > 9 ? '9+' : data.count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

// Toggle notifications panel
async function toggleNotifications() {
    const panel = document.getElementById('notificationsPanel');
    
    if (panel.style.display === 'none') {
        // Load notifications
        try {
            const response = await fetch('api/notifications.php?action=get');
            const data = await response.json();
            
            const container = document.getElementById('notificationsList');
            
            if (data.notifications && data.notifications.length > 0) {
                container.innerHTML = data.notifications.map(notif => `
                    <div class="notification-item ${notif.is_read ? '' : 'unread'}" onclick="handleNotification(${notif.id}, '${notif.type}', ${notif.reference_id || 0})">
                        <i class="fas ${getNotificationIcon(notif.type)}"></i>
                        <div class="notification-content">
                            <h4>${notif.title}</h4>
                            <p>${notif.content}</p>
                            <span class="notification-time">${timeAgo(notif.created_at)}</span>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="no-notifications">Tidak ada notifikasi</p>';
            }
            
            panel.style.display = 'block';
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    } else {
        panel.style.display = 'none';
    }
}

// Get notification icon
function getNotificationIcon(type) {
    const icons = {
        'message': 'fa-comment',
        'friend_request': 'fa-user-plus',
        'call': 'fa-phone',
        'system': 'fa-info-circle'
    };
    return icons[type] || 'fa-bell';
}

// Mark all notifications as read
async function markAllNotificationsRead() {
    try {
        await fetch('api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=mark_read'
        });
        
        checkNotifications();
        toggleNotifications();
    } catch (error) {
        console.error('Error marking notifications:', error);
    }
}

// Handle notification click
function handleNotification(id, type, refId) {
    // Mark as read
    fetch('api/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=mark_read&id=${id}`
    });
    
    // Navigate based on type
    switch (type) {
        case 'friend_request':
            switchTab('requests');
            break;
        case 'message':
            if (refId) openChat(refId);
            break;
        case 'call':
            // Handle call notification
            break;
    }
    
    toggleNotifications();
}

// Time ago function
function timeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
    return date.toLocaleDateString('id-ID');
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-section')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});