/**
 * Idle Session Timeout Manager (Handout Section 8.4)
 * IT 107 - Information Assurance and Security 1
 * Seeded idle timeout: 9 minutes (540 seconds). Warns 1 minute before (at 480 seconds).
 */

(function () {
    const IDLE_LIMIT_SEC = 9 * 60; // 540 seconds
    const WARN_AT_SEC = 8 * 60;    // 480 seconds (1 minute before timeout)

    let secondsIdle = 0;
    let warningActive = false;
    let countdownInterval = null;

    const modal = document.getElementById('idle-warning-modal');
    const countdownEl = document.getElementById('idle-countdown');
    const stayBtn = document.getElementById('stay-logged-in-btn');

    function resetActivity() {
        if (!warningActive) {
            secondsIdle = 0;
        }
    }

    // Monitor user interaction
    ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'].forEach(evt => {
        window.addEventListener(evt, resetActivity, { passive: true });
    });

    // Tick every second
    setInterval(function () {
        secondsIdle++;

        if (secondsIdle >= WARN_AT_SEC && !warningActive) {
            // Show warning modal
            warningActive = true;
            if (modal) modal.style.display = 'flex';
            let remaining = IDLE_LIMIT_SEC - secondsIdle;
            if (countdownEl) countdownEl.textContent = remaining;

            countdownInterval = setInterval(function () {
                remaining--;
                if (countdownEl) countdownEl.textContent = Math.max(0, remaining);
                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = 'logout.php?msg=idle_timeout';
                }
            }, 1000);
        }

        if (secondsIdle >= IDLE_LIMIT_SEC) {
            window.location.href = 'logout.php?msg=idle_timeout';
        }
    }, 1000);

    if (stayBtn) {
        stayBtn.addEventListener('click', function () {
            // Ping server to update $_SESSION['last_activity']
            fetch('api_keep_alive.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        secondsIdle = 0;
                        warningActive = false;
                        if (countdownInterval) clearInterval(countdownInterval);
                        if (modal) modal.style.display = 'none';
                    }
                })
                .catch(() => {
                    secondsIdle = 0;
                    warningActive = false;
                    if (countdownInterval) clearInterval(countdownInterval);
                    if (modal) modal.style.display = 'none';
                });
        });
    }
})();
