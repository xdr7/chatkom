<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    header('Location: ../login.php');
    exit;
}

$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get logs
$stmt = $pdo->prepare("
    SELECT al.*, u.username 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$logs = $stmt->fetchAll();

// Total count
$totalLogs = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

$currentUser = getUserById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - ChatKom Admin</title>
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
                <li class="nav-item">
                    <a href="messages.php"><i class="fas fa-comments"></i> Pesan</a>
                </li>
                <li class="nav-item">
                    <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
                </li>
                <li class="nav-item active">
                    <a href="logs.php"><i class="fas fa-history"></i> Activity Logs</a>
                </li>
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
                <h1><i class="fas fa-history"></i> Activity Logs</h1>
                <p>Catatan aktivitas sistem</p>
            </div>
            
            <div class="admin-card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Aksi</th>
                                <th>Detail</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>