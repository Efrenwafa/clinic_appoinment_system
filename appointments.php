<?php
/**
 * Clinic Appointment Request & Monitoring Portal
 * IT 107 - Information Assurance and Security 1
 * Implements System Core Functionality & Protection 13 (Decryption of sensitive contact field)
 */

$pageTitle = 'Clinic Appointments';
require_once __DIR__ . '/php/header.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/logger.php';

// Protection 6 & Handout 8.4: Session check & cache prevention
prevent_browser_cache();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'patient';
$pdo = get_db_connection();

// Fetch current user details
$userStmt = $pdo->prepare("SELECT * FROM `users` WHERE `id_number` = :id");
$userStmt->execute([':id' => $userId]);
$user = $userStmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Protection 13: Decrypt contact number at rest ONLY for display to its owner
$decryptedContact = decrypt_sensitive_field($user['contact_number_encrypted'] ?? '');

$successMessage = '';
$errorMessage = '';

// Handle Appointment Booking Submission (Patient)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid security token. Please refresh.';
    } else {
        $dept = trim($_POST['department'] ?? '');
        $date = trim($_POST['appointment_date'] ?? '');
        $time = trim($_POST['appointment_time'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        $allowedDepts = ['General Medicine', 'Pediatrics', 'Dental', 'Dermatology', 'Cardiology'];
        if (!in_array($dept, $allowedDepts, true)) {
            $errorMessage = 'Select a valid clinic department.';
        } elseif (empty($date) || empty($time)) {
            $errorMessage = 'Please select a valid appointment date and time.';
        } elseif (strtotime($date) < strtotime('today')) {
            $errorMessage = 'Appointment date cannot be in the past.';
        } elseif (empty($reason)) {
            $errorMessage = 'Please enter the reason for your clinic consultation.';
        } else {
            $now = date('Y-m-d H:i:s');
            $insStmt = $pdo->prepare("
                INSERT INTO `appointments` (`patient_id`, `department`, `appointment_date`, `appointment_time`, `reason`, `status`, `created_at`, `updated_at`)
                VALUES (:pid, :dept, :adate, :atime, :reason, 'Pending', :created, :updated)
            ");
            $insStmt->execute([
                ':pid'     => $userId,
                ':dept'    => $dept,
                ':adate'   => $date,
                ':atime'   => $time,
                ':reason'  => $reason,
                ':created' => $now,
                ':updated' => $now
            ]);

            log_activity('APPOINTMENT_REQUESTED', $user['username'], $userId, "Requested appointment for department {$dept} on {$date}.");
            $successMessage = 'Your appointment request has been scheduled successfully! Status: Pending.';
        }
    }
}

// Handle Status Updates (Admin or Patient cancellation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid security token.';
    } else {
        $apptId = (int)($_POST['appointment_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowedStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            $errorMessage = 'Invalid status selected.';
        } else {
            if ($userRole === 'admin') {
                $updStmt = $pdo->prepare("UPDATE `appointments` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id");
                $updStmt->execute([':st' => $newStatus, ':id' => $apptId]);
                log_activity('APPOINTMENT_UPDATED', $user['username'], $userId, "Admin updated appointment #{$apptId} to {$newStatus}.");
                $successMessage = "Appointment #{$apptId} status updated to {$newStatus}.";
            } else {
                // Patient: Can only cancel THEIR OWN appointment (Protection against horizontal privilege escalation)
                $cancelStmt = $pdo->prepare("UPDATE `appointments` SET `status` = 'Cancelled', `updated_at` = NOW() WHERE `id` = :id AND `patient_id` = :pid AND `status` = 'Pending'");
                $cancelStmt->execute([':id' => $apptId, ':pid' => $userId]);

                if ($cancelStmt->rowCount() > 0) {
                    log_activity('APPOINTMENT_CANCELLED', $user['username'], $userId, "Patient cancelled appointment #{$apptId}.");
                    $successMessage = "Appointment #{$apptId} has been cancelled.";
                } else {
                    $errorMessage = 'Unable to cancel this appointment. It may already be confirmed or does not belong to you.';
                }
            }
        }
    }
}

// Retrieve Appointments
if ($userRole === 'admin') {
    $listStmt = $pdo->query("
        SELECT a.*, u.first_name, u.family_name, u.email, u.contact_number_encrypted
        FROM `appointments` a
        JOIN `users` u ON a.patient_id = u.id_number
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $appointments = $listStmt->fetchAll();
} else {
    // Patient: Strictly fetch only records belonging to the authenticated user ID
    $listStmt = $pdo->prepare("
        SELECT * FROM `appointments`
        WHERE `patient_id` = :pid
        ORDER BY `appointment_date` DESC, `appointment_time` DESC
    ");
    $listStmt->execute([':pid' => $userId]);
    $appointments = $listStmt->fetchAll();
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; border-bottom: 2px solid var(--primary-light); padding-bottom: 12px; margin-bottom: 20px;">
        <div>
            <h1 class="card-title" style="margin-bottom: 4px;">
                <?= $userRole === 'admin' ? '&#128197; Clinic Appointments Management' : '&#128197; My Clinic Appointments' ?>
            </h1>
            <p class="field-hint" style="margin-top: 0;">
                Patient: <strong><?= e($user['first_name'] . ' ' . $user['family_name']) ?></strong> (ID: <?= e($user['id_number']) ?>) &middot;
                Contact: <strong><?= e($decryptedContact) ?></strong> (AES-256 Decrypted)
            </p>
        </div>
        <?php if ($userRole !== 'admin'): ?>
            <a href="#book-form" class="btn btn-primary btn-sm">&#43; Request New Appointment</a>
        <?php endif; ?>
    </div>

    <?php if ($successMessage): ?>
        <div class="alert alert-success">&#10003; <?= e($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger">&#9888; <?= e($errorMessage) ?></div>
    <?php endif; ?>

    <!-- APPOINTMENTS LIST TABLE -->
    <h3 style="color: var(--secondary); font-size: 16px; margin: 18px 0 10px 0;">Scheduled Consultations</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <?php if ($userRole === 'admin'): ?>
                        <th>Patient</th>
                        <th>Contact</th>
                    <?php endif; ?>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason for Visit</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="<?= $userRole === 'admin' ? '8' : '6' ?>" style="text-align: center; color: var(--text-muted); padding: 24px;">
                            No clinic appointment records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td><?= (int)$apt['id'] ?></td>
                            <?php if ($userRole === 'admin'): ?>
                                <td>
                                    <strong><?= e($apt['first_name'] . ' ' . $apt['family_name']) ?></strong><br>
                                    <span style="font-size: 11px; color: var(--text-muted);"><?= e($apt['patient_id']) ?></span>
                                </td>
                                <td><?= e(decrypt_sensitive_field($apt['contact_number_encrypted'] ?? '')) ?></td>
                            <?php endif; ?>
                            <td><strong><?= e($apt['department']) ?></strong></td>
                            <td><?= e($apt['appointment_date']) ?></td>
                            <td><?= date('h:i A', strtotime($apt['appointment_time'])) ?></td>
                            <td><?= e($apt['reason']) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-pending';
                                if ($apt['status'] === 'Confirmed') $badgeClass = 'badge-confirmed';
                                elseif ($apt['status'] === 'Completed') $badgeClass = 'badge-completed';
                                elseif ($apt['status'] === 'Cancelled') $badgeClass = 'badge-cancelled';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= e($apt['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($userRole === 'admin'): ?>
                                    <form method="POST" action="appointments.php" style="display: flex; gap: 6px; align-items: center;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                                        <select name="status" class="form-control" style="padding: 4px 8px; font-size: 12px; max-width: 115px;">
                                            <option value="Pending" <?= $apt['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $apt['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="Completed" <?= $apt['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $apt['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;">Update</button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($apt['status'] === 'Pending'): ?>
                                        <form method="POST" action="appointments.php" onsubmit="return confirm('Are you sure you want to cancel this appointment request?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$apt['id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: var(--danger); padding: 4px 8px; font-size: 11px;">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--text-muted);">&mdash;</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($userRole !== 'admin'): ?>
<!-- BOOKING FORM SECTION FOR PATIENTS -->
<div class="card" id="book-form" style="max-width: 700px; margin: 30px auto;">
    <h2 class="card-title">&#43; Request a Clinic Consultation</h2>
    <p class="card-subtitle">Select your preferred medical department and consultation schedule.</p>

    <form method="POST" action="appointments.php">
        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="book_appointment">

        <div class="form-row">
            <div class="form-group">
                <label for="department" class="form-label">Clinic Department<span class="required-star">*</span></label>
                <select id="department" name="department" class="form-control" required>
                    <option value="">-- Choose Department --</option>
                    <option value="General Medicine" <?= ($user['preferred_department'] === 'General Medicine') ? 'selected' : '' ?>>General Medicine</option>
                    <option value="Pediatrics" <?= ($user['preferred_department'] === 'Pediatrics') ? 'selected' : '' ?>>Pediatrics</option>
                    <option value="Dental" <?= ($user['preferred_department'] === 'Dental') ? 'selected' : '' ?>>Dental</option>
                    <option value="Dermatology" <?= ($user['preferred_department'] === 'Dermatology') ? 'selected' : '' ?>>Dermatology</option>
                    <option value="Cardiology" <?= ($user['preferred_department'] === 'Cardiology') ? 'selected' : '' ?>>Cardiology</option>
                </select>
            </div>

            <div class="form-group">
                <label for="appointment_date" class="form-label">Preferred Date<span class="required-star">*</span></label>
                <input type="date" id="appointment_date" name="appointment_date" class="form-control"
                       min="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="appointment_time" class="form-label">Preferred Time Slot<span class="required-star">*</span></label>
                <select id="appointment_time" name="appointment_time" class="form-control" required>
                    <option value="">-- Choose Time --</option>
                    <option value="08:30:00">08:30 AM</option>
                    <option value="09:30:00">09:30 AM</option>
                    <option value="10:30:00">10:30 AM</option>
                    <option value="13:30:00">01:30 PM</option>
                    <option value="14:30:00">02:30 PM</option>
                    <option value="15:30:00">03:30 PM</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Contact Number (AES-256 Encrypted at Rest)</label>
                <input type="text" class="form-control" value="<?= e($decryptedContact) ?>" readonly>
                <span class="field-hint">Your encrypted number will be used by attending medical staff.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="reason" class="form-label">Reason for Visit / Chief Complaint<span class="required-star">*</span></label>
            <textarea id="reason" name="reason" rows="3" class="form-control" placeholder="Describe symptoms or reasons for clinic consultation..." required></textarea>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary" style="padding: 12px 28px;">
                Submit Appointment Request &#8594;
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/php/footer.php'; ?>
