<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Cek role (admin atau superadmin)
if (!isAdmin()) {
    header('Location: ../chat.php');
    exit;
}

$userId = $_SESSION['user_id'];
$currentUser = getUserById($userId);

// Statistik
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$onlineUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'online'")->fetchColumn();
$todayMessages = $pdo->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// User terbaru
$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Aktivitas terbaru
$recentActivities = $pdo->query("
    SELECT al.*, u.username 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ChatKom</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
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
                <li class="nav-item active">
                    <a href="dashboard.php">
                        <i class="fas fa-dashboard"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php">
                        <i class="fas fa-users"></i> Pengguna
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages.php">
                        <i class="fas fa-comments"></i> Pesan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Pengaturan
                    </a>
                </li>
                <?php if (isSuperAdmin()): ?>
                <li class="nav-item">
                    <a href="logs.php">
                        <i class="fas fa-history"></i> Activity Logs
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-divider"></li>
                <li class="nav-item">
                    <a href="../chat.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Chat
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']); ?>!</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($totalUsers); ?></h3>
                        <p>Total Pengguna</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($totalMessages); ?></h3>
                        <p>Total Pesan</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $onlineUsers; ?></h3>
                        <p>Online Sekarang</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($todayMessages); ?></h3>
                        <p>Pesan Hari Ini</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users & Activities -->
            <div class="admin-row">
                <div class="admin-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-plus"></i> Pengguna Terbaru</h2>
                        <a href="users.php" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <img src="../uploads/<?php echo htmlspecialchars($user['avatar'] ?? 'default-avatar.png'); ?>" alt="Avatar">
                                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                    <td><span class="role-badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                                    <td><span class="status-badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Aktivitas Terbaru</h2>
                        <a href="logs.php" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php 
                                        echo $activity['action'] == 'login' ? 'sign-in-alt' : 
                                            ($activity['action'] == 'logout' ? 'sign-out-alt' : 
                                            ($activity['action'] == 'send_message' ? 'comment' : 'info-circle')); 
                                    ?>"></i>
                                </div>
                                <div class="activity-info">
                                    <p>
                                        <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                                        <?php echo htmlspecialchars($activity['action']); ?>
                                    </p>
                                    <span class="activity-time"><?php echo timeAgo($activity['created_at']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-footer">
                <p>ChatKom Admin Panel - Created by <strong>Sasskom.app</strong></p>
            </div>
        </div>
    </div>
</body>
</html>