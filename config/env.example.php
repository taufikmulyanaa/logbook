<?php
// config/env.example.php
// Copy this file to env.php and adjust values for your environment

return [
    'database' => [
        'host' => 'localhost',
        'name' => 'logbook_secure_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    'security' => [
        'max_login_attempts' => 5,
        'lockout_time' => '15 minutes',
        'session_lifetime' => 1800, // 30 minutes in seconds
        'csrf_token_expire' => 3600, // 1 hour
        'password_hash_algo' => PASSWORD_DEFAULT,
    ],
    
    'upload' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB in bytes
        'allowed_types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'xlsx', 'xls'],
        'upload_path' => __DIR__ . '/../uploads/',
        'quarantine_path' => __DIR__ . '/../uploads/quarantine/',
    ],
    
    'logging' => [
        'app_log' => __DIR__ . '/../logs/app.log',
        'error_log' => __DIR__ . '/../logs/error.log',
        'audit_log' => __DIR__ . '/../logs/audit.log',
        'max_log_size' => 50 * 1024 * 1024, // 50MB
        'log_retention_days' => 90,
    ],
    
    'email' => [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => 'your_email@company.com',
        'smtp_password' => 'your_app_password',
        'from_email' => 'noreply@company.com',
        'from_name' => 'R&D Logbook System',
    ],
    
    'app' => [
        'name' => 'R&D Logbook System',
        'version' => '1.0.0',
        'timezone' => 'Asia/Jakarta',
        'debug' => false, // Set to true for development
        'maintenance_mode' => false,
        'base_url' => 'https://your-domain.com',
    ],
    
    'backup' => [
        'enabled' => true,
        'schedule' => 'daily', // daily, weekly, monthly
        'retention_days' => 30,
        'backup_path' => __DIR__ . '/../backups/',
        'compress' => true,
    ],
];

// config/config.php - Enhanced configuration loader
class Config {
    private static $config = null;
    
    public static function load() {
        if (self::$config === null) {
            $env_file = __DIR__ . '/env.php';
            
            if (!file_exists($env_file)) {
                throw new Exception('Environment configuration file not found. Please copy env.example.php to env.php and configure it.');
            }
            
            self::$config = require $env_file;
            
            // Set timezone
            date_default_timezone_set(self::get('app.timezone', 'UTC'));
            
            // Set error reporting based on debug mode
            if (self::get('app.debug', false)) {
                error_reporting(E_ALL);
                ini_set('display_errors', '1');
            } else {
                error_reporting(E_ALL);
                ini_set('display_errors', '0');
            }
        }
        
        return self::$config;
    }
    
    public static function get($key, $default = null) {
        self::load();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

// Enhanced init.php using the new config system
// config/init.php (enhanced version)
?>