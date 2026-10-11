document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.login-form');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    const submitButton = document.getElementById('reset-password-button');
    const strengthContainer = document.getElementById('password-strength');
    const strengthFill = document.getElementById('password-strength-fill');
    const strengthLabel = document.getElementById('password-strength-label');
    const requirements = Array.from(document.querySelectorAll('.password-requirement'));
    const matchStatus = document.getElementById('password-match-status');
    const toggleButtons = document.querySelectorAll('.password-toggle');

    if (!form || !passwordInput || !confirmPasswordInput || !submitButton || !strengthContainer || !strengthFill || !strengthLabel || !matchStatus) {
        return;
    }

    const requirementMap = {
        length: (value) => value.length >= 8,
        uppercase: (value) => /[A-Z]/.test(value),
        lowercase: (value) => /[a-z]/.test(value),
        number: (value) => /\d/.test(value),
        symbol: (value) => /[^A-Za-z0-9]/.test(value),
    };

    const setButtonState = (isValid) => {
        submitButton.disabled = !isValid;
    };

    const updateStrengthMeter = (value) => {
        const isEmpty = value.length === 0;

        strengthContainer.hidden = isEmpty;

        if (isEmpty) {
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

        let label = 'Weak';
        let width = 25;
        let color = '#ef4444';

        if (score >= 5) {
            label = 'Strong';
            width = 100;
            color = '#22c55e';
        } else if (score >= 3) {
            label = 'Normal';
            width = 60;
            color = '#f59e0b';
        }

        strengthFill.style.width = width + '%';
        strengthFill.style.backgroundColor = color;
        strengthLabel.textContent = 'Password strength: ' + label;
    };

    const updateChecklist = (value) => {
        requirements.forEach((item) => {
            const rule = item.dataset.rule;
            const ruleCheck = requirementMap[rule];
            const isMet = typeof ruleCheck === 'function' ? ruleCheck(value) : false;
            item.classList.toggle('is-met', isMet);
            item.setAttribute('aria-checked', String(isMet));
        });
    };

    const updateMatchState = () => {
        const password = passwordInput.value;
        const confirmation = confirmPasswordInput.value;
        const shouldShow = confirmation.length > 0 || password.length > 0;

        if (!shouldShow) {
            matchStatus.textContent = 'Passwords match: No';
            matchStatus.classList.remove('is-match', 'is-mismatch');
            return;
        }

        const isMatch = password.length > 0 && password === confirmation;
        matchStatus.textContent = 'Passwords match: ' + (isMatch ? 'Yes' : 'No');
        matchStatus.classList.toggle('is-match', isMatch);
        matchStatus.classList.toggle('is-mismatch', !isMatch);
    };

    const evaluateForm = () => {
        const password = passwordInput.value;
        const confirmation = confirmPasswordInput.value;

        const isValid =
            password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /\d/.test(password) &&
            password === confirmation &&
            confirmation.length > 0;

        setButtonState(isValid);
    };

    const togglePasswordVisibility = (button, input) => {
        const shouldShow = input.type === 'password';
        input.type = shouldShow ? 'text' : 'password';
        button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        button.title = shouldShow ? 'Hide password' : 'Show password';
        button.innerHTML = shouldShow
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.58 10.58A2 2 0 0013.42 13.42"/><path d="M9.88 5.08A10.94 10.94 0 0112 5c4.42 0 8.17 2.48 10 7-1.1 2.6-2.92 4.7-5.16 6.1"/><path d="M14.12 18.92A10.94 10.94 0 0112 19c-4.42 0-8.17-2.48-10-7 1.24-2.86 3.43-5.08 6.24-6.2"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    };

    toggleButtons.forEach((button) => {
        const targetInput = document.getElementById(button.dataset.togglePassword);

        if (!targetInput) {
            return;
        }

        button.addEventListener('click', () => togglePasswordVisibility(button, targetInput));
    });

    passwordInput.addEventListener('input', () => {
        const value = passwordInput.value;
        updateStrengthMeter(value);
        updateChecklist(value);
        updateMatchState();
        evaluateForm();
    });

    confirmPasswordInput.addEventListener('input', () => {
        updateMatchState();
        evaluateForm();
    });

    form.addEventListener('submit', (event) => {
        if (submitButton.disabled) {
            event.preventDefault();
            return;
        }

        submitButton.classList.add('is-loading');
        submitButton.disabled = true;
        submitButton.querySelector('.button-text').textContent = 'Resetting...';
    });

    updateStrengthMeter(passwordInput.value);
    updateChecklist(passwordInput.value);
    updateMatchState();
    evaluateForm();
});
