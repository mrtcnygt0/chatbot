<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat</title>
    <?php
    require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
    require_once __DIR__ . '/../app/middlewares/CsrfMiddleware.php';
    require_once __DIR__ . '/../app/helpers/Url.php';
    
    AuthMiddleware::require();
    $user = AuthMiddleware::getUser();
    
    echo CsrfMiddleware::metaTag();
    ?>
    <link rel="stylesheet" href="<?php echo Url::asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo Url::asset('css/chat.css'); ?>">
</head>
<body>
    <div class="chat-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>AI Chat</h1>
                <button class="btn-icon" id="newChatBtn" title="New Chat">
                    ➕
                </button>
            </div>

            <div style="padding: 16px;">
                <button class="btn btn-primary new-chat-btn" id="newChatBtn2">
                    ➕ New Chat
                </button>
                <div id="quotaDisplay" class="quota-display">
                    <div class="quota-title">Loading...</div>
                </div>
            </div>

            <div class="conversations-list" id="conversationsList">
                <div style="padding: 20px; text-align: center; color: var(--text-tertiary);">
                    <div class="loading"></div>
                </div>
            </div>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['email'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-email" id="userEmail"><?php echo htmlspecialchars($user['email']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($user['role']); ?></div>
                    </div>
                    <button class="btn-icon" id="logoutBtn" title="Logout">
                        🚪
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="chat-header">
                <div class="flex items-center gap-2">
                    <button class="btn-icon" id="mobileMenuBtn">☰</button>
                    <h2 class="chat-title" id="chatTitle">AI Chat</h2>
                </div>
                <div class="chat-actions">
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="<?php echo Url::admin(''); ?>" class="btn btn-secondary btn-sm">
                            ⚙️ Admin Panel
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="messages-container" id="messagesContainer">
                <!-- Empty State -->
                <div class="empty-state" id="emptyState">
                    <div class="empty-state-icon">🤖</div>
                    <h2 class="empty-state-title">Welcome to AI Chat</h2>
                    <p class="empty-state-text">
                        Start a conversation by creating a new chat or selecting an existing one from the sidebar.
                    </p>
                </div>
            </div>

            <div class="input-area">
                <div class="input-wrapper">
                    <textarea 
                        id="messageInput" 
                        class="message-input" 
                        placeholder="Type your message... (Shift+Enter for new line)"
                        rows="1"
                    ></textarea>
                    <button id="sendBtn" class="btn btn-primary send-btn" title="Send message">
                        ➤
                    </button>
                </div>
            </div>
        </main>

        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobileOverlay"></div>
    </div>

    <script>
        // Make new chat buttons work the same way
        document.getElementById('newChatBtn2')?.addEventListener('click', () => {
            document.getElementById('newChatBtn').click();
        });
        
        // Pass base path to JavaScript
        window.BASE_PATH = '<?php echo Url::getBasePath(); ?>';
    </script>
    <script src="<?php echo Url::asset('js/app.js'); ?>"></script>
</body>
</html>
