<?php
/**
 * Chat Controller
 * Main controller for chat operations
 */

require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../services/OpenAIService.php';
require_once __DIR__ . '/../services/BudgetService.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/RateLimitMiddleware.php';
require_once __DIR__ . '/../middlewares/CsrfMiddleware.php';

class ChatController {
    private $conversationModel;
    private $messageModel;
    private $openAIService;
    private $budgetService;

    public function __construct() {
        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
        $this->openAIService = new OpenAIService();
        $this->budgetService = new BudgetService();
    }

    /**
     * Get user conversations
     */
    public function getConversations() {
        try {
            AuthMiddleware::require();
            $userId = AuthMiddleware::getUserId();
            
            $conversations = $this->conversationModel->getUserConversations($userId);
            
            Response::success('Conversations retrieved', $conversations);
            
        } catch (Exception $e) {
            error_log('Get conversations error: ' . $e->getMessage());
            Response::error('Failed to get conversations');
        }
    }

    /**
     * Create new conversation
     */
    public function createConversation() {
        try {
            AuthMiddleware::require();
            CsrfMiddleware::verify();
            
            $userId = AuthMiddleware::getUserId();
            $title = Security::sanitizeInput($_POST['title'] ?? 'New Chat');
            
            $conversationId = $this->conversationModel->create($userId, $title);
            
            Response::success('Conversation created', [
                'conversation_id' => $conversationId,
                'title' => $title
            ]);
            
        } catch (Exception $e) {
            error_log('Create conversation error: ' . $e->getMessage());
            Response::error('Failed to create conversation');
        }
    }

    /**
     * Get conversation messages
     */
    public function getMessages() {
        try {
            AuthMiddleware::require();
            $userId = AuthMiddleware::getUserId();
            
            $conversationId = intval($_GET['conversation_id'] ?? 0);
            
            if (!$conversationId) {
                Response::error('Conversation ID required');
            }
            
            // Verify ownership
            $conversation = $this->conversationModel->findById($conversationId, $userId);
            if (!$conversation) {
                Response::error('Conversation not found', null, 404);
            }
            
            $messages = $this->messageModel->getConversationMessages($conversationId);
            
            Response::success('Messages retrieved', [
                'conversation' => $conversation,
                'messages' => $messages
            ]);
            
        } catch (Exception $e) {
            error_log('Get messages error: ' . $e->getMessage());
            Response::error('Failed to get messages');
        }
    }

