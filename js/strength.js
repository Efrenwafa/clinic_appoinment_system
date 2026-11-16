/**
 * Live Password Strength Meter (Section 4.9)
 * IT 107 - Information Assurance and Security 1
 * Universal minimum: 8 characters. Weak passwords cannot be submitted.
 */

document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const submitBtn = document.getElementById('submit-btn');
    const confirmInput = document.getElementById('confirm_password');
    const confirmError = document.getElementById('error-confirm_password');

    if (!passwordInput || !strengthBar || !strengthText) return;

    function evaluateStrength(pwd) {
        if (!pwd || pwd.length < 8) {
            return { label: 'Weak', class: 'weak' };
        }

        let score = 0;
        if (pwd.length >= 12) score++;
        if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^a-zA-Z0-9]/.test(pwd)) score++;

        if (score >= 3) {
            return { label: 'Strong', class: 'strong' };
        } else if (score >= 1) {
            return { label: 'Medium', class: 'medium' };
        } else {
            return { label: 'Weak', class: 'weak' };
        }
    }

    passwordInput.addEventListener('input', function () {
        const val = this.value;
        const res = evaluateStrength(val);

        strengthBar.className = 'strength-bar ' + res.class;
        strengthText.className = 'strength-text ' + res.class;
        strengthText.textContent = res.label;

        // Weak cannot be submitted (Handout 4.9)
        if (submitBtn) {
            if (res.class === 'weak' && val.length > 0) {
                submitBtn.disabled = true;
            } else if (!document.body.classList.contains('locked-out')) {
                submitBtn.disabled = false;
            }
        }

        checkMatch();
    });

    function checkMatch() {
        if (!confirmInput || !confirmError) return;
        if (confirmInput.value.length > 0 && confirmInput.value !== passwordInput.value) {
            confirmError.textContent = 'Passwords do not match.';
        } else {
            confirmError.textContent = '';
        }
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', checkMatch);
    }
});
