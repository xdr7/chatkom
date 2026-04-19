<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle delete user
if ($action === 'delete' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Jangan hapus diri sendiri
    if ($userId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$userId])) {
            $message = 'Pengguna berhasil dihapus';
        } else {
            $error = 'Gagal menghapus pengguna';
        }
    } else {
        $error = 'Tidak dapat menghapus akun sendiri';
    }
}

// Handle update role
if ($action === 'role' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $userId = (int)$_POST['user_id'];
    $role = $_POST['role'];
    
    if ($userId != $_SESSION['user_id'] || $_SESSION['role'] === 'superadmin') {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        if ($stmt->execute([$role, $userId])) {
            $message = 'Role pengguna berhasil diupdate';
        }
    }
}

// Get all users
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $sql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
if ($search) {
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll();

$currentUser = getUserById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - ChatKom Admin</title>
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
                <li class="nav-item active">
                    <a href="users.php"><i class="fas fa-users"></i> Pengguna</a>
                </li>
                <li class="nav-item">
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
                <h1><i class="fas fa-users"></i> Manajemen Pengguna</h1>
                <p>Kelola semua pengguna terdaftar</p>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-card">
                <div class="card-header">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari pengguna..." value="<?php echo htmlspecialchars($search); ?>">
                        <button onclick="searchUsers()" class="btn-search">Cari</button>
                    </div>
                    <div class="card-actions">
                        <span class="total-count">Total: <?php echo count($users); ?> pengguna</span>
                    </div>
                </div>
                
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pengguna</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <div class="user-cell">
                                        <img src="../uploads/<?php echo htmlspecialchars($user['avatar'] ?? 'default-avatar.png'); ?>" alt="Avatar">
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <br>
                                            <small><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id'] || isSuperAdmin()): ?>
                                    <select class="role-select" onchange="updateRole(<?php echo $user['id']; ?>, this.value)">
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <?php if (isSuperAdmin()): ?>
                                        <option value="superadmin" <?php echo $user['role'] === 'superadmin' ? 'selected' : ''; ?>>Superadmin</option>
                                        <?php endif; ?>
                                    </select>
                                    <?php else: ?>
                                    <span class="role-badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../chat.php?user=<?php echo $user['id']; ?>" class="btn-icon" title="Chat">
                                            <i class="fas fa-comment"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                           class="btn-icon danger" 
                                           onclick="return confirm('Hapus pengguna ini?')"
                                           title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <form id="roleForm" method="POST" action="?action=role" style="display: none;">
        <input type="hidden" name="user_id" id="roleUserId">
        <input type="hidden" name="role" id="roleValue">
    </form>
    
    <script>
        function searchUsers() {
            const search = document.getElementById('searchInput').value;
            window.location.href = '?search=' + encodeURIComponent(search);
        }
        
        function updateRole(userId, role) {
            if (confirm('Ubah role pengguna ini?')) {
                document.getElementById('roleUserId').value = userId;
                document.getElementById('roleValue').value = role;
                document.getElementById('roleForm').submit();
            } else {
                location.reload();
            }
        }
        
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });
    </script>
</body>
</html>