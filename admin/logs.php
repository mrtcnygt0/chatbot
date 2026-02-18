<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Logs - Admin Panel</title>
    <?php
    require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
    require_once __DIR__ . '/../app/middlewares/CsrfMiddleware.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../app/models/ApiLog.php';
    
    AuthMiddleware::requireAdmin();
    $user = AuthMiddleware::getUser();
    
    echo CsrfMiddleware::metaTag();
    
    // Load recent logs
    $apiLogModel = new ApiLog();
    $logs = $apiLogModel->getAll(50);
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
                <a href="/admin/logs.php" class="admin-nav-item active">
                    📜 API Logs
                </a>
                <a href="/admin/settings.php" class="admin-nav-item">
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
                <h1 class="admin-header-title">API Logs</h1>
                <div class="chat-actions">
                    <button class="btn-icon" id="mobileMenuBtn">☰</button>
                </div>
            </header>

            <div class="admin-content">
                <!-- Logs Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Recent API Calls (Last 50)</h3>
                        <div class="table-actions">
                            <button class="btn btn-secondary btn-sm" onclick="location.reload()">🔄 Refresh</button>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Model</th>
                                <th>Input Tokens</th>
                                <th>Output Tokens</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                                        No logs found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['user_email'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($log['model']); ?></td>
                                        <td><?php echo number_format($log['input_tokens']); ?></td>
                                        <td><?php echo number_format($log['output_tokens']); ?></td>
                                        <td>$<?php echo number_format($log['cost'], 6); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $log['status'] === 'success' ? 'success' : 
                                                     ($log['status'] === 'error' ? 'error' : 'warning'); 
                                            ?>">
                                                <?php echo htmlspecialchars($log['status']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 12px; color: var(--text-tertiary);">
                                            <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/admin.js"></script>
</body>
</html>
