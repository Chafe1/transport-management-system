<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect them to their respective dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('user_dashboard.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TMS - Modern Transport Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="bg-blobs">
        <div class="blob"></div>
        <div class="blob blob-2"></div>
    </div>

    <nav class="navbar">
        <div class="logo">
            <h2>TMS PRO</h2>
        </div>
        <div class="nav-menu">
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="nav-cta">
            <a href="login.php" class="btn-outline">Login</a>
            <a href="register.php" class="btn">Get Started</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1>The Future of <br><span style="color: var(--primary)">Transport</span> Logistics</h1>
            <p>Smart, efficient, and reliable management for your entire transport fleet. Experience the next generation of logistics tracking and booking.</p>
            <div style="display: flex; gap: 20px;">
                <a href="register.php" class="btn" style="width: auto; padding: 15px 40px;">Explore Now</a>
                <a href="#features" class="btn-outline" style="width: auto; padding: 15px 40px; border-color: rgba(255,255,255,0.2); color: white;">Learn More</a>
            </div>
        </div>
        <div class="hero-image"></div>
    </header>

    <section id="features" class="features">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bolt"></i></div>
            <h3>Real-time Booking</h3>
            <p>Instant confirmation and live updates for all your transport needs. Never miss a ride again.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Secure Logistics</h3>
            <p>End-to-end encryption for all transactions and fleet management data. Your security is our priority.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <h3>Advanced Analytics</h3>
            <p>Detailed insights into fleet performance, route efficiency, and booking trends for administrators.</p>
        </div>
    </section>

    <footer style="padding: 60px 80px; text-align: center; border-top: 1px solid var(--glass-border); margin-top: 50px;">
        <p style="color: var(--text-dim)">&copy; 2026 TMS PRO. All rights reserved.</p>
    </footer>
</body>
</html>
