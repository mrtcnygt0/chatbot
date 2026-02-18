// Admin Panel JavaScript

class AdminApp {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.basePath = window.BASE_PATH || '';
        this.currentModal = null;
        this.init();
    }

    init() {
        this.loadDashboard();
        this.attachEventListeners();
    }

    url(path) {
        // Helper to generate correct URL with base path
        if (path.startsWith('/')) {
            path = path.substring(1);
        }
        return this.basePath ? this.basePath + '/' + path : '/' + path;
    }

    attachEventListeners() {
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => this.toggleMobileMenu());
        
        // Modal close buttons
        document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });

        // Click outside modal to close
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.closeModal();
            });
        });
    }

    async loadDashboard() {
        try {
            const response = await this.fetch(this.url('/admin/api/dashboard.php');
            const data = await response.json();

            if (data.success) {
                this.renderDashboard(data.data);
            }
        } catch (error) {
            console.error('Failed to load dashboard:', error);
        }
    }

    renderDashboard(data) {
        // Update stats
        document.getElementById('totalUsers').textContent = data.total_users || 0;
        document.getElementById('totalCost').textContent = '$' + (data.total_cost || 0).toFixed(4);

        // Render top users table
        this.renderTopUsers(data.top_users || []);

        // Render daily usage chart (simplified)
        this.renderUsageChart(data.daily_usage || []);
    }

    renderTopUsers(users) {
        const tbody = document.getElementById('topUsersTable');
        if (!tbody) return;

        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-tertiary);">No data available</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${this.escapeHtml(user.email)}</td>
                <td>${user.total_requests || 0}</td>
                <td>${(user.total_tokens || 0).toLocaleString()}</td>
                <td>$${(user.total_cost || 0).toFixed(4)}</td>
                <td class="table-actions-cell">
                    <button class="btn-sm btn-secondary" onclick="adminApp.viewUser(${user.id})">View</button>
                </td>
            </tr>
        `).join('');
    }

    renderUsageChart(data) {
        // Simple text-based visualization
        const container = document.getElementById('usageChart');
        if (!container || data.length === 0) return;

        const maxCost = Math.max(...data.map(d => parseFloat(d.cost) || 0));

        container.innerHTML = data.map(d => {
            const percentage = maxCost > 0 ? ((parseFloat(d.cost) || 0) / maxCost) * 100 : 0;
            return `
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                        <span>${d.date}</span>
                        <span>$${parseFloat(d.cost || 0).toFixed(4)}</span>
                    </div>
                    <div style="height: 20px; background: var(--bg-tertiary); border-radius: 4px; overflow: hidden;">
                        <div style="width: ${percentage}%; height: 100%; background: var(--accent-gradient);"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    async loadUsers() {
        try {
            const response = await this.fetch(this.url('/admin/api/users.php');
            const data = await response.json();

            if (data.success) {
                this.renderUsersTable(data.data.users || []);
            }
        } catch (error) {
            this.showError('Failed to load users');
        }
    }

    renderUsersTable(users) {
        const tbody = document.getElementById('usersTable');
        if (!tbody) return;

        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-tertiary);">No users found</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${this.escapeHtml(user.email)}</td>
                <td><span class="badge badge-${user.role === 'admin' ? 'warning' : 'info'}">${user.role}</span></td>
                <td>${(user.quota_tokens || 0).toLocaleString()}</td>
                <td>${(user.tokens_used_today || 0).toLocaleString()}</td>
                <td><span class="badge badge-${user.is_active ? 'success' : 'error'}">${user.is_active ? 'Active' : 'Inactive'}</span></td>
                <td class="table-actions-cell">
                    <button class="btn-sm btn-secondary" onclick="adminApp.editUserQuota(${user.id})">Edit Quota</button>
                    <button class="btn-sm btn-secondary" onclick="adminApp.toggleUserStatus(${user.id}, ${!user.is_active})">
                        ${user.is_active ? 'Disable' : 'Enable'}
                    </button>
                    <button class="btn-sm btn-danger" onclick="adminApp.deleteUser(${user.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    async viewUser(userId) {
        try {
            const response = await this.fetch(`/admin/api/user-details.php?user_id=${userId}`);
            const data = await response.json();

            if (data.success) {
                this.showUserDetailsModal(data.data);
            }
        } catch (error) {
            this.showError('Failed to load user details');
        }
    }

    showUserDetailsModal(data) {
        const modal = document.getElementById('userDetailsModal');
        if (!modal) return;

        document.getElementById('modalUserEmail').textContent = data.user.email;
        document.getElementById('modalUserQuota').textContent = (data.statistics.quota_tokens || 0).toLocaleString();
        document.getElementById('modalUserUsage').textContent = (data.statistics.tokens_used_today || 0).toLocaleString();
        document.getElementById('modalUserCost').textContent = '$' + (data.statistics.total_cost || 0).toFixed(4);

        this.showModal(modal);
    }

    async editUserQuota(userId) {
        const quotaTokens = prompt('Enter new quota tokens:');
        const dailyLimit = prompt('Enter daily limit:');
        const monthlyLimit = prompt('Enter monthly limit:');

        if (!quotaTokens || !dailyLimit || !monthlyLimit) return;

        try {
            const response = await this.fetch(this.url('/admin/api/update-quota.php', {
                method: 'POST',
                body: JSON.stringify({
                    user_id: userId,
                    quota_tokens: parseInt(quotaTokens),
                    daily_limit: parseInt(dailyLimit),
                    monthly_limit: parseInt(monthlyLimit)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Quota updated successfully');
                this.loadUsers();
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Failed to update quota');
        }
    }

    async toggleUserStatus(userId, isActive) {
        try {
            const response = await this.fetch(this.url('/admin/api/toggle-status.php', {
                method: 'POST',
                body: JSON.stringify({
                    user_id: userId,
                    is_active: isActive
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('User status updated');
                this.loadUsers();
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Failed to update status');
        }
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;

        try {
            const response = await this.fetch(this.url('/admin/api/delete-user.php', {
                method: 'POST',
                body: JSON.stringify({ user_id: userId })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('User deleted successfully');
                this.loadUsers();
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Failed to delete user');
        }
    }

    async toggleApiStatus(enabled) {
        try {
            const response = await this.fetch(this.url('/admin/api/toggle-api.php', {
                method: 'POST',
                body: JSON.stringify({ enabled })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(`API ${enabled ? 'enabled' : 'disabled'} successfully`);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('Failed to toggle API');
        }
    }

    showModal(modal) {
        this.currentModal = modal;
        modal.classList.add('active');
    }

    closeModal() {
        if (this.currentModal) {
            this.currentModal.classList.remove('active');
            this.currentModal = null;
        }
    }

    toggleMobileMenu() {
        document.querySelector('.admin-sidebar')?.classList.toggle('open');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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

// Initialize when DOM is loaded
let adminApp;
document.addEventListener('DOMContentLoaded', () => {
    adminApp = new AdminApp();
});
