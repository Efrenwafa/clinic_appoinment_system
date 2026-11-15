<?php
/**
 * Privacy Notice under the Philippine Data Privacy Act of 2012 (RA 10173)
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 4.3 & Grading Criterion F2 / P5
 */

$pageTitle = 'Privacy Notice';
require_once __DIR__ . '/php/header.php';
?>

<div class="card">
    <h1 class="card-title" style="font-size: 24px; border-bottom: 2px solid var(--primary); padding-bottom: 8px;">
        Privacy Notice &amp; Consent Statement
    </h1>
    <p class="card-subtitle">In compliance with Republic Act No. 10173 (Data Privacy Act of 2012 of the Philippines)</p>

    <div style="font-size: 14px; line-height: 1.7; color: var(--secondary);">
        <h3 style="color: var(--primary); margin: 18px 0 6px 0;">1. Purpose and Scope</h3>
        <p>The <strong>Secure Clinic Appointment and Monitoring System</strong> collects and processes personal information for the exclusive purpose of scheduling, managing, and facilitating medical consultations at the Caraga State University Clinic.</p>

        <h3 style="color: var(--primary); margin: 18px 0 6px 0;">2. Information We Collect and Why</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Data Category</th>
                    <th style="width: 30%;">Fields Collected</th>
                    <th>Reason for Collection &amp; Protection Applied</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Identity Data</strong></td>
                    <td>ID Number, Full Name, Birthdate, Age</td>
                    <td>Required to establish unique patient identity, verify adult consent (must be &ge; 18 years old), and link clinical consultation records accurately.</td>
                </tr>
                <tr>
                    <td><strong>Address Data</strong></td>
                    <td>Purok/Street, Barangay, City, Province, Country, ZIP</td>
                    <td>Necessary for patient geographical profiling, local health monitoring, and emergency health responses.</td>
                </tr>
                <tr>
                    <td><strong>Contact Information</strong></td>
                    <td>Email Address, Contact Number, Emergency Contact</td>
                    <td>Used for two-step authentication OTP dispatch, consultation updates, and emergency medical notification. <strong>Patient contact numbers are encrypted at rest using AES-256 (Protection 13).</strong></td>
                </tr>
                <tr>
                    <td><strong>Authentication &amp; Security</strong></td>
                    <td>Password, Security Question Answers (Q2, Q5, Q7)</td>
                    <td>Used exclusively to authenticate identity and authorize self-service password recovery. <strong>Stored strictly as one-way cryptographic hashes. Never stored in plaintext.</strong></td>
                </tr>
                <tr>
                    <td><strong>Audit &amp; Access Logs</strong></td>
                    <td>IP Address, Browser User Agent, Timestamps</td>
                    <td>Maintained in an append-only security log to detect credential guessing, automated attacks, and protect clinic record integrity (Section 13.4).</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: var(--primary); margin: 18px 0 6px 0;">3. Security Safeguards Applied</h3>
        <ul style="margin-left: 24px;">
            <li><strong>AES-256 Symmetric Encryption:</strong> Patient contact numbers are encrypted at rest with a securely generated IV.</li>
            <li><strong>Cryptographic Hashing:</strong> Passwords, security answers, and OTP codes are hashed with PHP <code>password_hash()</code>.</li>
            <li><strong>Access Control:</strong> Patients can access only their own appointment records. Administrative pages are restricted by server authorization.</li>
            <li><strong>Automated Monitoring:</strong> The system logs security events and detects brute-force patterns, account targeting, and off-hours activity.</li>
        </ul>

        <h3 style="color: var(--primary); margin: 18px 0 6px 0;">4. Retention Period</h3>
        <p>Personal and medical appointment data are retained for the duration of the student or staff member's affiliation with Caraga State University, in accordance with the National Archives of the Philippines Act and University Health Services guidelines.</p>

        <h3 style="color: var(--primary); margin: 18px 0 6px 0;">5. Data Protection Officer Contact</h3>
        <p>For inquiries regarding your rights under the Data Privacy Act (access, correction, or erasure), contact the Clinic Data Officer at: <a href="mailto:efren.castillano@csucc.edu.ph">efren.castillano@csucc.edu.ph</a>.</p>
    </div>

    <div style="margin-top: 24px; text-align: center;">
        <a href="register.php" class="btn btn-primary">&#8592; Back to Patient Registration</a>
    </div>
</div>

<?php require_once __DIR__ . '/php/footer.php'; ?>
