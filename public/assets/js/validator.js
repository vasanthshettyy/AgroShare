/**
 * validator.js — Shared validation logic for AgroShare forms.
 */
class Validator {
    static isNameValid(name) {
        // Allows letters, spaces, hyphens, and apostrophes. Min 2 chars.
        return /^[A-Za-z\s\-']{2,120}$/.test(name.trim());
    }

    static isPhoneValid(phone) {
        // Indian 10-digit mobile number format
        return /^[6-9]\d{9}$/.test(phone.trim());
    }

    static isEmailValid(email) {
        // Basic email pattern
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
    }

    static validateForm(form) {
        let isValid = true;
        
        // Remove existing error messages added by JS
        form.querySelectorAll('.js-val-error').forEach(el => el.remove());
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        const showError = (input, message) => {
            isValid = false;
            input.classList.add('is-invalid');
            const err = document.createElement('span');
            err.className = 'error-msg js-val-error';
            err.style.color = 'var(--danger)';
            err.style.fontSize = '0.75rem';
            err.style.marginTop = '4px';
            err.style.display = 'block';
            err.textContent = message;
            
            // Append after the input wrapper if exists, else after input
            if (input.parentElement.classList.contains('input-wrap')) {
                input.parentElement.insertAdjacentElement('afterend', err);
            } else {
                input.insertAdjacentElement('afterend', err);
            }
        };

        // Name validation
        form.querySelectorAll('input[name="full_name"], input[name="name"]').forEach(input => {
            if (input.required || input.value.trim() !== '') {
                if (!this.isNameValid(input.value)) {
                    showError(input, 'Please enter a valid name (letters only).');
                }
            }
        });

        // Phone validation
        form.querySelectorAll('input[type="tel"], input[name="phone"]').forEach(input => {
            if (input.required || input.value.trim() !== '') {
                if (!this.isPhoneValid(input.value)) {
                    showError(input, 'Please enter a valid 10-digit Indian phone number.');
                }
            }
        });

        // Email validation
        form.querySelectorAll('input[type="email"], input[name="email"]').forEach(input => {
            if (input.required || input.value.trim() !== '') {
                if (!this.isEmailValid(input.value)) {
                    showError(input, 'Please enter a valid email address.');
                }
            }
        });

        return isValid;
    }

    static init() {
        document.addEventListener('submit', (e) => {
            // Only validate forms that have a specific class or we can just validate all
            // To be safe, validate forms that don't opt-out with 'no-js-val'
            if (!e.target.classList.contains('no-js-val')) {
                if (!this.validateForm(e.target)) {
                    e.preventDefault();
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    Validator.init();
});
