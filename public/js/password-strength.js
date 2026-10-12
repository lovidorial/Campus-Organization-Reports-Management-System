document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('secretary_password');
    const confirmationInput = document.getElementById('secretary_password_confirmation');
    const strengthContainer = document.getElementById('secretary-password-strength');
    const strengthFill = document.getElementById('secretary-password-strength-fill');
    const strengthLabel = document.getElementById('secretary-password-strength-label');
    const requirements = Array.from(document.querySelectorAll('[data-secretary-password-rule]'));
    const matchStatus = document.getElementById('secretary-password-match-status');

    if (!passwordInput || !confirmationInput || !strengthContainer || !strengthFill || !strengthLabel || !matchStatus) {
        return;
    }

    const ruleChecks = {
        length: (value) => value.length >= 8,
        uppercase: (value) => /[A-Z]/.test(value),
        lowercase: (value) => /[a-z]/.test(value),
        number: (value) => /\d/.test(value),
        symbol: (value) => /[^A-Za-z0-9]/.test(value),
    };

    const updateStrength = () => {
        const value = passwordInput.value;
        strengthContainer.hidden = value.length === 0;

        if (value.length === 0) {
            strengthFill.style.width = '0%';
            strengthFill.style.backgroundColor = '#cbd5e1';
            strengthLabel.textContent = 'Password strength:';
            return;
        }

        let score = 0;
        if (value.length >= 8) score += 1;
        if (value.length >= 12) score += 1;
        if (/[a-z]/.test(value)) score += 1;
        if (/[A-Z]/.test(value)) score += 1;
        if (/\d/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;

        let level = 'Weak';
        let width = 25;
        let color = '#ef4444';
        if (score >= 5) {
            level = 'Strong';
            width = 100;
            color = '#22c55e';
        } else if (score >= 3) {
            level = 'Normal';
            width = 60;
            color = '#f59e0b';
        }

        strengthFill.style.width = width + '%';
        strengthFill.style.backgroundColor = color;
        strengthLabel.textContent = 'Password strength: ' + level;
    };

    const updateRequirements = () => {
        requirements.forEach((item) => {
            const check = ruleChecks[item.dataset.secretaryPasswordRule];
            const isMet = typeof check === 'function' && check(passwordInput.value);
            item.classList.toggle('is-met', isMet);
        });
    };

    const updateMatch = () => {
        const password = passwordInput.value;
        const confirmation = confirmationInput.value;
        if (password.length === 0 && confirmation.length === 0) {
            matchStatus.textContent = 'Passwords match: No';
            matchStatus.classList.remove('is-match', 'is-mismatch');
            return;
        }

        const isMatch = password.length > 0 && password === confirmation;
        matchStatus.textContent = 'Passwords match: ' + (isMatch ? 'Yes' : 'No');
        matchStatus.classList.toggle('is-match', isMatch);
        matchStatus.classList.toggle('is-mismatch', !isMatch);
    };

    passwordInput.addEventListener('input', () => {
        updateStrength();
        updateRequirements();
        updateMatch();
    });
    confirmationInput.addEventListener('input', updateMatch);

    updateStrength();
    updateRequirements();
    updateMatch();
});
