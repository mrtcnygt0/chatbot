// Authentication JavaScript

class AuthApp {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.init();
    }

    init() {
        this.attachEventListeners();
    }

    attachEventListeners() {
        // Login form
        document.getElementById('loginForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        // Register form
        document.getElementById('registerForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });

        // Password toggle
        document.querySelectorAll('.password-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => this.togglePasswordVisibility(btn));
        });
    }

    async handleLogin() {
        const form = document.getElementById('loginForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        const email = form.querySelector('[name="email"]').value;
        const password = form.querySelector('[name="password"]').value;

        if (!email || !password) {
            this.showError('Please fill in all fields');
            return;
        }

        this.setButtonLoading(submitBtn, true);

        try {
            const response = await fetch('/api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Login successful! Redirecting...');
                setTimeout(() => {
                    window.location.href = data.data.redirect;
                }, 1000);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Login failed. Please try again.');
        } finally {
            this.setButtonLoading(submitBtn, false);
        }
    }

    async handleRegister() {
        const form = document.getElementById('registerForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        const email = form.querySelector('[name="email"]').value;
        const password = form.querySelector('[name="password"]').value;
        const confirmPassword = form.querySelector('[name="confirm_password"]').value;

        if (!email || !password || !confirmPassword) {
            this.showError('Please fill in all fields');
            return;
        }

        if (password !== confirmPassword) {
            this.showError('Passwords do not match');
            return;
        }

        if (password.length < 8) {
            this.showError('Password must be at least 8 characters');
            return;
        }

        this.setButtonLoading(submitBtn, true);

        try {
            const response = await fetch('/api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ email, password, confirm_password: confirmPassword })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Registration successful! Redirecting...');
                setTimeout(() => {
                    window.location.href = data.data.redirect;
                }, 1000);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Registration failed. Please try again.');
        } finally {
            this.setButtonLoading(submitBtn, false);
        }
    }

    togglePasswordVisibility(button) {
        const input = button.closest('.password-toggle').querySelector('input');
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        button.textContent = type === 'password' ? '👁️' : '🙈';
    }

    setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.innerHTML = '<div class="loading"></div>';
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText;
        }
    }

    showError(message) {
        this.showAlert(message, 'error');
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showAlert(message, type) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        const form = document.querySelector('.auth-form');
        form.insertBefore(alert, form.firstChild);

        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AuthApp();
});