    /**
     * Send message and get AI response
     */
    public function sendMessage() {
        try {
            AuthMiddleware::require();
            CsrfMiddleware::verify();
            
            $userId = AuthMiddleware::getUserId();
            
            // Rate limiting
            RateLimitMiddleware::checkChat($userId);
            
            // Get input
            $conversationId = intval($_POST['conversation_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            $stream = isset($_POST['stream']) && $_POST['stream'] === 'true';
            
            if (empty($message)) {
                Response::error('Message cannot be empty');
            }
            
            if (!$conversationId) {
                Response::error('Conversation ID required');
            }
            
            // Verify ownership
            $conversation = $this->conversationModel->findById($conversationId, $userId);
            if (!$conversation) {
                Response::error('Conversation not found', null, 404);
            }
            
            // Check if API is enabled
            if (!$this->openAIService->isApiEnabled()) {
                Response::error('API is currently disabled by administrator');
            }
            
            // Estimate tokens
            $estimatedTokens = $this->openAIService->estimateTokens($message) * 3; // Multiply for safety
            
            // Check quota
            $this->budgetService->enforceQuota($userId, $estimatedTokens);
            
            // Save user message
            $userMessageId = $this->messageModel->create($conversationId, 'user', $message);
            
            // Get conversation history
            $history = $this->messageModel->getLastMessages($conversationId, 10);
            
            // Build messages for OpenAI
            $messages = $this->openAIService->buildMessages(
                array_slice($history, 0, -1), // Exclude the message we just added
                $message
            );
            
            if ($stream) {
                $this->handleStreamingResponse($userId, $conversationId, $messages);
            } else {
                $this->handleNormalResponse($userId, $conversationId, $messages);
            }
            
        } catch (Exception $e) {
            error_log('Send message error: ' . $e->getMessage());
            
            if (isset($userId) && isset($conversationId)) {
                $this->budgetService->logUsage(
                    $userId,
                    $conversationId,
                    0,
                    0,
                    0,
                    'error',
                    $e->getMessage()
                );
            }
            
            Response::error($e->getMessage());
        }
    }

    /**
     * Handle normal (non-streaming) response
     */
    private function handleNormalResponse($userId, $conversationId, $messages) {
        try {
            // Call OpenAI API
            $response = $this->openAIService->chatCompletion($messages, false);
            
            $assistantMessage = $response['choices'][0]['message']['content'] ?? '';
            $inputTokens = $response['usage']['prompt_tokens'] ?? 0;
            $outputTokens = $response['usage']['completion_tokens'] ?? 0;
            $totalTokens = $response['usage']['total_tokens'] ?? 0;
            
            // Calculate cost
            $cost = $this->openAIService->calculateCost($inputTokens, $outputTokens);
            
            // Save assistant message
            $this->messageModel->create($conversationId, 'assistant', $assistantMessage, $outputTokens);
            
            // Deduct tokens
            $this->budgetService->deductTokens($userId, $totalTokens);
            
            // Log usage
            $this->budgetService->logUsage(
                $userId,
                $conversationId,
                $inputTokens,
                $outputTokens,
                $cost,
                'success'
            );
            
            // Update conversation timestamp
            $this->conversationModel->touch($conversationId);
            
            // Get remaining quota
            $quota = $this->budgetService->getRemainingQuota($userId);
            
            Response::success('Message sent', [
                'message' => $assistantMessage,
                'tokens_used' => $totalTokens,
                'cost' => $cost,
                'quota' => $quota
            ]);
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Handle streaming response
     */
    private function handleStreamingResponse($userId, $conversationId, $messages) {
        try {
            // Start SSE stream
            Response::streamStart();
            
            // Send start event
            Response::streamSend(['status' => 'started'], 'start');
            
            $fullResponse = '';
            
            // Call OpenAI API with streaming
            $this->openAIService->chatCompletion($messages, true);
            
            // Note: The actual streaming is handled in OpenAIService::handleStreamChunk
            // Here we would need to collect the full response to save it
            
            // For now, we'll estimate tokens since we don't have actual usage with streaming
            $estimatedInputTokens = $this->openAIService->estimateTokens(json_encode($messages));
            $estimatedOutputTokens = $this->openAIService->estimateTokens($fullResponse);
            $totalTokens = $estimatedInputTokens + $estimatedOutputTokens;
            
            $cost = $this->openAIService->calculateCost($estimatedInputTokens, $estimatedOutputTokens);
            
            // Save assistant message (we'd need to capture this from the stream)
            // $this->messageModel->create($conversationId, 'assistant', $fullResponse, $estimatedOutputTokens);
            
            // Deduct tokens
            $this->budgetService->deductTokens($userId, $totalTokens);
            
            // Log usage
            $this->budgetService->logUsage(
                $userId,
                $conversationId,
                $estimatedInputTokens,
                $estimatedOutputTokens,
                $cost,
                'success'
            );
            
            // Update conversation
            $this->conversationModel->touch($conversationId);
            
            Response::streamEnd();
            
        } catch (Exception $e) {
            Response::streamSend(['error' => $e->getMessage()], 'error');
            throw $e;
        }
    }

    /**
     * Rename conversation
     */
    public function renameConversation() {
        try {
            AuthMiddleware::require();
            CsrfMiddleware::verify();
            
            $userId = AuthMiddleware::getUserId();
            $conversationId = intval($_POST['conversation_id'] ?? 0);
            $newTitle = Security::sanitizeInput($_POST['title'] ?? '');
            
            if (!$conversationId || empty($newTitle)) {
                Response::error('Conversation ID and title required');
            }
            
            $success = $this->conversationModel->updateTitle($conversationId, $userId, $newTitle);
            
            if ($success) {
                Response::success('Conversation renamed');
            } else {
                Response::error('Failed to rename conversation');
            }
            
        } catch (Exception $e) {
            error_log('Rename conversation error: ' . $e->getMessage());
            Response::error('Failed to rename conversation');
        }
    }

    /**
     * Delete conversation
     */
    public function deleteConversation() {
        try {
            AuthMiddleware::require();
            CsrfMiddleware::verify();
            
            $userId = AuthMiddleware::getUserId();
            $conversationId = intval($_POST['conversation_id'] ?? 0);
            
            if (!$conversationId) {
                Response::error('Conversation ID required');
            }
            
            $success = $this->conversationModel->delete($conversationId, $userId);
            
            if ($success) {
                Response::success('Conversation deleted');
            } else {
                Response::error('Failed to delete conversation');
            }
            
        } catch (Exception $e) {
            error_log('Delete conversation error: ' . $e->getMessage());
            Response::error('Failed to delete conversation');
        }
    }

    /**
     * Get user quota info
     */
    public function getQuota() {
        try {
            AuthMiddleware::require();
            
            $userId = AuthMiddleware::getUserId();
            $quota = $this->budgetService->getRemainingQuota($userId);
            
            Response::success('Quota retrieved', $quota);
            
        } catch (Exception $e) {
            error_log('Get quota error: ' . $e->getMessage());
            Response::error('Failed to get quota');
        }
    }
}
