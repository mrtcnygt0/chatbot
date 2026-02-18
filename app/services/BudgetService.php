<?php
/**
 * Budget Service
 * Handles token quota and budget management
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ApiLog.php';

class BudgetService {
    private $userModel;
    private $apiLogModel;

    public function __construct() {
        $this->userModel = new User();
        $this->apiLogModel = new ApiLog();
    }

    /**
     * Check if user can make request
     */
    public function canMakeRequest($userId, $estimatedTokens) {
        return $this->userModel->hasEnoughTokens($userId, $estimatedTokens);
    }

    /**
     * Deduct tokens from user
     */
    public function deductTokens($userId, $tokens) {
        return $this->userModel->deductTokens($userId, $tokens);
    }

    /**
     * Log API usage
     */
    public function logUsage($userId, $conversationId, $inputTokens, $outputTokens, $cost, $status = 'success', $errorMessage = null) {
        return $this->apiLogModel->create(
            $userId,
            $conversationId,
            $inputTokens,
            $outputTokens,
            $cost,
            $status,
            $errorMessage
        );
    }

    /**
     * Get user's remaining quota
     */
    public function getRemainingQuota($userId) {
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            return 0;
        }
        
        return [
            'quota_tokens' => $user['quota_tokens'],
            'daily_limit' => $user['daily_limit'],
            'monthly_limit' => $user['monthly_limit'],
            'tokens_used_today' => $user['tokens_used_today'],
            'tokens_used_month' => $user['tokens_used_month'],
            'daily_remaining' => max(0, $user['daily_limit'] - $user['tokens_used_today']),
            'monthly_remaining' => max(0, $user['monthly_limit'] - $user['tokens_used_month'])
        ];
    }

    /**
     * Get user's usage statistics
     */
    public function getUserStatistics($userId, $days = 30) {
        return $this->apiLogModel->getStatistics($userId, $days);
    }

    /**
     * Get total cost
     */
    public function getTotalCost($userId = null) {
        return $this->apiLogModel->getTotalCost($userId);
    }

    /**
     * Check quota and throw exception if exceeded
     */
    public function enforceQuota($userId, $estimatedTokens) {
        if (!$this->canMakeRequest($userId, $estimatedTokens)) {
            $quota = $this->getRemainingQuota($userId);
            
            if ($quota['quota_tokens'] < $estimatedTokens) {
                throw new Exception('Token quota exceeded. Please contact administrator.');
            }
            
            if ($quota['daily_remaining'] < $estimatedTokens) {
                throw new Exception('Daily token limit exceeded. Please try again tomorrow.');
            }
            
            if ($quota['monthly_remaining'] < $estimatedTokens) {
                throw new Exception('Monthly token limit exceeded. Please try again next month.');
            }
            
            throw new Exception('Token quota exceeded');
        }
        
        return true;
    }
}
