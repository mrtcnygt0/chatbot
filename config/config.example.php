<?php
/**
 * Configuration File Example
 * Copy this file to config.php and update with your settings
 */

return [
    // Application Settings
    'app' => [
        'name' => 'AI Chat',
        'url' => 'http://localhost',
        'environment' => 'production', // development, production
        'debug' => false,
        'timezone' => 'UTC',
    ],

    // Database Settings
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'chatbot_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    // OpenAI API Settings
    'openai' => [
        'api_key' => '', // Your OpenAI API key
        'model' => 'gpt-4o-mini', // ONLY gpt-4o-mini allowed
        'max_tokens' => 4000,
        'temperature' => 0.7,
        'timeout' => 30,
    ],

    // Security Settings
    'security' => [
        'session_lifetime' => 7200, // 2 hours
        'csrf_token_name' => '_csrf_token',
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],

    // Rate Limiting
    'rate_limit' => [
        'chat_requests_per_minute' => 10,
        'api_requests_per_hour' => 100,
    ],

    // Token & Budget Settings
    'budget' => [
        'default_user_quota' => 100000,
        'default_daily_limit' => 10000,
        'default_monthly_limit' => 100000,
        'gpt4o_mini_input_cost' => 0.00000015, // $0.15 per 1M input tokens
        'gpt4o_mini_output_cost' => 0.0000006, // $0.60 per 1M output tokens
    ],
];
