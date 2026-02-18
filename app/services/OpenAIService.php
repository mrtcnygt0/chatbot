<?php
/**
 * OpenAI Service
 * Handles all OpenAI API interactions
 */

require_once __DIR__ . '/../../config/database.php';

class OpenAIService {
    private $apiKey;
    private $model = 'gpt-4o-mini';
    private $maxTokens;
    private $temperature;
    private $timeout;

    public function __construct() {
        $config = Database::getInstance()->getConfig();
        
        $this->apiKey = $config['openai']['api_key'] ?? '';
        $this->model = $config['openai']['model'] ?? 'gpt-4o-mini';
        $this->maxTokens = $config['openai']['max_tokens'] ?? 4000;
        $this->temperature = $config['openai']['temperature'] ?? 0.7;
        $this->timeout = $config['openai']['timeout'] ?? 30;
        
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }
    }

    /**
     * Send chat completion request
     */
    public function chatCompletion($messages, $stream = false) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'stream' => $stream
        ];
        
        if ($stream) {
            return $this->streamRequest($url, $data);
        } else {
            return $this->request($url, $data);
        }
    }

    /**
     * Make non-streaming request
     */
    private function request($url, $data) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception("OpenAI API error (HTTP {$httpCode}): {$errorMessage}");
        }
        
        return json_decode($response, true);
    }

    /**
     * Make streaming request
     */
    private function streamRequest($url, $data) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_WRITEFUNCTION => function($ch, $data) {
                return $this->handleStreamChunk($data);
            }
        ]);
        
        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        return true;
    }

    /**
     * Handle stream chunk
     */
    private function handleStreamChunk($data) {
        $lines = explode("\n", $data);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || !str_starts_with($line, 'data: ')) {
                continue;
            }
            
            $json = substr($line, 6);
            
            if ($json === '[DONE]') {
                Response::streamSend(['done' => true], 'done');
                continue;
            }
            
            $chunk = json_decode($json, true);
            
            if (isset($chunk['choices'][0]['delta']['content'])) {
                $content = $chunk['choices'][0]['delta']['content'];
                Response::streamSend(['content' => $content], 'message');
            }
        }
        
        return strlen($data);
    }

    /**
     * Estimate tokens (rough approximation)
     */
    public function estimateTokens($text) {
        // Rough estimation: ~4 characters per token
        return ceil(strlen($text) / 4);
    }

    /**
     * Build messages array from conversation history
     */
    public function buildMessages($conversationMessages, $newMessage) {
        $messages = [];
        
        // Add conversation history
        foreach ($conversationMessages as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
        
        // Add new user message
        $messages[] = [
            'role' => 'user',
            'content' => $newMessage
        ];
        
        return $messages;
    }

    /**
     * Calculate cost based on tokens
     */
    public function calculateCost($inputTokens, $outputTokens) {
        $config = Database::getInstance()->getConfig();
        
        $inputCost = $config['budget']['gpt4o_mini_input_cost'] ?? 0.00000015;
        $outputCost = $config['budget']['gpt4o_mini_output_cost'] ?? 0.0000006;
        
        return ($inputTokens * $inputCost) + ($outputTokens * $outputCost);
    }

    /**
     * Check if API is enabled
     */
    public function isApiEnabled() {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT setting_value FROM system_settings 
                    WHERE setting_key = 'api_enabled' LIMIT 1";
            
            $stmt = $db->query($sql);
            $result = $stmt->fetch();
            
            return $result && $result['setting_value'] === '1';
        } catch (Exception $e) {
            return true; // Default to enabled if check fails
        }
    }
}
