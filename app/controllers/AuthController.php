<?php
/**
 * Authentication Controller
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RateLimitMiddleware.php';
require_once __DIR__ . '/../middlewares/CsrfMiddleware.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Register new user
     */
    public function register() {
        try {
            // Verify CSRF token
            CsrfMiddleware::verify();
            
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate input
            $emailValidation = Validator::email($email);
            if (!$emailValidation['valid']) {
                Response::error($emailValidation['message']);
            }
            
            $passwordValidation = Security::validatePassword($password);
            if (!$passwordValidation['valid']) {
                Response::error($passwordValidation['message']);
            }
            
            if ($password !== $confirmPassword) {
                Response::error('Passwords do not match');
            }
            
            // Check if email already exists
            if ($this->userModel->findByEmail($email)) {
                Response::error('Email already registered');
            }
            
            // Create user
            $passwordHash = Security::hashPassword($password);
            $userId = $this->userModel->create($email, $passwordHash, 'user');
            
            // Auto-login
            AuthMiddleware::login($userId, $email, 'user');
            
            Response::success('Registration successful', ['redirect' => '/']);
            
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            Response::error('Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Login user
     */
    public function login() {
        try {
            // Verify CSRF token
            CsrfMiddleware::verify();
            
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Rate limiting
            RateLimitMiddleware::checkLogin($email);
            
            // Validate input
            if (empty($email) || empty($password)) {
                Response::error('Email and password are required');
            }
            
            // Find user
            $user = $this->userModel->findByEmail($email);
            
            if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
                Response::error('Invalid email or password');
            }
            
            // Check if user is active
            if (!$user['is_active']) {
                Response::error('Account is disabled');
            }
            
            // Login
            AuthMiddleware::login($user['id'], $user['email'], $user['role']);
            
            // Reset rate limit on successful login
            RateLimitMiddleware::reset($email, 'login');
            
            $redirect = $user['role'] === 'admin' ? '/admin' : '/';
            
            Response::success('Login successful', ['redirect' => $redirect]);
            
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            Response::error('Login failed: ' . $e->getMessage());
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        try {
            AuthMiddleware::logout();
            Response::success('Logged out successfully', ['redirect' => '/login.php']);
            
        } catch (Exception $e) {
            error_log('Logout error: ' . $e->getMessage());
            Response::error('Logout failed');
        }
    }

    /**
     * Get current user info
     */
    public function getCurrentUser() {
        try {
            AuthMiddleware::require();
            
            $userId = AuthMiddleware::getUserId();
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                Response::error('User not found', null, 404);
            }
            
            // Remove sensitive data
            unset($user['password_hash']);
            
            Response::success('User info retrieved', $user);
            
        } catch (Exception $e) {
            error_log('Get user error: ' . $e->getMessage());
            Response::error('Failed to get user info');
        }
    }
}
