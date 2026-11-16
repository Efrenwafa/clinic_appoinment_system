/**
 * Client-Side Usability Validation
 * IT 107 - Information Assurance and Security 1
 * Mirroring Section 4 rules for UI usability.
 * (Note: Server strictly re-validates everything independently under Protection S3)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Birthdate to Age Live Calculation (Section 4.6)
    const birthdateInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('age');

    if (birthdateInput && ageInput) {
        birthdateInput.addEventListener('change', function () {
            const val = this.value;
            const errorSpan = document.getElementById('error-birthdate');
            if (!val) {
                ageInput.value = '';
                return;
            }

            const birth = new Date(val);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }

            if (birth > today) {
                ageInput.value = '0';
                if (errorSpan) errorSpan.textContent = 'Birthdate cannot be in the future.';
            } else if (age < 18) {
                ageInput.value = age;
                if (errorSpan) errorSpan.textContent = 'You must be at least 18 years old to register.';
            } else {
                ageInput.value = age;
                if (errorSpan) errorSpan.textContent = '';
            }
        });
    }

    // 2. ID Number Live Formatting (xxxx-xxxx) (Section 4.4)
    const idInput = document.getElementById('id_number');
    if (idInput) {
        idInput.addEventListener('input', function () {
            let v = this.value.replace(/[^\d]/g, '');
            if (v.length > 4) {
                v = v.substring(0, 4) + '-' + v.substring(4, 8);
            }
            this.value = v;

            const errorSpan = document.getElementById('error-id_number');
            if (errorSpan) {
                if (v.length > 0 && !/^\d{4}-\d{4}$/.test(v)) {
                    errorSpan.textContent = 'ID Number must follow the format xxxx-xxxx.';
                } else {
                    errorSpan.textContent = '';
                }
            }
        });
    }

    // 3. Username Live Availability Check (Section 4.9)
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        let debounceTimer;
        usernameInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            const statusSpan = document.getElementById('error-username');
            if (!statusSpan) return;

            if (val.length < 6 || val.length > 20) {
                statusSpan.textContent = 'Username must be between 6 and 20 characters.';
                statusSpan.style.color = '#c0392b';
                return;
            }

            if (!/^[a-zA-Z]/.test(val)) {
                statusSpan.textContent = 'Username must start with a letter.';
                statusSpan.style.color = '#c0392b';
                return;
            }

            if (!/^[a-zA-Z0-9]+$/.test(val)) {
                statusSpan.textContent = 'Username must contain letters and numbers only.';
                statusSpan.style.color = '#c0392b';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch('api_check_username.php?username=' + encodeURIComponent(val))
                    .then(res => res.json())
                    .then(data => {
                        if (data.taken) {
                            statusSpan.textContent = 'This username is already taken.';
                            statusSpan.style.color = '#c0392b';
                        } else {
                            statusSpan.textContent = 'Username is available!';
                            statusSpan.style.color = '#27ae60';
                        }
                    })
                    .catch(() => {});
            }, 300);
        });
    }

    // 4. Name Extension "Other" field handler (Section 4.5)
    const extSelect = document.getElementById('name_extension_select');
    const extOtherInput = document.getElementById('name_extension_other');
    if (extSelect && extOtherInput) {
        extSelect.addEventListener('change', function () {
            if (this.value === 'Other') {
                extOtherInput.style.display = 'block';
                extOtherInput.focus();
            } else {
                extOtherInput.style.display = 'none';
                extOtherInput.value = '';
            }
        });
    }

    // 5. Contact Number live format (09XXXXXXXXX)
    const contactInput = document.getElementById('contact_number');
    if (contactInput) {
        contactInput.addEventListener('input', function () {
            const errorSpan = document.getElementById('error-contact_number');
            if (!errorSpan) return;
            const val = this.value.trim();
            if (val.length > 0 && !/^09\d{9}$/.test(val)) {
                errorSpan.textContent = 'Enter a valid contact number in the format 09XXXXXXXXX.';
            } else {
                errorSpan.textContent = '';
            }
        });
    }
});
