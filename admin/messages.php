<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Handle delete message
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $msgId = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    if ($stmt->execute([$msgId])) {
        $message = 'Pesan berhasil dihapus';
    }
}

// Get all messages
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$sql = "SELECT m.*, s.username as sender_name, r.username as receiver_name 
        FROM messages m
        JOIN users s ON m.sender_id = s.id
        JOIN users r ON m.receiver_id = r.id
        WHERE 1=1";
if ($search) {
    $sql .= " AND (m.message LIKE ? OR s.username LIKE ? OR r.username LIKE ?)";
}
$sql .= " ORDER BY m.created_at DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
if ($search) {
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
} else {
    $stmt->execute([$limit, $offset]);
}
$messages = $stmt->fetchAll();

// Total count
$countSql = "SELECT COUNT(*) FROM messages";
$totalMessages = $pdo->query($countSql)->fetchColumn();
$totalPages = ceil($totalMessages / $limit);

$currentUser = getUserById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesan - ChatKom Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-brand">
                <i class="fas fa-comments"></i>
                <span>ChatKom Admin</span>
            </div>
            
            <div class="admin-user">
                <img src="../uploads/<?php echo htmlspecialchars($currentUser['avatar'] ?? 'default-avatar.png'); ?>" alt="Avatar">
                <div>
                    <h4><?php echo htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']); ?></h4>
                    <span class="role-badge <?php echo $currentUser['role']; ?>"><?php echo ucfirst($currentUser['role']); ?></span>
                </div>
            </div>
            
            <ul class="admin-nav">
                <li class="nav-item">
                    <a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="users.php"><i class="fas fa-users"></i> Pengguna</a>
                </li>
                <li class="nav-item active">
                    <a href="messages.php"><i class="fas fa-comments"></i> Pesan</a>
                </li>
                <li class="nav-item">
                    <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
                </li>
                <?php if (isSuperAdmin()): ?>
                <li class="nav-item">
                    <a href="logs.php"><i class="fas fa-history"></i> Activity Logs</a>
                </li>
                <?php endif; ?>
                <li class="nav-divider"></li>
                <li class="nav-item">
                    <a href="../chat.php"><i class="fas fa-arrow-left"></i> Kembali ke Chat</a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-comments"></i> Manajemen Pesan</h1>
                <p>Lihat dan kelola semua pesan</p>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="admin-card">
                <div class="card-header">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari pesan..." value="<?php echo htmlspecialchars($search); ?>">
                        <button onclick="searchMessages()" class="btn-search">Cari</button>
                    </div>
                </div>
                
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pengirim</th>
                                <th>Penerima</th>
                                <th>Pesan</th>
                                <th>Tipe</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['sender_name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['receiver_name']); ?></td>
                                <td>
                                    <?php if ($msg['message_type'] === 'image'): ?>
                                        <i class="fas fa-image"></i> Gambar
                                    <?php elseif ($msg['message_type'] === 'file'): ?>
                                        <i class="fas fa-file"></i> <?php echo htmlspecialchars($msg['file_name'] ?? 'File'); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(mb_substr($msg['message'] ?? '', 0, 50)); ?>...
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge"><?php echo $msg['message_type']; ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                                <td>
                                    <a href="?delete=1&id=<?php echo $msg['id']; ?>" 
                                       class="btn-icon danger" 
                                       onclick="return confirm('Hapus pesan ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function searchMessages() {
            const search = document.getElementById('searchInput').value;
            window.location.href = '?search=' + encodeURIComponent(search);
        }
        
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchMessages();
            }
        });
    </script>
</body>
</html>