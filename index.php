<?php
/**
 * Application Home & Portal Page
 * IT 107 - Information Assurance and Security 1
 * Secure Clinic Appointment and Monitoring System
 */

$pageTitle = 'Home';
require_once __DIR__ . '/php/header.php';
?>

<div class="card" style="margin-top: 10px;">
    <div style="text-align: center; padding: 20px 0;">
        <span style="font-size: 48px; color: var(--primary);">&#9877;</span>
        <h1 style="color: var(--primary); font-size: 28px; margin: 12px 0 6px 0;">Caraga State University Health Services</h1>
        <p style="color: var(--text-muted); font-size: 16px; max-width: 650px; margin: 0 auto;">
            Official portal for secure patient consultations, clinic appointment booking, and real-time security monitoring.
        </p>
    </div>

    <?php if (!$isLoggedIn): ?>
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 24px;">
            <a href="register.php" class="btn btn-primary" style="padding: 14px 28px; font-size: 15px;">&#128100; Register as New Patient</a>
            <a href="login.php" class="btn btn-accent" style="padding: 14px 28px; font-size: 15px;">&#128274; Patient & Staff Log-in</a>
        </div>
    <?php else: ?>
        <div style="background-color: var(--primary-light); border-radius: 6px; padding: 20px; margin-top: 20px; text-align: center;">
            <h3 style="color: var(--primary-dark); margin-bottom: 8px;">Welcome back, <?= e($_SESSION['user_name'] ?? 'Patient') ?>!</h3>
            <p style="color: var(--secondary); margin-bottom: 18px;">
                You are currently signed in as <strong><?= e(ucfirst($_SESSION['user_role'] ?? 'patient')) ?></strong> (ID: <?= e($_SESSION['user_id'] ?? '') ?>).
            </p>
            <div style="display: flex; gap: 14px; justify-content: center;">
                <a href="appointments.php" class="btn btn-primary">&#128197; Go to Appointments</a>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <a href="admin_dashboard.php" class="btn btn-accent">&#128737; Open Security Monitoring Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">Log-out</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="form-row-3" style="margin-top: 24px;">
    <div class="card">
        <h3 class="card-title" style="font-size: 17px;">&#127973; Clinic Departments</h3>
        <p class="field-hint" style="margin-bottom: 12px;">Preconfigured medical departments ready for consultation:</p>
        <ul style="margin-left: 20px; font-size: 13.5px; color: var(--secondary); line-height: 1.8;">
            <li><strong>General Medicine</strong> &middot; Routine checkups</li>
            <li><strong>Pediatrics</strong> &middot; Child wellness</li>
            <li><strong>Dental Care</strong> &middot; Oral examinations</li>
            <li><strong>Dermatology</strong> &middot; Skin evaluations</li>
            <li><strong>Cardiology</strong> &middot; Cardiovascular screening</li>
        </ul>
    </div>

    <div class="card">
        <h3 class="card-title" style="font-size: 17px;">&#128274; Security Assurance</h3>
        <p class="field-hint" style="margin-bottom: 12px;">Protected by 13 IAS defense layers:</p>
        <ul style="margin-left: 20px; font-size: 13.5px; color: var(--secondary); line-height: 1.8;">
            <li>AES-256 encrypted contact numbers</li>
            <li>Two-step authentication (Password + OTP)</li>
            <li>Server-enforced progressive lockout</li>
            <li>Automated threat monitoring script</li>
            <li>Data Privacy Act of 2012 compliance</li>
        </ul>
    </div>

    <div class="card">
        <h3 class="card-title" style="font-size: 17px;">&#9200; Consultation Hours</h3>
        <p class="field-hint" style="margin-bottom: 12px;">University Clinic Schedule:</p>
        <p style="font-size: 13.5px; color: var(--secondary); margin-bottom: 8px;">
            <strong>Monday &ndash; Friday:</strong><br>
            8:00 AM &ndash; 12:00 PM &nbsp;|&nbsp; 1:00 PM &ndash; 5:00 PM
        </p>
        <p style="font-size: 12px; color: var(--text-muted); margin-top: 14px;">
            <em>Note: Off-hours logins (12:00 AM &ndash; 5:00 AM) are flagged by automated security monitoring.</em>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/php/footer.php'; ?>
