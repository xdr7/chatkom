<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Jika user tidak ditemukan, redirect
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Handle avatar upload
        $avatar = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['avatar']);
            if ($uploadResult['success']) {
                $avatar = basename($uploadResult['path']);
            } else {
                $error = $uploadResult['error'];
            }
        }
        
        if (empty($error)) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, bio = ?, email = ?, avatar = ?
                WHERE id = ?
            ");
            if ($stmt->execute([$fullName, $bio, $email, $avatar, $userId])) {
                $message = 'Profil berhasil diperbarui';
                $user = getUserById($userId);
                logActivity($userId, 'update_profile', 'Updated profile information');
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Password saat ini salah';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password baru minimal 6 karakter';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Password baru tidak cocok';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashedPassword, $userId])) {
                $message = 'Password berhasil diubah';
                logActivity($userId, 'change_password', 'Changed password');
            }
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    updateUserStatus($userId, 'offline');
    logActivity($userId, 'logout', 'User logged out');
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - ChatKom</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="profile-wrapper">
        <!-- SIDEBAR NAVIGASI - DITAMBAHKAN -->
        <div class="profile-sidebar">
            <div class="sidebar-header">
                <div class="user-profile-mini">
                    <img src="uploads/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="avatar-small">
                    <div class="user-info-mini">
                        <h4><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h4>
                        <span class="role-badge <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                </div>
            </div>
            
            <ul class="profile-nav">
                <li class="nav-item active">
                    <a href="#profile-info">
                        <i class="fas fa-user"></i>
                        <span>Informasi Profil</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#change-password">
                        <i class="fas fa-lock"></i>
                        <span>Ubah Password</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#privacy">
                        <i class="fas fa-shield-alt"></i>
                        <span>Privasi & Keamanan</span>
                    </a>
                </li>
                <li class="nav-divider"></li>
                <li class="nav-item">
                    <a href="chat.php">
                        <i class="fas fa-comments"></i>
                        <span>Kembali ke Chat</span>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a href="admin/dashboard.php">
                        <i class="fas fa-gauge"></i>
                        <span>Admin Panel</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-divider"></li>
                <li class="nav-item nav-danger">
                    <a href="?logout=1" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <p>Created by <strong>Sasskom.app</strong></p>
            </div>
        </div>
        
        <!-- MAIN CONTENT -->
        <div class="profile-main">
            <div class="profile-header">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Pengaturan Profil</h1>
                <button class="logout-btn-mobile" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
            
            <div class="profile-content">
                <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <!-- INFORMASI PROFIL -->
                <div class="profile-section" id="profile-info">
                    <h2><i class="fas fa-user-circle"></i> Informasi Profil</h2>
                    <form method="POST" enctype="multipart/form-data" class="profile-form">
                        <div class="avatar-upload">
                            <img src="uploads/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="Avatar" class="profile-avatar" id="avatarPreview">
                            <label for="avatar" class="avatar-upload-btn">
                                <i class="fas fa-camera"></i> Ganti Foto
                            </label>
                            <input type="file" id="avatar" name="avatar" accept="image/*" 
                                   onchange="previewAvatar(this)" style="display: none;">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-at"></i> Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small>Username tidak dapat diubah</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name"><i class="fas fa-user"></i> Nama Lengkap</label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                       placeholder="Masukkan nama lengkap">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio"><i class="fas fa-quote-right"></i> Bio</label>
                            <textarea id="bio" name="bio" rows="3" 
                                      placeholder="Ceritakan tentang dirimu..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
                
                <!-- UBAH PASSWORD -->
                <div class="profile-section" id="change-password">
                    <h2><i class="fas fa-key"></i> Ubah Password</h2>
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <div class="password-input">
                                <input type="password" id="current_password" name="current_password" required 
                                       placeholder="Masukkan password saat ini">
                                <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <div class="password-input">
                                <input type="password" id="new_password" name="new_password" required minlength="6"
                                       placeholder="Minimal 6 karakter">
                                <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       placeholder="Ulangi password baru">
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-secondary">
                            <i class="fas fa-sync-alt"></i> Ubah Password
                        </button>
                    </form>
                </div>
                
                <!-- PRIVASI & KEAMANAN -->
                <div class="profile-section" id="privacy">
                    <h2><i class="fas fa-shield-alt"></i> Privasi & Keamanan</h2>
                    <div class="settings-list">
                        <div class="setting-item">
                            <div class="setting-info">
                                <h3>Status Online</h3>
                                <p>Tampilkan status online ke pengguna lain</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <h3>Notifikasi Pesan</h3>
                                <p>Terima notifikasi saat ada pesan baru</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <h3>Notifikasi Suara</h3>
                                <p>Putar suara saat ada pesan baru</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- ZONA BERBAHAYA -->
                <div class="profile-section danger-zone">
                    <h2><i class="fas fa-exclamation-triangle"></i> Zona Berbahaya</h2>
                    <p class="danger-warning">Tindakan di bawah ini tidak dapat dibatalkan. Harap berhati-hati.</p>
                    
                    <div class="danger-actions">
                        <button class="btn-danger-outline" onclick="confirmLogoutAll()">
                            <i class="fas fa-sign-out-alt"></i> Logout dari Semua Perangkat
                        </button>
                        <button class="btn-danger" onclick="confirmDeleteAccount()">
                            <i class="fas fa-trash-alt"></i> Hapus Akun Permanen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.profile-sidebar').classList.toggle('show');
        }
        
        // Preview avatar before upload
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const button = input.parentElement.querySelector('.toggle-password i');
            
            if (input.type === 'password') {
                input.type = 'text';
                button.classList.remove('fa-eye');
                button.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                button.classList.remove('fa-eye-slash');
                button.classList.add('fa-eye');
            }
        }
        
        // Logout biasa
        function confirmLogout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '?logout=1';
            }
        }
        
        // Logout semua perangkat
        function confirmLogoutAll() {
            if (confirm('Apakah Anda yakin ingin logout dari SEMUA perangkat?')) {
                window.location.href = 'logout.php?all=true';
            }
        }
        
        // Hapus akun
        function confirmDeleteAccount() {
            if (confirm('⚠️ PERINGATAN: Akun Anda akan dihapus PERMANEN!\n\nSemua data, pesan, dan file akan hilang selamanya.\n\nLanjutkan?')) {
                if (confirm('Ini adalah tindakan TERAKHIR. Yakin ingin menghapus akun?')) {
                    window.location.href = 'api/delete_account.php';
                }
            }
        }
        
        // Smooth scroll to section
        document.querySelectorAll('.profile-nav a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // Update active state
                document.querySelectorAll('.profile-nav .nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                this.parentElement.classList.add('active');
                
                // Close sidebar on mobile
                document.querySelector('.profile-sidebar').classList.remove('show');
            });
        });
        
        // Highlight active section on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.profile-section[id]');
            const scrollY = window.pageYOffset;
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionBottom = sectionTop + section.offsetHeight;
                const sectionId = section.getAttribute('id');
                
                if (scrollY >= sectionTop && scrollY < sectionBottom) {
                    document.querySelectorAll('.profile-nav .nav-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    document.querySelector(`.profile-nav a[href="#${sectionId}"]`)?.parentElement.classList.add('active');
                }
            });
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.profile-sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuBtn.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>