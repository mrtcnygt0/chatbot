<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin Panel</title>
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
                <a href="<?php echo Url::admin(''); ?>" class="admin-nav-item">
                    📊 Dashboard
                </a>
                <a href="<?php echo Url::admin('users.php'); ?>" class="admin-nav-item active">
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
                <h1 class="admin-header-title">User Management</h1>
                <div class="chat-actions">
                    <button class="btn-icon" id="mobileMenuBtn">☰</button>
                </div>
            </header>

            <div class="admin-content">
                <!-- Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">All Users</h3>
                        <div class="table-actions">
                            <button class="btn btn-secondary btn-sm" onclick="adminApp.loadUsers()">🔄 Refresh</button>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Quota</th>
                                <th>Used Today</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                                    <div class="loading"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>window.BASE_PATH = '<?php echo Url::getBasePath(); ?>';</script>
    <script src="<?php echo Url::asset('js/admin.js'); ?>"></script>
    <script>
        // Load users when page loads
        document.addEventListener('DOMContentLoaded', () => {
            adminApp.loadUsers();
        });
    </script>
</body>
</html>
