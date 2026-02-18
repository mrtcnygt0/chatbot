<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <?php
    require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
    require_once __DIR__ . '/../app/middlewares/CsrfMiddleware.php';
    require_once __DIR__ . '/../config/database.php';
    
    AuthMiddleware::requireAdmin();
    $user = AuthMiddleware::getUser();
    
    echo CsrfMiddleware::metaTag();
    
    // Load settings
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM system_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <div class="admin-logo">🤖 AI Chat Admin</div>
            </div>

            <nav class="admin-nav">
                <a href="/admin" class="admin-nav-item">
                    📊 Dashboard
                </a>
                <a href="/admin/users.php" class="admin-nav-item">
                    👥 Users
                </a>
                <a href="/admin/logs.php" class="admin-nav-item">
                    📜 API Logs
                </a>
                <a href="/admin/settings.php" class="admin-nav-item active">
                    ⚙️ Settings
                </a>
                <a href="/" class="admin-nav-item">
                    💬 Back to Chat
                </a>
            </nav>

            <div style="padding: 16px; border-top: 1px solid var(--border-color);">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['email'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-header-title">System Settings</h1>
                <div class="chat-actions">
                    <button class="btn-icon" id="mobileMenuBtn">☰</button>
                </div>
            </header>

            <div class="admin-content">
                <div class="settings-section">
                    <h3 class="settings-section-title">API Configuration</h3>
                    
                    <?php foreach ($settings as $setting): ?>
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $setting['setting_key']))); ?></div>
                            <div class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></div>
                        </div>
                        
                        <?php if ($setting['setting_key'] === 'api_enabled'): ?>
                            <label class="toggle-switch">
                                <input 
                                    type="checkbox" 
                                    <?php echo $setting['setting_value'] === '1' ? 'checked' : ''; ?>
                                    onchange="adminApp.toggleApiStatus(this.checked)"
                                >
                                <span class="toggle-slider"></span>
                            </label>
                        <?php else: ?>
                            <input 
                                type="text" 
                                class="input-field" 
                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                style="max-width: 200px;"
                                onchange="updateSetting('<?php echo htmlspecialchars($setting['setting_key']); ?>', this.value)"
                            >
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="settings-section">
                    <h3 class="settings-section-title">System Information</h3>
                    
                    <div class="card">
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 8px;"><strong>PHP Version:</strong></td>
                                <td style="padding: 8px;"><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px;"><strong>MySQL Version:</strong></td>
                                <td style="padding: 8px;">
                                    <?php
                                    $version = $db->query('SELECT VERSION()')->fetchColumn();
                                    echo htmlspecialchars($version);
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 8px;"><strong>Server:</strong></td>
                                <td style="padding: 8px;"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px;"><strong>Max Upload Size:</strong></td>
                                <td style="padding: 8px;"><?php echo ini_get('upload_max_filesize'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px;"><strong>Memory Limit:</strong></td>
                                <td style="padding: 8px;"><?php echo ini_get('memory_limit'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="settings-section">
                    <h3 class="settings-section-title">Danger Zone</h3>
                    
                    <div class="card" style="border-color: var(--error);">
                        <p style="margin-bottom: 16px; color: var(--text-secondary);">
                            These actions are permanent and cannot be undone. Please be careful.
                        </p>
                        
                        <button class="btn btn-danger" onclick="if(confirm('Clear all API logs? This cannot be undone.')) clearLogs()">
                            🗑️ Clear All API Logs
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/admin.js"></script>
    <script>
        async function updateSetting(key, value) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const response = await fetch('/admin/api/update-setting.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ key, value })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    adminApp.showSuccess('Setting updated successfully');
                } else {
                    adminApp.showError(data.message);
                }
            } catch (error) {
                adminApp.showError('Failed to update setting');
            }
        }

        async function clearLogs() {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const response = await fetch('/admin/api/clear-logs.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    adminApp.showSuccess('Logs cleared successfully');
                } else {
                    adminApp.showError(data.message);
                }
            } catch (error) {
                adminApp.showError('Failed to clear logs');
            }
        }
    </script>
</body>
</html>
