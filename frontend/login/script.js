document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const staffIdInput = document.getElementById('staff_id');
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.querySelector('.toggle-password');

    // API Base URL - Using 127.0.0.1 to avoid localhost DNS resolution issues
    const API_BASE = 'http://127.0.0.1/BTEC%20CODE/backend/api';

    // Toggle Password Visibility
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePasswordBtn.classList.toggle('visible');
        });
    }

    // Form Validation & Submission
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        let isValid = true;

        // Clear previous errors
        clearErrors();

        // Validate Staff ID
        if (!staffIdInput.value.trim()) {
            showError(staffIdInput, 'Staff ID is required');
            isValid = false;
        }

        // Validate Password
        if (!passwordInput.value.trim()) {
            showError(passwordInput, 'Password is required');
            isValid = false;
        }

        if (!isValid) return;

        // Show loading state
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        submitBtn.innerText = 'Logging in...';
        submitBtn.disabled = true;

        // Create a timeout controller
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

        try {
            const response = await fetch(`${API_BASE}/auth/login.php`, {
                signal: controller.signal,
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    staff_id: staffIdInput.value.trim(),
                    password: passwordInput.value
                })
            });

            clearTimeout(timeoutId); // Clear timeout if request completes

            const data = await response.json();

            if (data.success) {
                // Store user info in localStorage
                localStorage.setItem('user', JSON.stringify(data.data.user));
                // Redirect to dashboard
                window.location.href = '../dashboard/dashboard.html';
            } else {
                showError(passwordInput, data.message || 'Invalid credentials');
            }
        } catch (error) {
            console.error('Login error:', error);
            if (error.name === 'AbortError') {
                showError(passwordInput, 'Request timed out. Please check your server connection.');
            } else {
                showError(passwordInput, 'Connection error. Please try again.');
            }
        } finally {
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        }
    });

    // Real-time validation
    [staffIdInput, passwordInput].forEach(input => {
        input.addEventListener('input', () => {
            if (input.classList.contains('error')) {
                clearError(input);
            }
        });
    });

    function showError(input, message) {
        const errorSpan = document.getElementById(`${input.id}_error`);
        input.classList.add('error');
        if (errorSpan) {
            errorSpan.innerText = message;
        }
    }

    function clearError(input) {
        const errorSpan = document.getElementById(`${input.id}_error`);
        input.classList.remove('error');
        if (errorSpan) {
            errorSpan.innerText = '';
        }
    }

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(span => span.innerText = '');
        document.querySelectorAll('input').forEach(input => input.classList.remove('error'));
    }
});
