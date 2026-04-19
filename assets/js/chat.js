// ChatKom - Chat JavaScript
let currentConversation = null;
let messagePolling = null;
let selectedFile = null;
let emojiPickerInitialized = false;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateUserStatus('online');
    
    const textarea = document.getElementById('messageInput');
    if (textarea) {
        textarea.addEventListener('input', autoResize);
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Search conversations
    const searchInput = document.getElementById('searchConversation');
    if (searchInput) {
        searchInput.addEventListener('input', filterConversations);
    }
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});

// Update user status
function updateUserStatus(status) {
    fetch(siteUrl + '/api/update_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({status: status})
    }).catch(err => console.log('Status update failed'));
}

// Toggle sidebar
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

// Filter conversations
function filterConversations() {
    const query = document.getElementById('searchConversation').value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');
    
    items.forEach(item => {
        const name = item.querySelector('h4').textContent.toLowerCase();
        item.style.display = name.includes(query) ? 'flex' : 'none';
    });
}

// Open conversation
function openConversation(userId) {
    currentConversation = userId;
    
    // Update UI
    document.querySelector('.welcome-message')?.remove();
    document.getElementById('messageInputContainer').style.display = 'block';
    
    // Load user info
    loadUserInfo(userId);
    
    // Load messages
    loadMessages(userId);
    
    // Mark as read
    markMessagesAsRead(userId);
    
    // Start polling
    if (messagePolling) clearInterval(messagePolling);
    messagePolling = setInterval(() => {
        if (currentConversation) {
            loadMessages(currentConversation, true);
        }
    }, 3000);
    
    // Update active conversation
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.userId == userId) {
            item.classList.add('active');
        }
    });
}

// Load user info
async function loadUserInfo(userId) {
    try {
        const response = await fetch(siteUrl + '/api/get_user_info.php?id=' + userId);
        const user = await response.json();
        
        document.getElementById('chatAvatar').src = 'uploads/' + (user.avatar || 'default-avatar.png');
        document.getElementById('chatUserName').textContent = user.full_name || user.username;
        document.getElementById('chatUserStatus').textContent = user.status || 'offline';
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

// Load messages
async function loadMessages(userId, append = false) {
    try {
        const response = await fetch(siteUrl + '/api/get_messages.php?user_id=' + userId);
        const messages = await response.json();
        
        const messagesList = document.getElementById('messagesList');
        messagesList.innerHTML = '';
        
        messages.forEach(message => {
            displayMessage(message);
        });
        
        scrollToBottom();
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

// Display message
function displayMessage(message) {
    const messagesList = document.getElementById('messagesList');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message ' + (message.sender_id == currentUserId ? 'sent' : 'received');
    messageDiv.dataset.messageId = message.id;
    
    let content = '';
    
    if (message.message_type === 'image') {
        content = '<img src="' + message.file_path + '" alt="Image" class="message-image" onclick="viewImage(this.src)">';
        if (message.message) {
            content += '<p>' + escapeHtml(message.message) + '</p>';
        }
    } else if (message.message_type === 'file') {
        content = `
            <div class="file-message" onclick="downloadFile('${message.file_path}', '${escapeHtml(message.file_name)}')">
                <i class="fas fa-file"></i>
                <div class="file-info">
                    <span class="file-name">${escapeHtml(message.file_name)}</span>
                    <span class="file-size">${formatFileSize(message.file_size)}</span>
                </div>
                <i class="fas fa-download"></i>
            </div>
        `;
        if (message.message) {
            content += '<p>' + escapeHtml(message.message) + '</p>';
        }
    } else {
        content = '<p>' + formatMessage(escapeHtml(message.message)) + '</p>';
    }
    
    messageDiv.innerHTML = `
        ${content}
        <div class="message-meta">
            <span class="time">${formatTime(message.created_at)}</span>
            ${message.sender_id == currentUserId ? '<span class="status">' + (message.is_read ? '✓✓' : '✓') + '</span>' : ''}
        </div>
    `;
    
    messagesList.appendChild(messageDiv);
}

// Send message
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message && !selectedFile) return;
    if (!currentConversation) {
        alert('Pilih percakapan terlebih dahulu');
        return;
    }
    
    const formData = new FormData();
    formData.append('receiver_id', currentConversation);
    formData.append('message', message);
    
    if (selectedFile) {
        formData.append('file', selectedFile);
    }
    
    try {
        const response = await fetch(siteUrl + '/api/send_message.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            clearFile();
            autoResize.call(input);
            loadMessages(currentConversation);
            updateConversationsList();
        } else {
            alert(result.error || 'Gagal mengirim pesan');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Gagal mengirim pesan');
    }
}

// Mark messages as read
async function markMessagesAsRead(userId) {
    try {
        await fetch(siteUrl + '/api/get_messages.php?user_id=' + userId);
    } catch (error) {
        console.error('Error marking messages:', error);
    }
}

// Update conversations list
async function updateConversationsList() {
    try {
        const response = await fetch(siteUrl + '/api/get_conversations.php');
        const data = await response.json();
        // Could update sidebar dynamically here
    } catch (error) {
        console.error('Error updating conversations:', error);
    }
}

// Handle file select
function handleFileSelect(input) {
    if (input.files.length > 0) {
        selectedFile = input.files[0];
        
        const preview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        
        fileName.textContent = selectedFile.name;
        preview.style.display = 'flex';
    }
}

// Clear file
function clearFile() {
    selectedFile = null;
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreview').style.display = 'none';
}

// Toggle emoji picker
function toggleEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    
    if (!emojiPickerInitialized) {
        initEmojiPicker();
        emojiPickerInitialized = true;
    }
    
    picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
}

// Initialize emoji picker
function initEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '👍', '👎', '👊', '✊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏'];
    
    let html = '<div class="emoji-grid">';
    emojis.forEach(emoji => {
        html += `<span class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</span>`;
    });
    html += '</div>';
    picker.innerHTML = html;
    
    // Close picker when clicking outside
    document.addEventListener('click', function(e) {
        if (!picker.contains(e.target) && !e.target.closest('[onclick="toggleEmojiPicker()"]')) {
            picker.style.display = 'none';
        }
    });
}

// Insert emoji
function insertEmoji(emoji) {
    const input = document.getElementById('messageInput');
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const text = input.value;
    
    input.value = text.substring(0, start) + emoji + text.substring(end);
    input.selectionStart = input.selectionEnd = start + emoji.length;
    input.focus();
    autoResize.call(input);
}

// Auto resize textarea
function autoResize() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
}

// Scroll to bottom
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

// Format time
function formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
    }
    return date.toLocaleDateString('id-ID', {day: 'numeric', month: 'short'});
}

// Format file size
function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

// Format message (links, etc)
function formatMessage(text) {
    text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
    return text;
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// View image
function viewImage(src) {
    window.open(src, '_blank');
}

// Download file
function downloadFile(path, name) {
    const link = document.createElement('a');
    link.href = path;
    link.download = name;
    link.click();
}

// Toggle theme
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

// Window unload
window.addEventListener('beforeunload', function() {
    updateUserStatus('offline');
    if (messagePolling) clearInterval(messagePolling);
});