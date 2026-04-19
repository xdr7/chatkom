// Notifications Management
let notificationPanel = null;
let notificationInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    notificationPanel = document.getElementById('notificationPanel');
    
    // Start checking notifications
    checkNotificationCount();
    notificationInterval = setInterval(checkNotificationCount, 10000); // Every 10 seconds
    
    // Close panel when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationPanel && notificationPanel.style.display === 'block') {
            if (!notificationPanel.contains(e.target) && !e.target.closest('.notification-btn')) {
                notificationPanel.style.display = 'none';
            }
        }
    });
});

// Check notification count
async function checkNotificationCount() {
    try {
        const response = await fetch(siteUrl + '/api/notifications.php?action=count');
        const data = await response.json();
        
        const badge = document.querySelector('.notification-btn .icon-badge');
        if (badge) {
            if (data.count > 0) {
                badge.textContent = data.count > 9 ? '9+' : data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

// Toggle notification panel
async function toggleNotificationPanel() {
    if (!notificationPanel) return;
    
    if (notificationPanel.style.display === 'block') {
        notificationPanel.style.display = 'none';
    } else {
        await loadNotifications();
        notificationPanel.style.display = 'block';
    }
}

// Load notifications
async function loadNotifications() {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    container.innerHTML = '<div class="loading">Memuat notifikasi...</div>';
    
    try {
        const response = await fetch(siteUrl + '/api/notifications.php?action=get&limit=30');
        const data = await response.json();
        
        if (data.notifications && data.notifications.length > 0) {
            container.innerHTML = data.notifications.map(notif => `
                <div class="notification-item ${notif.is_read ? '' : 'unread'}" onclick="handleNotificationClick(${notif.id}, '${notif.type}', ${notif.reference_id || 0})">
                    <i class="fas ${getNotificationIcon(notif.type)}"></i>
                    <div class="notification-content">
                        <h4>${escapeHtml(notif.title)}</h4>
                        <p>${escapeHtml(notif.content)}</p>
                        <span class="notification-time">${timeAgo(notif.created_at)}</span>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="no-notifications">
                    <i class="far fa-bell-slash"></i>
                    <p>Tidak ada notifikasi</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        container.innerHTML = '<div class="no-notifications">Gagal memuat notifikasi</div>';
    }
}

// Get notification icon
function getNotificationIcon(type) {
    const icons = {
        'message': 'fa-comment-dots',
        'friend_request': 'fa-user-plus',
        'call': 'fa-phone-alt',
        'system': 'fa-info-circle'
    };
    return icons[type] || 'fa-bell';
}

// Handle notification click
async function handleNotificationClick(id, type, refId) {
    // Mark as read
    try {
        await fetch(siteUrl + '/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=mark_read&id=${id}`
        });
        
        checkNotificationCount();
    } catch (error) {
        console.error('Error marking notification:', error);
    }
    
    // Navigate based on type
    switch (type) {
        case 'friend_request':
            window.location.href = siteUrl + '/friends.php?tab=requests';
            break;
        case 'message':
            if (refId) openConversation(refId);
            toggleNotificationPanel();
            break;
        case 'call':
            // Just close panel
            toggleNotificationPanel();
            break;
    }
}

// Mark all notifications as read
async function markAllNotificationsRead() {
    try {
        await fetch(siteUrl + '/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=mark_read'
        });
        
        await loadNotifications();
        checkNotificationCount();
    } catch (error) {
        console.error('Error marking notifications:', error);
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});