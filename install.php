<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat - Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .installer-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .installer-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .installer-logo {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        h1 {
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #666;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .progress {
            display: none;
            margin-top: 20px;
        }
        
        .progress-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
        }
        
        .progress-text {
            text-align: center;
            margin-top: 12px;
            color: #666;
            font-size: 14px;
        }

        .requirements {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .requirements h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #333;
        }

        .requirements ul {
            list-style: none;
            padding: 0;
        }

        .requirements li {
            padding: 6px 0;
            font-size: 14px;
            color: #666;
        }

        .requirements li::before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <div class="installer-logo">🤖</div>
            <h1>AI Chat Installation</h1>
            <p class="subtitle">Complete ChatGPT-like application setup</p>
        </div>

        <?php
        // Check if already installed
        if (file_exists(__DIR__ . '/config/config.php')) {
            echo '<div class="alert alert-info">Already installed! <a href="/">Go to Chat</a></div>';
            exit;
        }

        $error = '';
        $success = '';
        $installed = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Get form data
                $dbHost = trim($_POST['db_host'] ?? 'localhost');
                $dbPort = trim($_POST['db_port'] ?? '3306');
                $dbName = trim($_POST['db_name'] ?? '');
                $dbUser = trim($_POST['db_user'] ?? '');
                $dbPass = $_POST['db_pass'] ?? '';
                $adminEmail = trim($_POST['admin_email'] ?? '');
                $adminPassword = $_POST['admin_password'] ?? '';
                $openaiKey = trim($_POST['openai_key'] ?? '');

                // Validate
                if (empty($dbName) || empty($dbUser) || empty($adminEmail) || empty($adminPassword)) {
                    throw new Exception('All fields are required');
                }

                if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email address');
                }

                if (strlen($adminPassword) < 8) {
                    throw new Exception('Password must be at least 8 characters');
                }

                // Test database connection
                $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                // Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");

                // Run migration
                $sql = file_get_contents(__DIR__ . '/database/migrate.sql');
                $pdo->exec($sql);

                // Create admin user
                $passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, quota_tokens, daily_limit, monthly_limit) VALUES (?, ?, 'admin', 1000000, 100000, 1000000)");
                $stmt->execute([$adminEmail, $passwordHash]);

                // Create config file
                $configContent = "<?php\n\nreturn [\n";
                $configContent .= "    'app' => [\n";
                $configContent .= "        'name' => 'AI Chat',\n";
                $configContent .= "        'url' => 'http://localhost',\n";
                $configContent .= "        'environment' => 'production',\n";
                $configContent .= "        'debug' => false,\n";
                $configContent .= "        'timezone' => 'UTC',\n";
                $configContent .= "    ],\n\n";
                $configContent .= "    'database' => [\n";
                $configContent .= "        'host' => '{$dbHost}',\n";
                $configContent .= "        'port' => {$dbPort},\n";
                $configContent .= "        'name' => '{$dbName}',\n";
                $configContent .= "        'username' => '{$dbUser}',\n";
                $configContent .= "        'password' => '{$dbPass}',\n";
                $configContent .= "        'charset' => 'utf8mb4',\n";
                $configContent .= "        'collation' => 'utf8mb4_unicode_ci',\n";
                $configContent .= "    ],\n\n";
                $configContent .= "    'openai' => [\n";
                $configContent .= "        'api_key' => '{$openaiKey}',\n";
                $configContent .= "        'model' => 'gpt-4o-mini',\n";
                $configContent .= "        'max_tokens' => 4000,\n";
                $configContent .= "        'temperature' => 0.7,\n";
                $configContent .= "        'timeout' => 30,\n";
                $configContent .= "    ],\n\n";
                $configContent .= "    'security' => [\n";
                $configContent .= "        'session_lifetime' => 7200,\n";
                $configContent .= "        'csrf_token_name' => '_csrf_token',\n";
                $configContent .= "        'password_min_length' => 8,\n";
                $configContent .= "        'max_login_attempts' => 5,\n";
                $configContent .= "        'lockout_duration' => 900,\n";
                $configContent .= "    ],\n\n";
                $configContent .= "    'rate_limit' => [\n";
                $configContent .= "        'chat_requests_per_minute' => 10,\n";
                $configContent .= "        'api_requests_per_hour' => 100,\n";
                $configContent .= "    ],\n\n";
                $configContent .= "    'budget' => [\n";
                $configContent .= "        'default_user_quota' => 100000,\n";
                $configContent .= "        'default_daily_limit' => 10000,\n";
                $configContent .= "        'default_monthly_limit' => 100000,\n";
                $configContent .= "        'gpt4o_mini_input_cost' => 0.00000015,\n";
                $configContent .= "        'gpt4o_mini_output_cost' => 0.0000006,\n";
                $configContent .= "    ],\n";
                $configContent .= "];\n";

                file_put_contents(__DIR__ . '/config/config.php', $configContent);

                $success = 'Installation completed successfully!';
                $installed = true;

            } catch (Exception $e) {
                $error = 'Installation failed: ' . $e->getMessage();
            }
        }
        ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <br><br>
                <strong>Admin Credentials:</strong><br>
                Email: <?php echo htmlspecialchars($_POST['admin_email']); ?><br>
                <br>
                <a href="/login.php" style="color: inherit; font-weight: bold;">→ Go to Login Page</a>
            </div>
        <?php else: ?>
            <div class="requirements">
                <h3>Requirements</h3>
                <ul>
                    <li>PHP 8.1 or higher</li>
                    <li>MySQL 8.0 or higher</li>
                    <li>PDO PHP Extension</li>
                    <li>OpenAI API Key</li>
                </ul>
            </div>

            <form method="POST" id="installForm">
                <h3 style="margin-bottom: 16px; color: #333;">Database Configuration</h3>
                
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>

                <div class="form-group">
                    <label for="db_port">Database Port</label>
                    <input type="text" id="db_port" name="db_port" value="3306" required>
                </div>

                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" placeholder="chatbot_db" required>
                </div>

                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" placeholder="root" required>
                </div>

                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" placeholder="Leave empty if no password">
                </div>

                <h3 style="margin: 32px 0 16px; color: #333;">Administrator Account</h3>

                <div class="form-group">
                    <label for="admin_email">Admin Email</label>
                    <input type="email" id="admin_email" name="admin_email" placeholder="admin@example.com" required>
                </div>

                <div class="form-group">
                    <label for="admin_password">Admin Password</label>
                    <input type="password" id="admin_password" name="admin_password" placeholder="Minimum 8 characters" required>
                </div>

                <h3 style="margin: 32px 0 16px; color: #333;">OpenAI Configuration</h3>

                <div class="form-group">
                    <label for="openai_key">OpenAI API Key</label>
                    <input type="text" id="openai_key" name="openai_key" placeholder="sk-..." required>
                </div>

                <button type="submit" class="btn" id="installBtn">
                    Install AI Chat
                </button>

                <div class="progress" id="progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">Installing...</div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('installForm')?.addEventListener('submit', function() {
            document.getElementById('installBtn').disabled = true;
            document.getElementById('installBtn').textContent = 'Installing...';
            document.getElementById('progress').style.display = 'block';
            
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                if (progress >= 90) {
                    clearInterval(interval);
                }
                document.getElementById('progressFill').style.width = progress + '%';
            }, 200);
        });
    </script>
</body>
</html>
