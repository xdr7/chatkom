<?php
require_once 'includes/config.php';

// If logged in, redirect to chat
if (isset($_SESSION['user_id'])) {
    header('Location: chat.php');
    exit;
}

// Get site statistics with error handling
$totalUsers = 0;
$totalMessages = 0;
$onlineUsers = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    $totalUsers = $result['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM messages");
    $result = $stmt->fetch();
    $totalMessages = $result['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'online'");
    $result = $stmt->fetch();
    $onlineUsers = $result['total'] ?? 0;
} catch (Exception $e) {
    // Silently fail, use default 0
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="description" content="ChatKom - Aplikasi chat modern dengan fitur lengkap. Dibuat oleh Sasskom.app">
    <meta name="keywords" content="chat, messaging, komunikasi, chat app, Indonesia">
    <meta name="author" content="Sasskom.app">
    <title>ChatKom - Modern Chat Application | Created by Sasskom.app</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <div class="nav-brand">
                <i class="fas fa-comments"></i>
                <span>ChatKom</span>
            </div>
            
            <div class="nav-menu" id="navMenu">
                <a href="#home" class="nav-link active">Beranda</a>
                <a href="#features" class="nav-link">Fitur</a>
                <a href="#stats" class="nav-link">Statistik</a>
                <a href="#about" class="nav-link">Tentang</a>
            </div>
            
            <div class="nav-buttons">
                <a href="login.php" class="btn-outline">Login</a>
                <a href="register.php" class="btn-primary">Daftar</a>
                <button class="mobile-toggle" id="mobileToggle" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <div class="hero-badge">
                        <i class="fas fa-heart" style="color: #ff4757;"></i>
                        <span>Created by Sasskom.app</span>
                    </div>
                    
                    <h1>
                        Chat Lebih Mudah dengan 
                        <span class="gradient-text">ChatKom</span>
                    </h1>
                    
                    <p class="hero-description">
                        Aplikasi chat modern dengan fitur lengkap. Kirim pesan, gambar, file, 
                        dan ekspresikan diri dengan emoji. Gratis selamanya!
                    </p>
                    
                    <div class="hero-stats">
                        <div class="stat-item">
                            <h3><?php echo number_format($totalUsers, 0, ',', '.'); ?>+</h3>
                            <p>Pengguna</p>
                        </div>
                        <div class="stat-item">
                            <h3><?php echo number_format($totalMessages, 0, ',', '.'); ?>+</h3>
                            <p>Pesan</p>
                        </div>
                        <div class="stat-item">
                            <h3><?php echo $onlineUsers; ?></h3>
                            <p>Online</p>
                        </div>
                    </div>
                    
                    <div class="hero-cta">
                        <a href="register.php" class="btn-primary btn-large">
                            <i class="fas fa-user-plus"></i> Mulai Sekarang
                        </a>
                        <a href="login.php" class="btn-outline-light btn-large">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
                
                <div class="hero-image">
                    <div class="chat-preview">
                        <div class="chat-preview-header">
                            <div class="preview-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="preview-title">
                                <i class="fas fa-comment-dots"></i>
                                <span>Chat Preview</span>
                            </div>
                        </div>
                        
                        <div class="chat-preview-body">
                            <div class="message received">
                                <div class="msg-avatar">S</div>
                                <div class="msg-content">
                                    <p>Hai! Selamat datang di ChatKom 👋</p>
                                    <span class="msg-time">10:30</span>
                                </div>
                            </div>
                            
                            <div class="message sent">
                                <div class="msg-content">
                                    <p>Wah, keren banget! 😍</p>
                                    <span class="msg-time">10:31</span>
                                </div>
                            </div>
                            
                            <div class="message received">
                                <div class="msg-avatar">A</div>
                                <div class="msg-content">
                                    <p>Yuk mulai chatting dengan teman-temanmu!</p>
                                    <span class="msg-time">10:31</span>
                                </div>
                            </div>
                            
                            <div class="message sent">
                                <div class="msg-content">
                                    <p>Fitur lengkap, kirim file & gambar juga! 🎉</p>
                                    <span class="msg-time">10:32</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-preview-footer">
                            <button class="emoji-btn" disabled>
                                <i class="far fa-smile"></i>
                            </button>
                            <input type="text" placeholder="Ketik pesan..." disabled>
                            <button class="send-btn" disabled>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hero-wave">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#ffffff" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">FITUR UNGGULAN</span>
                <h2>Kenapa Harus ChatKom?</h2>
                <p>Dibangun dengan teknologi modern untuk pengalaman chat terbaik</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Real-time Chat</h3>
                    <p>Pesan terkirim dan diterima secara instan tanpa delay. Rasakan pengalaman chat yang mulus dan responsif.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-image"></i>
                    </div>
                    <h3>Kirim File & Gambar</h3>
                    <p>Bagikan momen berharga dengan mengirim gambar dan berbagai jenis file dengan mudah dan cepat.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="far fa-smile"></i>
                    </div>
                    <h3>Emoji Lengkap</h3>
                    <p>Ekspresikan dirimu dengan ribuan emoji yang tersedia. Chat jadi lebih berwarna dan menyenangkan!</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Akses dari mana saja dengan tampilan responsif yang optimal di semua perangkat smartphone dan tablet.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Keamanan Terjamin</h3>
                    <p>Data Anda aman dengan enkripsi modern dan sistem keamanan berlapis. Privasi Anda adalah prioritas kami.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Admin Panel</h3>
                    <p>Kelola semua aspek aplikasi dengan panel admin yang lengkap dan mudah digunakan untuk superadmin.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section" id="stats">
        <div class="container">
            <div class="stats-grid-large">
                <div class="stat-card-large">
                    <i class="fas fa-users"></i>
                    <div class="stat-number-large"><?php echo number_format($totalUsers, 0, ',', '.'); ?>+</div>
                    <div class="stat-label-large">Pengguna Terdaftar</div>
                </div>
                
                <div class="stat-card-large">
                    <i class="fas fa-comments"></i>
                    <div class="stat-number-large"><?php echo number_format($totalMessages, 0, ',', '.'); ?>+</div>
                    <div class="stat-label-large">Pesan Terkirim</div>
                </div>
                
                <div class="stat-card-large">
                    <i class="fas fa-circle"></i>
                    <div class="stat-number-large"><?php echo $onlineUsers; ?></div>
                    <div class="stat-label-large">Online Sekarang</div>
                </div>
                
                <div class="stat-card-large">
                    <i class="fas fa-clock"></i>
                    <div class="stat-number-large">24/7</div>
                    <div class="stat-label-large">Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div class="about-content">
                <h2>Tentang ChatKom</h2>
                <p class="about-description">
                    ChatKom adalah aplikasi chat modern yang dikembangkan oleh 
                    <strong>Sasskom.app</strong> dengan fokus pada kemudahan penggunaan, 
                    keamanan, dan performa tinggi. Kami percaya bahwa komunikasi yang baik 
                    adalah kunci kesuksesan, dan ChatKom hadir untuk memfasilitasi hal tersebut.
                </p>
                
                <div class="team-section">
                    <div class="team-card">
                        <i class="fas fa-code"></i>
                        <h3>Created with ❤️ by</h3>
                        <div class="team-logo">Sasskom.app</div>
                        <p>Innovative Software Solutions</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Siap Bergabung dengan ChatKom?</h2>
                <p>Daftar sekarang dan nikmati pengalaman chat terbaik bersama kami!</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn-primary btn-large">
                        <i class="fas fa-user-plus"></i> Daftar Gratis
                    </a>
                    <a href="login.php" class="btn-outline-light btn-large">
                        <i class="fas fa-sign-in-alt"></i> Sudah Punya Akun?
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="brand">
                        <i class="fas fa-comments"></i>
                        <span>ChatKom</span>
                    </div>
                    <p>Aplikasi chat modern untuk komunikasi yang lebih baik.</p>
                    <p class="footer-credit">Created with <i class="fas fa-heart" style="color: #ff4757;"></i> by <strong>Sasskom.app</strong></p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <div class="link-group">
                        <h4>Menu</h4>
                        <ul>
                            <li><a href="#home">Beranda</a></li>
                            <li><a href="#features">Fitur</a></li>
                            <li><a href="#stats">Statistik</a></li>
                            <li><a href="#about">Tentang</a></li>
                        </ul>
                    </div>
                    
                    <div class="link-group">
                        <h4>Akun</h4>
                        <ul>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Daftar</a></li>
                            <li><a href="forgot-password.php">Lupa Password</a></li>
                        </ul>
                    </div>
                    
                    <div class="link-group">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#">Syarat & Ketentuan</a></li>
                            <li><a href="#">Kebijakan Privasi</a></li>
                            <li><a href="#">Kontak</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ChatKom. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const backToTop = document.getElementById('backToTop');
            
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const navMenu = document.getElementById('navMenu');
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
            });
        }
        
        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
            });
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Back to top button
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
        
        // Add active class to nav links on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const scrollY = window.pageYOffset;
            
            sections.forEach(section => {
                const sectionHeight = section.offsetHeight;
                const sectionTop = section.offsetTop - 100;
                const sectionId = section.getAttribute('id');
                
                if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                    document.querySelector('.nav-link[href*=' + sectionId + ']')?.classList.add('active');
                } else {
                    document.querySelector('.nav-link[href*=' + sectionId + ']')?.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>