<?php
/**
 * database configuration
 * Inventory & Maintenance Management System
 * 
 * update these placeholders with your actual database credentials
 */

require_once __DIR__ . '/config.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ulsc_inventory');

// Error reporting configuration
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

/**
 * custom error handler
 */
function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR             => 'Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parse Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compile Error',
        E_COMPILE_WARNING   => 'Compile Warning',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated'
    ];
    
    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    $message = "[$errorType] $errstr in $errfile on line $errline";
    
    // Log to file
    error_log($message);
    
    // For development, also display
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "<div style='background: #fee; border: 1px solid #c00; padding: 10px; margin: 10px;'>";
        echo "<strong>$errorType:</strong> $errstr<br>";
        echo "<small>File: $errfile, Line: $errline</small>";
        echo "</div>";
    }
    
    return true;
}

// Set custom error handler
set_error_handler('handleError');

/**
 * get pdo database connection with enhanced error handling
 * @return PDO
 * @throws RuntimeException
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Test connection
            $pdo->query("SELECT 1");
            
        } catch (PDOException $e) {
            $errorMessage = "Database connection failed: " . $e->getMessage();
            
            // Log the error
            error_log($errorMessage);
            
            // For development, show detailed error
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                throw new RuntimeException($errorMessage, 0, $e);
            }
            
            // For production, show generic error
            throw new RuntimeException("Database connection failed. Please contact administrator.");
        }
    }
    
    return $pdo;
}

/**
 * execute database query with error handling
 * @param PDO $pdo
 * @param string $query
 * @param array $params
 * @return PDOStatement
 * @throws RuntimeException
 */
function executeQuery(PDO $pdo, string $query, array $params = []): PDOStatement {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        $errorMessage = "Database query failed: " . $e->getMessage();
        error_log($errorMessage . " | Query: $query | Params: " . json_encode($params));
        
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            throw new RuntimeException($errorMessage, 0, $e);
        }
        
        throw new RuntimeException("Database operation failed. Please try again later.");
    }
}

/**
 * check if database exists
 * @return bool
 */
function databaseExists(): bool {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([DB_NAME]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * create logs directory if it doesn't exist
 */
function ensureLogsDirectory(): void {
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
}

// ensure logs directory exists
ensureLogsDirectory();
