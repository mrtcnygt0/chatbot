<!DOCTYPE html>
<html>
<head>
    <title>Path Detection Test</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
        }
        h1 { color: #6366f1; }
        .info { 
            background: #333;
            padding: 10px;
            margin: 10px 0;
            border-left: 3px solid #6366f1;
        }
        .success { border-left-color: #10b981; }
        .warning { border-left-color: #f59e0b; }
        .error { border-left-color: #ef4444; }
        code {
            background: #1a1a1a;
            padding: 2px 6px;
            border-radius: 3px;
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Path Detection Test</h1>
        
        <?php
        // Include the Url helper
        if (file_exists(__DIR__ . '/app/helpers/Url.php')) {
            require_once __DIR__ . '/app/helpers/Url.php';
            
            echo '<div class="info success">';
            echo '<strong>✓ Url Helper Found</strong><br>';
            echo 'Location: <code>app/helpers/Url.php</code>';
            echo '</div>';
            
            // Test base path detection
            try {
                $basePath = Url::getBasePath();
                
                echo '<div class="info success">';
                echo '<strong>✓ Base Path Detected</strong><br>';
                echo 'Detected base path: <code>' . htmlspecialchars($basePath ?: '/') . '</code>';
                echo '</div>';
                
                // Show example URLs
                echo '<div class="info">';
                echo '<strong>Example URLs:</strong><br>';
                echo 'Home: <code>' . htmlspecialchars(Url::to('')) . '</code><br>';
                echo 'Login: <code>' . htmlspecialchars(Url::to('login.php')) . '</code><br>';
                echo 'Assets: <code>' . htmlspecialchars(Url::asset('css/main.css')) . '</code><br>';
                echo 'API: <code>' . htmlspecialchars(Url::api('chat.php')) . '</code><br>';
                echo 'Admin: <code>' . htmlspecialchars(Url::admin('')) . '</code>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="info error">';
                echo '<strong>✗ Error Getting Base Path</strong><br>';
                echo 'Error: <code>' . htmlspecialchars($e->getMessage()) . '</code>';
                echo '</div>';
            }
            
        } else {
            echo '<div class="info error">';
            echo '<strong>✗ Url Helper Not Found</strong><br>';
            echo 'Expected location: <code>app/helpers/Url.php</code>';
            echo '</div>';
        }
        ?>
        
        <div class="info">
            <strong>Server Information:</strong><br>
            SCRIPT_NAME: <code><?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A'); ?></code><br>
            SCRIPT_FILENAME: <code><?php echo htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'N/A'); ?></code><br>
            REQUEST_URI: <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A'); ?></code><br>
            DOCUMENT_ROOT: <code><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A'); ?></code><br>
            PHP_SELF: <code><?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? 'N/A'); ?></code>
        </div>
        
        <?php
        // Check if config exists
        if (file_exists(__DIR__ . '/config/config.php')) {
            require_once __DIR__ . '/config/database.php';
            
            echo '<div class="info success">';
            echo '<strong>✓ Configuration Found</strong><br>';
            
            try {
                $db = Database::getInstance();
                $config = $db->getConfig('app.base_path');
                
                echo 'Configured base_path: <code>' . htmlspecialchars($config ?: '(empty - root)') . '</code>';
                
            } catch (Exception $e) {
                echo 'Config exists but could not load: <code>' . htmlspecialchars($e->getMessage()) . '</code>';
            }
            
            echo '</div>';
            
        } else {
            echo '<div class="info warning">';
            echo '<strong>⚠ Configuration Not Found</strong><br>';
            echo 'Please run the installer: <code>' . htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'])) . '/install.php</code>';
            echo '</div>';
        }
        ?>
        
        <div class="info">
            <strong>🎯 Next Steps:</strong><br>
            <?php if (!file_exists(__DIR__ . '/config/config.php')): ?>
                1. <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/install.php" style="color: #10b981;">Run the installer</a><br>
            <?php else: ?>
                1. Configuration is set up<br>
            <?php endif; ?>
            2. Delete this file (<code>test-path.php</code>) for security<br>
            3. Visit your application: <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/" style="color: #10b981;">Go to App</a>
        </div>
        
        <div class="info warning">
            <strong>⚠ Security Notice</strong><br>
            Delete this file after testing! It reveals system information.
        </div>
    </div>
</body>
</html>
