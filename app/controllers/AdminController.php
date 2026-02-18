<?php
/**
 * Admin Controller
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/ApiLog.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/CsrfMiddleware.php';
require_once __DIR__ . '/../services/BudgetService.php';

class AdminController {
    private $userModel;
    private $conversationModel;
    private $apiLogModel;
    private $budgetService;

    public function __construct() {
        $this->userModel = new User();
        $this->conversationModel = new Conversation();
        $this->apiLogModel = new ApiLog();
        $this->budgetService = new BudgetService();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboard() {
        try {
            AuthMiddleware::requireAdmin();
            
            $totalUsers = $this->userModel->count();
            $totalCost = $this->budgetService->getTotalCost();
            $topUsers = $this->apiLogModel->getTopUsers(10);
            $dailyUsage = $this->apiLogModel->getDailyUsage(30);
            
            // Get system settings
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM system_settings";
            $stmt = $db->query($sql);
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            Response::success('Dashboard data retrieved', [
                'total_users' => $totalUsers,
                'total_cost' => $totalCost,
                'top_users' => $topUsers,
                'daily_usage' => $dailyUsage,
                'settings' => $settings
            ]);
            
        } catch (Exception $e) {
            error_log('Get dashboard error: ' . $e->getMessage());
            Response::error('Failed to get dashboard data');
        }
    }

    /**
     * Get all users
     */
    public function getUsers() {
        try {
            AuthMiddleware::requireAdmin();
            
            $page = intval($_GET['page'] ?? 1);
            $perPage = intval($_GET['per_page'] ?? 50);
            $offset = ($page - 1) * $perPage;
            
            $users = $this->userModel->getAll($perPage, $offset);
            $total = $this->userModel->count();
            
            Response::success('Users retrieved', [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
        } catch (Exception $e) {
            error_log('Get users error: ' . $e->getMessage());
            Response::error('Failed to get users');
        }
    }

    /**
     * Get user details
     */
    public function getUserDetails() {
        try {
            AuthMiddleware::requireAdmin();
            
            $userId = intval($_GET['user_id'] ?? 0);
            
            if (!$userId) {
                Response::error('User ID required');
            }
            
            $user = $this->userModel->findById($userId);
            if (!$user) {
                Response::error('User not found', null, 404);
            }
            
            $statistics = $this->userModel->getStatistics($userId);
            $logs = $this->apiLogModel->getUserLogs($userId, 50);
            
            unset($user['password_hash']);
            
            Response::success('User details retrieved', [
                'user' => $user,
                'statistics' => $statistics,
                'recent_logs' => $logs
            ]);
            
        } catch (Exception $e) {
            error_log('Get user details error: ' . $e->getMessage());
            Response::error('Failed to get user details');
        }
    }

    /**
     * Update user quota
     */
    public function updateUserQuota() {
        try {
            AuthMiddleware::requireAdmin();
            CsrfMiddleware::verify();
            
            $userId = intval($_POST['user_id'] ?? 0);
            $quotaTokens = intval($_POST['quota_tokens'] ?? 0);
            $dailyLimit = intval($_POST['daily_limit'] ?? 0);
            $monthlyLimit = intval($_POST['monthly_limit'] ?? 0);
            
            if (!$userId) {
                Response::error('User ID required');
            }
            
            $validation = Validator::positiveInteger($quotaTokens, 'Quota tokens');
            if (!$validation['valid']) {
                Response::error($validation['message']);
            }
            
            $success = $this->userModel->updateQuota($userId, $quotaTokens, $dailyLimit, $monthlyLimit);
            
            if ($success) {
                Response::success('User quota updated');
            } else {
                Response::error('Failed to update quota');
            }
            
        } catch (Exception $e) {
            error_log('Update quota error: ' . $e->getMessage());
            Response::error('Failed to update quota');
        }
    }

    /**
     * Toggle user status
     */
    public function toggleUserStatus() {
        try {
            AuthMiddleware::requireAdmin();
            CsrfMiddleware::verify();
            
            $userId = intval($_POST['user_id'] ?? 0);
            $isActive = isset($_POST['is_active']) && $_POST['is_active'] === 'true';
            
            if (!$userId) {
                Response::error('User ID required');
            }
            
            // Prevent disabling own account
            if ($userId === AuthMiddleware::getUserId()) {
                Response::error('Cannot disable your own account');
            }
            
            $success = $this->userModel->updateStatus($userId, $isActive);
            
            if ($success) {
                Response::success('User status updated');
            } else {
                Response::error('Failed to update status');
            }
            
        } catch (Exception $e) {
            error_log('Toggle status error: ' . $e->getMessage());
            Response::error('Failed to toggle status');
        }
    }

    /**
     * Delete user
     */
    public function deleteUser() {
        try {
            AuthMiddleware::requireAdmin();
            CsrfMiddleware::verify();
            
            $userId = intval($_POST['user_id'] ?? 0);
            
            if (!$userId) {
                Response::error('User ID required');
            }
            
            // Prevent deleting own account
            if ($userId === AuthMiddleware::getUserId()) {
                Response::error('Cannot delete your own account');
            }
            
            $success = $this->userModel->delete($userId);
            
            if ($success) {
                Response::success('User deleted');
            } else {
                Response::error('Failed to delete user');
            }
            
        } catch (Exception $e) {
            error_log('Delete user error: ' . $e->getMessage());
            Response::error('Failed to delete user');
        }
    }

    /**
     * Get all API logs
     */
    public function getLogs() {
        try {
            AuthMiddleware::requireAdmin();
            
            $page = intval($_GET['page'] ?? 1);
            $perPage = intval($_GET['per_page'] ?? 100);
            $offset = ($page - 1) * $perPage;
            
            $logs = $this->apiLogModel->getAll($perPage, $offset);
            
            Response::success('Logs retrieved', [
                'logs' => $logs,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
        } catch (Exception $e) {
            error_log('Get logs error: ' . $e->getMessage());
            Response::error('Failed to get logs');
        }
    }

    /**
     * Get all conversations
     */
    public function getConversations() {
        try {
            AuthMiddleware::requireAdmin();
            
            $page = intval($_GET['page'] ?? 1);
            $perPage = intval($_GET['per_page'] ?? 100);
            $offset = ($page - 1) * $perPage;
            
            $conversations = $this->conversationModel->getAll($perPage, $offset);
            
            Response::success('Conversations retrieved', [
                'conversations' => $conversations,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
        } catch (Exception $e) {
            error_log('Get conversations error: ' . $e->getMessage());
            Response::error('Failed to get conversations');
        }
    }

    /**
     * Update system setting
     */
    public function updateSetting() {
        try {
            AuthMiddleware::requireAdmin();
            CsrfMiddleware::verify();
            
            $key = Security::sanitizeInput($_POST['key'] ?? '');
            $value = Security::sanitizeInput($_POST['value'] ?? '');
            
            if (empty($key)) {
                Response::error('Setting key required');
            }
            
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE system_settings SET setting_value = :value WHERE setting_key = :key";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([
                ':key' => $key,
                ':value' => $value
            ]);
            
            if ($success) {
                Response::success('Setting updated');
            } else {
                Response::error('Failed to update setting');
            }
            
        } catch (Exception $e) {
            error_log('Update setting error: ' . $e->getMessage());
            Response::error('Failed to update setting');
        }
    }

    /**
     * Toggle API status
     */
    public function toggleApi() {
        try {
            AuthMiddleware::requireAdmin();
            CsrfMiddleware::verify();
            
            $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true';
            
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE system_settings SET setting_value = :value WHERE setting_key = 'api_enabled'";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([':value' => $enabled ? '1' : '0']);
            
            if ($success) {
                Response::success('API status updated');
            } else {
                Response::error('Failed to update API status');
            }
            
        } catch (Exception $e) {
            error_log('Toggle API error: ' . $e->getMessage());
            Response::error('Failed to toggle API');
        }
    }
}
