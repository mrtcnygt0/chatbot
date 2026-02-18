<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - AI Chat</title>
    <?php
    require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
    require_once __DIR__ . '/../app/middlewares/CsrfMiddleware.php';
    require_once __DIR__ . '/../app/helpers/Url.php';
    
    AuthMiddleware::requireAdmin();
    $user = AuthMiddleware::getUser();
    
    echo CsrfMiddleware::metaTag();
    ?>
    <link rel="stylesheet" href="<?php echo Url::asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo Url::asset('css/admin.css'); ?>">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <div class="admin-logo">🤖 AI Chat Admin</div>
            </div>

            <nav class="admin-nav">
                <a href="<?php echo Url::admin(''); ?>" class="admin-nav-item active">
                    📊 Dashboard
                </a>
                <a href="<?php echo Url::admin('users.php'); ?>" class="admin-nav-item">
                    👥 Users
                </a>
                <a href="<?php echo Url::admin('logs.php'); ?>" class="admin-nav-item">
                    📜 API Logs
                </a>
                <a href="<?php echo Url::admin('settings.php'); ?>" class="admin-nav-item">
                    ⚙️ Settings
                </a>
                <a href="<?php echo Url::to(''); ?>" class="admin-nav-item">
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
                <h1 class="admin-header-title">Dashboard</h1>
                <div class="chat-actions">
                    <button class="btn-icon" id="mobileMenuBtn">☰</button>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Total Users</span>
                            <div class="stat-icon primary">👥</div>
                        </div>
                        <div class="stat-value" id="totalUsers">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Total Cost</span>
                            <div class="stat-icon success">💰</div>
                        </div>
                        <div class="stat-value" id="totalCost">$0.00</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">API Status</span>
                            <div class="stat-icon warning">🔌</div>
                        </div>
                        <div class="setting-item" style="padding: 0; background: none; margin: 0;">
                            <span class="stat-title">Enabled</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="apiToggle" onchange="adminApp.toggleApiStatus(this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Daily Usage Chart -->
                <div class="chart-container">
                    <h3 class="chart-title">Daily Usage (Last 30 Days)</h3>
                    <div id="usageChart">
                        <div style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                            <div class="loading"></div>
                        </div>
                    </div>
                </div>

                <!-- Top Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Top Users by Cost</h3>
                        <div class="table-actions">
                            <a href="<?php echo Url::admin('users.php'); ?>" class="btn btn-secondary btn-sm">View All Users</a>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Requests</th>
                                <th>Total Tokens</th>
                                <th>Total Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="topUsersTable">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                                    <div class="loading"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- User Details Modal -->
    <div class="modal" id="userDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">User Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <label class="input-label">Email</label>
                    <div id="modalUserEmail" style="padding: 12px; background: var(--bg-tertiary); border-radius: 8px;">-</div>
                </div>
                <div class="input-group">
                    <label class="input-label">Quota Tokens</label>
                    <div id="modalUserQuota" style="padding: 12px; background: var(--bg-tertiary); border-radius: 8px;">-</div>
                </div>
                <div class="input-group">
                    <label class="input-label">Tokens Used Today</label>
                    <div id="modalUserUsage" style="padding: 12px; background: var(--bg-tertiary); border-radius: 8px;">-</div>
                </div>
                <div class="input-group">
                    <label class="input-label">Total Cost</label>
                    <div id="modalUserCost" style="padding: 12px; background: var(--bg-tertiary); border-radius: 8px;">-</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Close</button>
            </div>
        </div>
    </div>

    <script>window.BASE_PATH = '<?php echo Url::getBasePath(); ?>';</script>
    <script src="<?php echo Url::asset('js/admin.js'); ?>"></script>
</body>
</html>
