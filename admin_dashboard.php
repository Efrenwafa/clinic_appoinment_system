<?php
/**
 * Security Monitoring Dashboard (Admin Only & Read-Only)
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 13 & Protections 2, S5, S6, S11
 */

$pageTitle = 'Security Monitoring Dashboard';
$extraCss = 'css/dashboard.css';
require_once __DIR__ . '/php/header.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/security.php';
require_once __DIR__ . '/php/logger.php';

prevent_browser_cache();

// Handout Section 13.1: Authorization check
// A normal user who types the dashboard URL sees "Access denied", and attempt is logged as ADMIN_ACCESS_DENIED
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    log_activity('ADMIN_ACCESS_DENIED', $_SESSION['username'] ?? null, $_SESSION['user_id'] ?? null, 'Unauthorized attempt to open security dashboard.');
    http_response_code(403);
    echo "
    <div class='card' style='max-width: 500px; margin: 40px auto; text-align: center;'>
        <h2 style='color: var(--danger);'>&#9888; Access Denied</h2>
        <p style='color: var(--text-muted); margin: 12px 0 20px 0;'>You do not have administrative privileges to view the security monitoring dashboard.</p>
        <p class='field-hint'>This unauthorized access attempt has been recorded in the security audit logs.</p>
        <a href='index.php' class='btn btn-primary' style='margin-top: 18px;'>Return to Home</a>
    </div>";
    require_once __DIR__ . '/php/footer.php';
    exit;
}

// Handout Section 13.1: Log viewing the dashboard
log_activity('ADMIN_VIEWED_DASHBOARD', $_SESSION['username'], $_SESSION['user_id'], 'Administrator accessed security monitoring dashboard.');

$pdo = get_db_connection();
$today = date('Y-m-d');
$todayStart = $today . ' 00:00:00';
$todayEnd = $today . ' 23:59:59';

// 1. Summary Cards
$succLogins = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'LOGIN_SUCCESS' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$failLogins = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'LOGIN_FAILED' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$lockedNow = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `locked_until` > NOW()")->fetchColumn();
$blockedNow = (int)$pdo->query("SELECT COUNT(*) FROM `ip_blocks` WHERE `blocked_until` > NOW()")->fetchColumn();
$otpsSent = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'OTP_SENT' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$otpsFailed = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'OTP_FAILED' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();
$alertsSent = (int)$pdo->query("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'ALERT_EMAIL_SENT' AND `created_at` BETWEEN '{$todayStart}' AND '{$todayEnd}'")->fetchColumn();

// 2. Failed logins per hour (last 24 hours)
$hourlyData = [];
$maxFailPerHour = 1;
for ($i = 23; $i >= 0; $i--) {
    $targetTime = strtotime("-{$i} hours");
    $hourLabel = date('H:00', $targetTime);
    $startH = date('Y-m-d H:00:00', $targetTime);
    $endH = date('Y-m-d H:59:59', $targetTime);

    $hStmt = $pdo->prepare("SELECT COUNT(*) FROM `activity_logs` WHERE `event_type` = 'LOGIN_FAILED' AND `created_at` BETWEEN :s AND :e");
    $hStmt->execute([':s' => $startH, ':e' => $endH]);
    $c = (int)$hStmt->fetchColumn();
    if ($c > $maxFailPerHour) $maxFailPerHour = $c;
    $hourlyData[] = ['hour' => $hourLabel, 'count' => $c];
}

