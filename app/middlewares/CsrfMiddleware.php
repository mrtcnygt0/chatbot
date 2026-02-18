<?php
/**
 * CSRF Protection Middleware
 */

require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Response.php';

class CsrfMiddleware {
    
    /**
     * Verify CSRF token
     */
    public static function verify() {
        AuthMiddleware::initSession();
        
        $token = null;
        
        // Check for token in POST data
        if (isset($_POST['_csrf_token'])) {
            $token = $_POST['_csrf_token'];
        }
        // Check for token in header
        elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        if (!$token || !Security::verifyCsrfToken($token)) {
            Response::error('CSRF token validation failed', null, 403);
        }
        
        return true;
    }

    /**
     * Generate and return CSRF token
     */
    public static function getToken() {
        AuthMiddleware::initSession();
        return Security::generateCsrfToken();
    }

    /**
     * Generate hidden CSRF input field
     */
    public static function field() {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get CSRF meta tag
     */
    public static function metaTag() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
