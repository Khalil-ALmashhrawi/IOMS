document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signupForm');

    // Toggle Password Visibility
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    });

    const inputs = {
        staff_id: document.getElementById('staff_id'),
        fullname: document.getElementById('fullname'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        password: document.getElementById('password'),
        confirm_password: document.getElementById('confirm_password'),
        terms: document.getElementById('terms')
    };

    // API Base URL
    const API_BASE = 'http://localhost/BTEC%20CODE/backend/api';

    signupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        let isValid = true;
        clearErrors();

        // 1. Validate Staff ID
        if (!inputs.staff_id.value.trim()) {
            showError(inputs.staff_id, 'Staff ID is required');
            isValid = false;
        }

        // 2. Validate Full Name
        if (!inputs.fullname.value.trim()) {
            showError(inputs.fullname, 'Full Name is required');
            isValid = false;
        }

        // 3. Validate Email
        if (!inputs.email.value.trim()) {
            showError(inputs.email, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(inputs.email.value)) {
            showError(inputs.email, 'Please enter a valid email');
            isValid = false;
        }

        // 4. Validate Password
        if (!inputs.password.value) {
            showError(inputs.password, 'Password is required');
            isValid = false;
        } else if (inputs.password.value.length < 6) {
            showError(inputs.password, 'Password must be at least 6 characters');
            isValid = false;
        }

        // 5. Validate Confirm Password
        if (inputs.confirm_password.value !== inputs.password.value) {
            showError(inputs.confirm_password, 'Passwords do not match');
            isValid = false;
        }

        // 6. Validate Terms
        if (!inputs.terms.checked) {
            alert('You must accept the Terms & Privacy Policy to continue.');
            isValid = false;
        }

        if (!isValid) return;

        // Show loading state
        const submitBtn = signupForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        submitBtn.innerText = 'Creating Account...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(`${API_BASE}/auth/signup.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    staff_id: inputs.staff_id.value.trim(),
                    fullname: inputs.fullname.value.trim(),
                    email: inputs.email.value.trim(),
                    phone: inputs.phone.value.trim(), // Optional but sending it
                    password: inputs.password.value
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('Account created successfully! Redirecting to login...');
                window.location.href = '../login/login.html';
            } else {
                // Show generalized error alert since we don't have dedicated error spans below inputs in new design
                alert(data.message || 'Signup failed. Please try again.');
            }
        } catch (error) {
            console.error('Signup error:', error);
            alert('Connection error. Please try again.');
        } finally {
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        }
    });

    // Helper functions
    function showError(input, message) {
        // Since new design is compact, we'll use simple red border and browser validation style or just alert
        input.style.borderColor = 'red';
        // Optionally create a temporary tooltip
        // For now, focusing the invalid input is good
        input.focus();
    }

    function clearErrors() {
        document.querySelectorAll('input').forEach(input => input.style.borderColor = '');
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
