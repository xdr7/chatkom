<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = str_replace('setting_', '', $key);
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $settingKey]);
        }
    }
    $message = 'Pengaturan berhasil disimpan';
}

// Get all settings
$settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['setting_key']] = $s;
}

$currentUser = getUserById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - ChatKom Admin</title>
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
                <li class="nav-item active">
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
                <h1><i class="fas fa-cog"></i> Pengaturan Sistem</h1>
                <p>Konfigurasi aplikasi ChatKom</p>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="admin-card">
                <div class="card-header">
                    <h2>Pengaturan Umum</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label>Nama Situs</label>
                            <input type="text" name="setting_site_name" 
                                   value="<?php echo htmlspecialchars($settingsMap['site_name']['setting_value'] ?? 'ChatKom'); ?>" required>
                            <small>Nama yang ditampilkan di header dan title</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Ukuran Maksimal File Upload (MB)</label>
                            <input type="number" name="setting_max_file_size" 
                                   value="<?php echo round(($settingsMap['max_file_size']['setting_value'] ?? 10485760) / 1048576); ?>" required>
                            <small>Ukuran maksimal file yang bisa diupload (dalam MB)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Tipe File yang Diizinkan</label>
                            <input type="text" name="setting_allowed_file_types" 
                                   value="<?php echo htmlspecialchars($settingsMap['allowed_file_types']['setting_value'] ?? '["jpg","jpeg","png","gif","pdf","doc","docx","txt","zip"]'); ?>" required>
                            <small>Format JSON array, contoh: ["jpg","png","pdf"]</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Mode Maintenance</label>
                            <select name="setting_maintenance_mode">
                                <option value="false" <?php echo ($settingsMap['maintenance_mode']['setting_value'] ?? 'false') === 'false' ? 'selected' : ''; ?>>Nonaktif</option>
                                <option value="true" <?php echo ($settingsMap['maintenance_mode']['setting_value'] ?? 'false') === 'true' ? 'selected' : ''; ?>>Aktif</option>
                            </select>
                            <small>Aktifkan mode maintenance untuk menonaktifkan akses publik</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Pendaftaran Pengguna Baru</label>
                            <select name="setting_registration_enabled">
                                <option value="true" <?php echo ($settingsMap['registration_enabled']['setting_value'] ?? 'true') === 'true' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="false" <?php echo ($settingsMap['registration_enabled']['setting_value'] ?? 'true') === 'false' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                            <small>Izinkan pengguna baru mendaftar</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header">
                    <h2>Informasi Sistem</h2>
                </div>
                <div class="card-body">
                    <table class="info-table">
                        <tr>
                            <td>PHP Version</td>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <td>Database</td>
                            <td>MySQL <?php echo $pdo->query("SELECT VERSION()")->fetchColumn(); ?></td>
                        </tr>
                        <tr>
                            <td>Upload Max Size</td>
                            <td><?php echo ini_get('upload_max_filesize'); ?></td>
                        </tr>
                        <tr>
                            <td>Post Max Size</td>
                            <td><?php echo ini_get('post_max_size'); ?></td>
                        </tr>
                        <tr>
                            <td>Memory Limit</td>
                            <td><?php echo ini_get('memory_limit'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>