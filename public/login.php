<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AI Chat</title>
    <?php
    require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
    require_once __DIR__ . '/../app/middlewares/CsrfMiddleware.php';
    
    AuthMiddleware::initSession();
    
    // Redirect if already logged in
    if (AuthMiddleware::check()) {
        header('Location: /');
        exit;
    }
    
    echo CsrfMiddleware::metaTag();
    ?>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">🤖</div>
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Sign in to continue to AI Chat</p>
                </div>

                <form id="loginForm" class="auth-form">
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
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle-btn">👁️</button>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Sign In
                    </button>
                </form>

                <div class="auth-link">
                    Don't have an account? <a href="/register.php">Sign up</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/auth.js"></script>
</body>
</html>
