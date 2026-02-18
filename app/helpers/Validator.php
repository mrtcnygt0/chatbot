<?php
/**
 * Validation Helper Functions
 */

class Validator {
    
    /**
     * Validate required fields
     */
    public static function required($value, $fieldName = 'Field') {
        if (empty($value) && $value !== '0' && $value !== 0) {
            return ['valid' => false, 'message' => "{$fieldName} is required"];
        }
        return ['valid' => true];
    }

    /**
     * Validate email format
     */
    public static function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }
        return ['valid' => true];
    }

    /**
     * Validate string length
     */
    public static function length($value, $min = null, $max = null, $fieldName = 'Field') {
        $length = strlen($value);
        
        if ($min !== null && $length < $min) {
            return ['valid' => false, 'message' => "{$fieldName} must be at least {$min} characters"];
        }
        
        if ($max !== null && $length > $max) {
            return ['valid' => false, 'message' => "{$fieldName} must not exceed {$max} characters"];
        }
        
        return ['valid' => true];
    }

    /**
     * Validate integer
     */
    public static function integer($value, $fieldName = 'Field') {
        if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== 0) {
            return ['valid' => false, 'message' => "{$fieldName} must be a valid integer"];
        }
        return ['valid' => true];
    }

    /**
     * Validate positive integer
     */
    public static function positiveInteger($value, $fieldName = 'Field') {
        if (!filter_var($value, FILTER_VALIDATE_INT) || $value < 0) {
            return ['valid' => false, 'message' => "{$fieldName} must be a positive integer"];
        }
        return ['valid' => true];
    }

    /**
     * Validate range
     */
    public static function range($value, $min, $max, $fieldName = 'Field') {
        if ($value < $min || $value > $max) {
            return ['valid' => false, 'message' => "{$fieldName} must be between {$min} and {$max}"];
        }
        return ['valid' => true];
    }

    /**
     * Validate against allowed values
     */
    public static function in($value, $allowed, $fieldName = 'Field') {
        if (!in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            return ['valid' => false, 'message' => "{$fieldName} must be one of: {$allowedStr}"];
        }
        return ['valid' => true];
    }
}