// 3. Top 5 IPs (last 24 hours)
$twentyFourHoursAgo = date('Y-m-d H:i:s', time() - 86400);
$topIpsStmt = $pdo->prepare("
    SELECT l.ip_address, COUNT(*) as fail_count,
           CASE WHEN b.blocked_until > NOW() THEN 1 ELSE 0 END as is_blocked
    FROM `activity_logs` l
    LEFT JOIN `ip_blocks` b ON l.ip_address = b.ip_address
    WHERE l.event_type = 'LOGIN_FAILED' AND l.created_at >= :since
    GROUP BY l.ip_address
    ORDER BY fail_count DESC
    LIMIT 5
");
$topIpsStmt->execute([':since' => $twentyFourHoursAgo]);
$topIps = $topIpsStmt->fetchAll();

// 4. Flags from Monitoring Script (Section 14)
$flagsStmt = $pdo->query("SELECT * FROM `security_flags` ORDER BY `id` DESC LIMIT 10");
$flags = $flagsStmt->fetchAll();

// 5. Recent Events with Filters
$filterDate = trim($_GET['date_filter'] ?? '');
$filterType = trim($_GET['event_type'] ?? '');
$filterUser = trim($_GET['username'] ?? '');
$filterIp = trim($_GET['ip_address'] ?? '');

$sql = "SELECT * FROM `activity_logs` WHERE 1=1";
$params = [];

if ($filterDate !== '') {
    $sql .= " AND DATE(`created_at`) = :dfilter";
    $params[':dfilter'] = $filterDate;
}
if ($filterType !== '') {
    $sql .= " AND `event_type` = :tfilter";
    $params[':tfilter'] = $filterType;
}
if ($filterUser !== '') {
    $sql .= " AND (`username_typed` LIKE :ufilter OR `user_id` LIKE :ufilter)";
    $params[':ufilter'] = "%{$filterUser}%";
}
if ($filterIp !== '') {
    $sql .= " AND `ip_address` LIKE :ipfilter";
    $params[':ipfilter'] = "%{$filterIp}%";
}

$sql .= " ORDER BY `id` DESC LIMIT 50";
$recentStmt = $pdo->prepare($sql);
$recentStmt->execute($params);
$recentEvents = $recentStmt->fetchAll();

// Distinct event types for dropdown
$typesStmt = $pdo->query("SELECT DISTINCT `event_type` FROM `activity_logs` ORDER BY `event_type` ASC");
$allEventTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- DASHBOARD TOP HEADER -->
<div class="dashboard-header-bar">
    <div>
        <h1 class="dashboard-title">&#128737; Security Monitoring Dashboard</h1>
        <p class="field-hint" style="margin-top: 2px;">Real-time attack detection and audit trail analysis</p>
    </div>
    <!-- Handout Section 13.3: Read-only badge and 60s auto refresh indicator -->
    <div class="readonly-pill">
        READ-ONLY &middot; logs cannot be edited or deleted &middot; auto-refresh 60 s
    </div>
</div>

<!-- 1. TODAY'S SUMMARY CARDS (Section 13.2) -->
<div class="summary-cards-grid">
    <div class="stat-card success">
        <div class="stat-label">Successful Log-ins</div>
        <div class="stat-value" id="card-succ-logins"><?= $succLogins ?></div>
    </div>
    <div class="stat-card <?= $failLogins > 0 ? 'danger' : '' ?>">
        <div class="stat-label">Failed Log-ins</div>
        <div class="stat-value" id="card-fail-logins"><?= $failLogins ?></div>
    </div>
    <div class="stat-card <?= $lockedNow > 0 ? 'warning' : '' ?>">
        <div class="stat-label">Accounts Locked Now</div>
        <div class="stat-value" id="card-locked-now"><?= $lockedNow ?></div>
    </div>
    <div class="stat-card <?= $blockedNow > 0 ? 'danger' : '' ?>">
        <div class="stat-label">IPs Blocked Now</div>
        <div class="stat-value" id="card-blocked-now"><?= $blockedNow ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">OTPs Sent / Failed</div>
        <div class="stat-value" id="card-otps"><?= $otpsSent ?> / <?= $otpsFailed ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Alert Emails Sent</div>
        <div class="stat-value" id="card-alerts"><?= $alertsSent ?></div>
    </div>
</div>

<!-- 2 & 3. MIDDLE SECTION: BAR CHART & TOP IPS / FLAGS -->
<div class="dashboard-middle-grid">
    <!-- FAILED LOG-INS PER HOUR (LAST 24 HOURS) -->
    <div class="chart-container">
        <h3 class="card-title" style="font-size: 16px; margin-bottom: 4px;">Failed log-ins per hour &mdash; last 24 hours</h3>
        <p class="field-hint" style="margin-bottom: 12px;">Visualizes brute-force spike patterns across 24-hour window</p>

        <div class="bar-chart-visual">
            <?php foreach ($hourlyData as $h): ?>
                <?php
                $pct = ($maxFailPerHour > 0) ? min(100, max(4, round(($h['count'] / $maxFailPerHour) * 100))) : 4;
                ?>
                <div class="chart-bar-col" title="<?= e($h['hour']) ?>: <?= (int)$h['count'] ?> failures">
                    <div class="bar-fill <?= $h['count'] > 0 ? 'has-fails' : '' ?>" style="height: <?= $pct ?>%;"></div>
                    <span class="bar-label"><?= (substr($h['hour'], 0, 2) % 4 === 0) ? e(substr($h['hour'], 0, 2)) : '' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-top: 6px;">
            <span>24h Ago</span>
            <span>Current Hour</span>
        </div>
    </div>

    <!-- TOP IPS & MONITORING SCRIPT FLAGS -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <!-- Top IP addresses (24 h) -->
        <div class="card" style="padding: 18px; margin-bottom: 0;">
            <h3 class="card-title" style="font-size: 15px; margin-bottom: 8px;">Top IP addresses by failures (24 h)</h3>
            <?php if (empty($topIps)): ?>
                <p class="field-hint">No failed login IP addresses recorded in the last 24 hours.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); text-align: left;">
                            <th style="padding: 4px 0;">IP Address</th>
                            <th style="padding: 4px 8px;">Failures</th>
                            <th style="padding: 4px 0; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topIps as $ipRow): ?>
                            <tr style="border-bottom: 1px solid #f2f4f4;">
                                <td style="padding: 6px 0; font-family: Consolas, monospace;"><?= e($ipRow['ip_address']) ?></td>
                                <td style="padding: 6px 8px;"><?= (int)$ipRow['fail_count'] ?></td>
                                <td style="padding: 6px 0; text-align: right;">
                                    <?php if ($ipRow['is_blocked']): ?>
                                        <span class="badge badge-blocked">BLOCKED</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Flags from the monitoring script (Section 14) -->
        <div class="card" style="padding: 18px; margin-bottom: 0;">
            <h3 class="card-title" style="font-size: 15px; margin-bottom: 8px;">Flags from the monitoring script</h3>
            <?php if (empty($flags)): ?>
                <p class="field-hint">No security flags written yet today. Run <code>php scripts/monitor.php</code> from terminal.</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; font-size: 12px; line-height: 1.6;">
                    <?php foreach ($flags as $f): ?>
                        <li style="margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #f2f4f4;">
                            <strong style="color: <?= $f['severity'] === 'HIGH' ? 'var(--danger)' : '#b7950b' ?>;">
                                [<?= e($f['severity']) ?>] <?= e($f['pattern_type']) ?>
                            </strong>
                            <span style="font-family: Consolas, monospace; color: var(--secondary); font-weight: 600;"> &middot; <?= e($f['target']) ?></span><br>
                            <span style="color: var(--text-muted);"><?= e($f['details']) ?> (<?= e($f['flagged_date']) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 5. RECENT EVENTS (LATEST 50) WITH FILTERS (Section 13.2) -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
        <h3 class="card-title" style="font-size: 17px; margin-bottom: 0;">Recent Security Events (Latest 50)</h3>
        <span class="field-hint">Auto-refreshes every 60 seconds</span>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="admin_dashboard.php" style="background: #f8f9fa; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
            <div>
                <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Date</label>
                <input type="date" name="date_filter" value="<?= e($filterDate) ?>" class="form-control" style="padding: 6px 8px; font-size: 12px; width: 140px;">
            </div>
            <div>
                <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Event Type</label>
                <select name="event_type" class="form-control" style="padding: 6px 8px; font-size: 12px; width: 170px;">
                    <option value="">All Events</option>
                    <?php foreach ($allEventTypes as $t): ?>
                        <option value="<?= e($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Username / ID</label>
                <input type="text" name="username" value="<?= e($filterUser) ?>" placeholder="Search username" class="form-control" style="padding: 6px 8px; font-size: 12px; width: 140px;">
            </div>
            <div>
                <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">IP Address</label>
                <input type="text" name="ip_address" value="<?= e($filterIp) ?>" placeholder="Search IP" class="form-control" style="padding: 6px 8px; font-size: 12px; width: 130px;">
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-sm" style="padding: 7px 14px;">Apply Filter</button>
                <a href="admin_dashboard.php" class="btn btn-secondary btn-sm" style="padding: 7px 10px;">Clear</a>
            </div>
        </div>
    </form>

    <!-- Audit Log Table -->
    <div class="table-responsive">
        <table class="data-table" style="font-size: 12px;">
            <thead>
                <tr>
                    <th style="width: 14%;">Time (PHT)</th>
                    <th style="width: 16%;">Event</th>
                    <th style="width: 16%;">Username / Target</th>
                    <th style="width: 14%;">IP Address</th>
                    <th style="width: 20%;">Browser / Device</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody id="recent-events-tbody">
                <?php if (empty($recentEvents)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 18px;">
                            No matching security events found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentEvents as $ev): ?>
                        <tr>
                            <td><?= e($ev['created_at']) ?></td>
                            <td>
                                <span class="event-badge event-<?= e($ev['event_type']) ?>">
                                    <?= e($ev['event_type']) ?>
                                </span>
                            </td>
                            <!-- Protection 2 & Section 13.3 Rule: Escape every value from attacker (XSS Defense) -->
                            <td style="font-weight: 600;">
                                <?= e($ev['username_typed'] ?? ($ev['user_id'] ?? '—')) ?>
                            </td>
                            <td style="font-family: Consolas, monospace;"><?= e($ev['ip_address']) ?></td>
                            <td style="color: var(--text-muted); font-size: 11px;" title="<?= e($ev['user_agent']) ?>">
                                <?= e(substr($ev['user_agent'], 0, 30)) ?><?= strlen($ev['user_agent']) > 30 ? '...' : '' ?>
                            </td>
                            <td style="color: #555;"><?= e($ev['details'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- AUTO-REFRESH SCRIPT (Handout Section 13.3: Auto-refresh every 60s via JSON endpoint) -->
<script>
setInterval(function () {
    fetch('admin_api.php')
        .then(res => {
            if (!res.ok) throw new Error('Unauthenticated');
            return res.json();
        })
        .then(data => {
            // Update cards
            if (data.cards) {
                document.getElementById('card-succ-logins').textContent = data.cards.successful_logins;
                document.getElementById('card-fail-logins').textContent = data.cards.failed_logins;
                document.getElementById('card-locked-now').textContent = data.cards.accounts_locked;
                document.getElementById('card-blocked-now').textContent = data.cards.ips_blocked;
                document.getElementById('card-otps').textContent = data.cards.otps_sent + ' / ' + data.cards.otps_failed;
                document.getElementById('card-alerts').textContent = data.cards.alerts_sent;
            }
        })
        .catch(err => {
            // Ignore if logged out or transient error
        });
}, 60000);
</script>

<?php require_once __DIR__ . '/php/footer.php'; ?>
