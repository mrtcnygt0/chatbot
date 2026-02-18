<?php
/**
 * Response Helper Functions
 */

class Response {
    
    /**
     * Send JSON response
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send success response
     */
    public static function success($message = 'Success', $data = null, $statusCode = 200) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $data = null, $statusCode = 400) {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Redirect
     */
    public static function redirect($url) {
        header("Location: {$url}");
        exit;
    }

    /**
     * Send 404
     */
    public static function notFound($message = 'Not Found') {
        http_response_code(404);
        echo "<h1>404 - {$message}</h1>";
        exit;
    }

    /**
     * Send 403
     */
    public static function forbidden($message = 'Forbidden') {
        http_response_code(403);
        echo "<h1>403 - {$message}</h1>";
        exit;
    }

    /**
     * Stream SSE response
     */
    public static function streamStart() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        
        if (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Send SSE event
     */
    public static function streamSend($data, $event = 'message') {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * End SSE stream
     */
    public static function streamEnd() {
        echo "event: done\n";
        echo "data: {}\n\n";
        
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
