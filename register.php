<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}

// Check if registration is enabled
if (!getSetting('registration_enabled')) {
    die('Pendaftaran sedang ditutup untuk sementara.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username harus 3-50 karakter';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Password tidak cocok';
    }
    
    // Check existing user
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Username atau email sudah terdaftar';
        }
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, role, status)
                VALUES (?, ?, ?, ?, 'user', 'offline')
            ");
            $stmt->execute([$username, $email, $hashedPassword, $fullName]);
            
            $userId = $pdo->lastInsertId();
            logActivity($userId, 'register', 'New user registration');
            
            $success = 'Pendaftaran berhasil! Silakan login.';
            
            // Auto login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            
            updateUserStatus($userId, 'online');
            header('Refresh: 2; URL=chat.php');
            
        } catch (PDOException $e) {
            $error = 'Gagal mendaftar: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - ChatKom</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-user-plus"></i>
                <h1>Daftar ChatKom</h1>
                <p>Bergabung dengan komunitas ChatKom</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username *
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Pilih username" minlength="3" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-id-card"></i>
                        Nama Lengkap
                    </label>
                    <input type="text" id="full_name" name="full_name" 
                           placeholder="Nama lengkap (opsional)">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email *
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="alamat@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password *
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required 
                               placeholder="Minimal 6 karakter" minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Konfirmasi Password *
                    </label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Ulangi password">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" required> Saya setuju dengan 
                        <a href="terms.php">Syarat & Ketentuan</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Daftar Sekarang
                </button>
                
                <div class="auth-footer">
                    Sudah punya akun? <a href="login.php">Login disini</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
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
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strength = checkPasswordStrength(password);
            displayPasswordStrength(strength);
        });
        
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            return strength;
        }
        
        function displayPasswordStrength(strength) {
            const colors = ['#ddd', '#ff4444', '#ffaa00', '#44aa00', '#00cc00'];
            const texts = ['', 'Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'];
            
            let indicator = document.getElementById('password-strength');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'password-strength';
                document.getElementById('password').parentElement.after(indicator);
            }
            
            indicator.innerHTML = `
                <div class="strength-bar">
                    <div class="strength-fill" style="width: ${strength * 25}%; background: ${colors[strength]}"></div>
                </div>
                <span class="strength-text" style="color: ${colors[strength]}">${texts[strength]}</span>
            `;
        }
    </script>
</body>
</html>