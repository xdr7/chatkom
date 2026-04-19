<?php
// includes/config.php - Main configuration file

// Development Mode
define('DEVELOPMENT_MODE', true);

// Error reporting for development
if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Password MySQL Anda
define('DB_NAME', 'chatkom_db');

// Auto-detect base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$script_dir = rtrim(dirname($script_name), '/\\');

// Remove '/includes' from path if present
if (strpos($script_dir, '/includes') !== false) {
    $script_dir = str_replace('/includes', '', $script_dir);
}

// Site configuration
define('SITE_NAME', 'ChatKom');
define('SITE_URL', $protocol . $host . $script_dir);
define('BASE_PATH', __DIR__ . '/..');
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB

// Create uploads directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // 0 for HTTP, 1 for HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    if (DEVELOPMENT_MODE) {
        die("Database Connection Failed: " . $e->getMessage());
    } else {
        die("Database connection error. Please try again later.");
    }
}

// PENTING: Include functions DULU, baru auth
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
?>
