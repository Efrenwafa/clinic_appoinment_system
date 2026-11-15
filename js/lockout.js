/**
 * Progressive Lockout Countdown & UI Enforcer
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 8.3
 */

document.addEventListener('DOMContentLoaded', function () {
    const countdownEl = document.getElementById('lockout-countdown');
    const loginBtn = document.getElementById('login-btn');
    const registerLink = document.getElementById('register-link');
    const lockoutBox = document.getElementById('lockout-box');

    if (!countdownEl) return;

    let seconds = parseInt(countdownEl.textContent, 10);
    if (isNaN(seconds) || seconds <= 0) return;

    // Mark body and disable elements
    document.body.classList.add('locked-out');
    if (loginBtn) loginBtn.disabled = true;
    if (registerLink) {
        registerLink.style.pointerEvents = 'none';
        registerLink.style.opacity = '0.5';
    }

    const timer = setInterval(function () {
        seconds--;
        if (seconds <= 0) {
            clearInterval(timer);
            countdownEl.textContent = '0';
            if (lockoutBox) {
                lockoutBox.innerHTML = '<p style="color: #27ae60; font-weight: bold;">Lockout period has expired. You may attempt to sign in again.</p>';
            }
            if (loginBtn) loginBtn.disabled = false;
            if (registerLink) {
                registerLink.style.pointerEvents = 'auto';
                registerLink.style.opacity = '1';
            }
            document.body.classList.remove('locked-out');
        } else {
            countdownEl.textContent = seconds;
        }
    }, 1000);
});
