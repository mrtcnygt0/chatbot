// Main Chat Application JavaScript

class ChatApp {
    constructor() {
        this.currentConversationId = null;
        this.isLoading = false;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.basePath = window.BASE_PATH || '';
        
        this.init();
    }

    init() {
        this.loadConversations();
        this.loadUserQuota();
        this.attachEventListeners();
        this.setupAutoResize();
    }

    attachEventListeners() {
        // New chat button
        document.getElementById('newChatBtn')?.addEventListener('click', () => this.createNewConversation());
        
        // Send message
        document.getElementById('sendBtn')?.addEventListener('click', () => this.sendMessage());
        
        // Message input - Enter to send, Shift+Enter for new line
        const messageInput = document.getElementById('messageInput');
        messageInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Logout
        document.getElementById('logoutBtn')?.addEventListener('click', () => this.logout());
        
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => this.toggleMobileMenu());
        document.getElementById('mobileOverlay')?.addEventListener('click', () => this.toggleMobileMenu());
    }

    setupAutoResize() {
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', () => {
                messageInput.style.height = 'auto';
                messageInput.style.height = Math.min(messageInput.scrollHeight, 200) + 'px';
            });
        }
    }

    async loadConversations() {
        try {
            const response = await this.fetch(this.url('/api/conversations.php'));
            const data = await response.json();
            
            if (data.success) {
                this.renderConversations(data.data);
            }
        } catch (error) {
            console.error('Failed to load conversations:', error);
        }
    }

    renderConversations(conversations) {
        const list = document.getElementById('conversationsList');
        if (!list) return;
        
        if (conversations.length === 0) {
            list.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-tertiary); font-size: 13px;">No conversations yet</div>';
            return;
        }
        
        list.innerHTML = conversations.map(conv => `
            <div class="conversation-item ${conv.id == this.currentConversationId ? 'active' : ''}" 
                 data-id="${conv.id}" 
                 onclick="chatApp.loadConversation(${conv.id})">
                <span class="conversation-title">${this.escapeHtml(conv.title)}</span>
                <div class="conversation-actions">
                    <button class="conversation-action" onclick="event.stopPropagation(); chatApp.renameConversation(${conv.id})" title="Rename">
                        ✏️
                    </button>
                    <button class="conversation-action" onclick="event.stopPropagation(); chatApp.deleteConversation(${conv.id})" title="Delete">
                        🗑️
                    </button>
                </div>
            </div>
        `).join('');
    }

    async createNewConversation() {
        try {
            const response = await this.fetch(this.url('/api/conversations.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'create', title: 'New Chat' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadConversations();
                this.loadConversation(data.data.conversation_id);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Failed to create conversation');
        }
    }

    async loadConversation(conversationId) {
        try {
            this.currentConversationId = conversationId;
            
            const response = await this.fetch(`/api/messages.php?conversation_id=${conversationId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderMessages(data.data.messages);
                document.getElementById('chatTitle').textContent = data.data.conversation.title;
                document.getElementById('emptyState')?.classList.add('hidden');
                this.loadConversations(); // Refresh to update active state
            }
        } catch (error) {
            this.showError('Failed to load conversation');
        }
    }

    renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        container.innerHTML = messages.map(msg => this.createMessageHTML(msg)).join('');
        this.scrollToBottom();
    }

    createMessageHTML(message) {
        const isUser = message.role === 'user';
        const avatar = isUser ? this.getUserInitial() : '🤖';
        const content = this.formatMessage(message.content);
        
        return `
            <div class="message ${message.role}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <div class="message-text">${content}</div>
                    <div class="message-actions">
                        <button class="btn-icon btn-sm" onclick="chatApp.copyMessage(this)" title="Copy">
                            📋
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    formatMessage(content) {
        // Convert markdown-like formatting
        let formatted = this.escapeHtml(content);
        
        // Code blocks
        formatted = formatted.replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
            return `
                <div class="code-header">
                    <span class="code-language">${lang || 'code'}</span>
                    <button class="btn-sm copy-code-btn" onclick="chatApp.copyCode(this)">Copy</button>
                </div>
                <pre><code>${code.trim()}</code></pre>
            `;
        });
        
        // Inline code
        formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Bold
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // Paragraphs
        formatted = formatted.split('\n\n').map(p => `<p>${p.replace(/\n/g, '<br>')}</p>`).join('');
        
        return formatted;
    }

    async sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input?.value.trim();
        
        if (!message || this.isLoading || !this.currentConversationId) return;
        
        this.isLoading = true;
        this.updateSendButton(true);
        
        // Add user message to UI immediately
        this.addMessageToUI('user', message);
        input.value = '';
        input.style.height = 'auto';
        
        // Show typing indicator
        this.showTypingIndicator();
        
        try {
            const response = await this.fetch(this.url('/api/chat.php', {
                method: 'POST',
                body: JSON.stringify({
                    conversation_id: this.currentConversationId,
                    message: message,
                    stream: false
                })
            });
            
            const data = await response.json();
            
            this.hideTypingIndicator();
            
            if (data.success) {
                this.addMessageToUI('assistant', data.data.message);
                this.updateQuotaDisplay(data.data.quota);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.hideTypingIndicator();
            this.showError('Failed to send message: ' + error.message);
        } finally {
            this.isLoading = false;
            this.updateSendButton(false);
        }
    }

    addMessageToUI(role, content) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        const messageHTML = this.createMessageHTML({ role, content });
        container.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    showTypingIndicator() {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        const indicator = `
            <div class="message assistant" id="typingIndicator">
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', indicator);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        document.getElementById('typingIndicator')?.remove();
    }

    updateSendButton(loading) {
        const btn = document.getElementById('sendBtn');
        if (!btn) return;
        
        btn.disabled = loading;
        btn.innerHTML = loading ? '<div class="loading"></div>' : '➤';
    }

    async renameConversation(conversationId) {
        const newTitle = prompt('Enter new conversation title:');
        if (!newTitle) return;
        
        try {
            const response = await this.fetch(this.url('/api/conversations.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'rename',
                    conversation_id: conversationId,
                    title: newTitle
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.loadConversations();
                if (conversationId == this.currentConversationId) {
                    document.getElementById('chatTitle').textContent = newTitle;
                }
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Failed to rename conversation');
        }
    }

    async deleteConversation(conversationId) {
        if (!confirm('Are you sure you want to delete this conversation?')) return;
        
        try {
            const response = await this.fetch(this.url('/api/conversations.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'delete',
                    conversation_id: conversationId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (conversationId == this.currentConversationId) {
                    this.currentConversationId = null;
                    document.getElementById('messagesContainer').innerHTML = '';
                    document.getElementById('emptyState')?.classList.remove('hidden');
                }
                this.loadConversations();
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Failed to delete conversation');
        }
    }

    async loadUserQuota() {
        try {
            const response = await this.fetch(this.url('/api/quota.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateQuotaDisplay(data.data);
            }
        } catch (error) {
            console.error('Failed to load quota:', error);
        }
    }

    updateQuotaDisplay(quota) {
        const display = document.getElementById('quotaDisplay');
        if (!display) return;
        
        const percentage = (quota.tokens_used_today / quota.daily_limit) * 100;
        const remaining = quota.daily_remaining;
        
        display.innerHTML = `
            <div class="quota-title">Daily Usage</div>
            <div class="quota-bar">
                <div class="quota-progress" style="width: ${percentage}%"></div>
            </div>
            <div class="quota-text">${remaining.toLocaleString()} tokens remaining</div>
        `;
    }

    async logout() {
        try {
            const response = await this.fetch(this.url('/api/logout.php'), { method: 'POST' });
            const data = await response.json();
            
            if (data.success) {
                window.location.href = this.url('/login.php');
            }
        } catch (error) {
            this.showError('Failed to logout');
        }
    }

    copyMessage(button) {
        const messageText = button.closest('.message-content').querySelector('.message-text').innerText;
        this.copyToClipboard(messageText);
        this.showSuccess('Message copied!');
    }

    copyCode(button) {
        const code = button.closest('.message-content').querySelector('code').innerText;
        this.copyToClipboard(code);
        button.textContent = 'Copied!';
        setTimeout(() => button.textContent = 'Copy', 2000);
    }

    copyToClipboard(text) {
        navigator.clipboard.writeText(text).catch(err => {
            console.error('Failed to copy:', err);
        });
    }

    toggleMobileMenu() {
        document.querySelector('.sidebar')?.classList.toggle('open');
        document.getElementById('mobileOverlay')?.classList.toggle('active');
    }

    scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    getUserInitial() {
        const email = document.getElementById('userEmail')?.textContent || '';
        return email.charAt(0).toUpperCase();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    url(path) {
        // Helper to generate correct URL with base path
        if (path.startsWith('/')) {
            path = path.substring(1);
        }
        return this.basePath ? this.basePath + '/' + path : '/' + path;
    }

    async fetch(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        return fetch(url, { ...defaultOptions, ...options });
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.textContent = message;
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '9999';
        toast.style.minWidth = '300px';
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize app when DOM is loaded
let chatApp;
document.addEventListener('DOMContentLoaded', () => {
    chatApp = new ChatApp();
});
