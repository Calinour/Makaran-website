// Client-side validation scripts for SahanFresh

document.addEventListener('DOMContentLoaded', () => {
    // Validate registration forms
    const registerForm = document.querySelector('form.register-form');
    if (registerForm) {
        const password = registerForm.querySelector('#password');
        const confirmPassword = registerForm.querySelector('#confirm_password');
        
        registerForm.addEventListener('submit', (e) => {
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match. Please verify.');
                confirmPassword.focus();
            }
        });
    }

    // Prevent negative quantities/prices in admin and stock forms
    const numericInputs = document.querySelectorAll('input[type="number"]');
    numericInputs.forEach(input => {
        // Set default min if not exists
        if (!input.hasAttribute('min')) {
            input.setAttribute('min', '0');
        }

        input.addEventListener('change', () => {
            const val = parseFloat(input.value);
            const min = parseFloat(input.getAttribute('min') || '0');
            if (val < min) {
                input.value = min;
            }
        });
    });

    // Validate phone number format (simple Somali style check if relevant, or digits only)
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', () => {
            // allow only plus sign and digits
            input.value = input.value.replace(/[^\d+]/g, '');
        });
    });
});
