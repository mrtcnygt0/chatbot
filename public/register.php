<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AI Chat</title>
    <?php
    require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
    require_once __DIR__ . '/../app/middlewares/CsrfMiddleware.php';
    require_once __DIR__ . '/../app/helpers/Url.php';
    
    AuthMiddleware::initSession();
    
    // Redirect if already logged in
    if (AuthMiddleware::check()) {
        Url::redirect('');
        exit;
    }
    
    echo CsrfMiddleware::metaTag();
    ?>
    <link rel="stylesheet" href="<?php echo Url::asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo Url::asset('css/auth.css'); ?>">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">🤖</div>
                    <h1 class="auth-title">Create Account</h1>
                    <p class="auth-subtitle">Start your AI journey today</p>
                </div>

                <form id="registerForm" class="auth-form">
                    <div class="input-group">
                        <label class="input-label" for="email">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="input-field" 
                            placeholder="Enter your email"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <div class="input-group password-toggle">
                        <label class="input-label" for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="input-field" 
                            placeholder="Create a password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle-btn">👁️</button>
                    </div>

                    <div class="input-group password-toggle">
                        <label class="input-label" for="confirm_password">Confirm Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="input-field" 
                            placeholder="Confirm your password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle-btn">👁️</button>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Create Account
                    </button>
                </form>

                <div class="auth-link">
                    Already have an account? <a href="<?php echo Url::to('login.php'); ?>">Sign in</a>
                </div>
            </div>
        </div>
    </div>

    <script>window.BASE_PATH = '<?php echo Url::getBasePath(); ?>';</script>
    <script src="<?php echo Url::asset('js/auth.js'); ?>"></script>
</body>
</html>
