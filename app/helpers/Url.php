<?php
/**
 * URL Helper Functions
 * Handles base path for subdirectory deployments
 */

class Url {
    private static $basePath = null;

    /**
     * Get base path from config or auto-detect
     */
    public static function getBasePath() {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        // Try to get from config
        try {
            $config = Database::getInstance()->getConfig('app.base_path');
            if ($config !== null) {
                self::$basePath = rtrim($config, '/');
                return self::$basePath;
            }
        } catch (Exception $e) {
            // Config not available, auto-detect
        }

        // Auto-detect from script name
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        
        // Remove /public if present
        if (substr($scriptDir, -7) === '/public') {
            $scriptDir = substr($scriptDir, 0, -7);
        }
        
        // Remove trailing slash, keep empty for root
        self::$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
        
        return self::$basePath;
    }

    /**
     * Generate URL with base path
     */
    public static function to($path = '') {
        $basePath = self::getBasePath();
        $path = ltrim($path, '/');
        
        if (empty($path)) {
            return $basePath ?: '/';
        }
        
        return $basePath . '/' . $path;
    }

    /**
     * Generate asset URL
     */
    public static function asset($path) {
        return self::to('assets/' . ltrim($path, '/'));
    }

    /**
     * Generate API URL
     */
    public static function api($path) {
        return self::to('api/' . ltrim($path, '/'));
    }

    /**
     * Generate admin URL
     */
    public static function admin($path = '') {
        return self::to('admin/' . ltrim($path, '/'));
    }

    /**
     * Redirect to URL
     */
    public static function redirect($path) {
        header('Location: ' . self::to($path));
        exit;
    }

    /**
     * Get current URL
     */
    public static function current() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        return $protocol . '://' . $host . $uri;
    }
}
