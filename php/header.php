<?php
/**
 * Shared Application Header
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 3: Pages, header and footer layout rules
 */

require_once __DIR__ . '/security.php';
send_security_headers();
start_secure_session();

$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'patient';
$userName = $_SESSION['user_name'] ?? 'User';

// Handout Rule 3.1: Approved system name appears ABOVE the header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Secure Clinic Appointment and Monitoring System</title>
    <link rel="stylesheet" href="css/style.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= e($extraCss) ?>">
    <?php endif; ?>
</head>
<body>

<!-- SYSTEM PROSPECT NAME: Strictly above the header on every page (Handout Section 3.1) -->
<div class="system-prospect-banner">
    <div class="container banner-inner">
        <span class="prospect-text">Secure Clinic Appointment and Monitoring System</span>
        <span class="institution-text">Caraga State University &middot; IT 107 IAS 1</span>
    </div>
</div>

<!-- MAIN HEADER & NAVIGATION -->
<header class="main-header">
    <div class="container header-inner">
        <div class="brand">
            <a href="index.php" class="brand-link">
                <span class="brand-icon">&#9877;</span>
                <span class="brand-name">CSU Clinic</span>
            </a>
        </div>

        <!-- Handout Section 3.2: Header links sit in upper-right corner -->
        <nav class="nav-links">
            <?php if (!$isLoggedIn): ?>
                <?php if ($currentPage === 'index.php'): ?>
                    <a href="index.php" class="nav-item active">Home</a>
                    <span class="nav-divider">|</span>
                    <a href="register.php" class="nav-item">Register</a>
                    <span class="nav-divider">|</span>
                    <a href="login.php" class="nav-item">Log-in</a>
                <?php elseif ($currentPage === 'login.php'): ?>
                    <a href="index.php" class="nav-item">Home</a>
                    <span class="nav-divider">|</span>
                    <a href="register.php" class="nav-item">Register</a>
                <?php elseif ($currentPage === 'register.php'): ?>
                    <a href="index.php" class="nav-item">Home</a>
                    <span class="nav-divider">|</span>
                    <a href="login.php" class="nav-item">Log-in</a>
                <?php else: ?>
                    <a href="index.php" class="nav-item">Home</a>
                    <span class="nav-divider">|</span>
                    <a href="login.php" class="nav-item">Log-in</a>
                <?php endif; ?>
            <?php else: ?>
                <!-- RULE: Log-out is only for logged-in users. Must NEVER appear to visitors -->
                <a href="index.php" class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
                <span class="nav-divider">|</span>
                <a href="appointments.php" class="nav-item <?= $currentPage === 'appointments.php' ? 'active' : '' ?>">Appointments</a>
                <?php if ($userRole === 'admin'): ?>
                    <span class="nav-divider">|</span>
                    <a href="admin_dashboard.php" class="nav-item admin-badge <?= $currentPage === 'admin_dashboard.php' ? 'active' : '' ?>">&#128737; Security Dashboard</a>
                <?php endif; ?>
                <span class="nav-divider">|</span>
                <span class="user-greeting">Hi, <?= e($userName) ?></span>
                <span class="nav-divider">|</span>
                <a href="logout.php" class="nav-item logout-link">Log-out</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">
