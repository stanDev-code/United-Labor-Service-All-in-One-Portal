<?php
/**
 * security helper functions
 * CSRF Protection, Input Validation, and Security Utilities
 */

// Generate CSRF token
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken(string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Token expires after 1 hour
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token input field
function csrfField(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Validate required fields
function validateRequired(array $data, array $required): array {
    $errors = [];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    return $errors;
}

// Sanitize input string
function sanitizeString(string $input, int $maxLength = 255): string {
    return trim(substr(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'), 0, $maxLength));
}

// Validate email
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate numeric input
function validateNumeric(string $input, ?float $min = null, ?float $max = null): bool {
    if (!is_numeric($input)) {
        return false;
    }
    
    $num = (float) $input;
    if ($min !== null && $num < $min) return false;
    if ($max !== null && $num > $max) return false;
    
    return true;
}

// Validate date
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Validate phone number (basic validation)
function validatePhone(string $phone): bool {
    return preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone);
}

// Rate limiting for login attempts
function checkRateLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): array {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    $now = time();
    
    // Reset if time window has passed
    if ($now - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => $now];
        return ['allowed' => true, 'attempts' => 1, 'remaining' => $maxAttempts - 1];
    }
    
    // Check if limit has been exceeded
    if ($data['attempts'] >= $maxAttempts) {
        $remainingTime = $timeWindow - ($now - $data['first_attempt']);
        return [
            'allowed' => false, 
            'attempts' => $data['attempts'], 
            'remaining' => 0,
            'reset_time' => $remainingTime
        ];
    }
    
    // Increment attempts
    $_SESSION[$key]['attempts']++;
    
    return [
        'allowed' => true, 
        'attempts' => $_SESSION[$key]['attempts'], 
        'remaining' => $maxAttempts - $_SESSION[$key]['attempts']
    ];
}

// Log security events
function logSecurityEvent(string $event, array $context = []): void {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    
    $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
    $logEntry = "[$timestamp] IP: $ip | User: $userId | Event: $event$contextStr | UA: $userAgent" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Detect suspicious activity
function detectSuspiciousActivity(): array {
    $warnings = [];
    
    // Check for rapid requests
    $key = 'request_count_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
    }
    
    $_SESSION[$key]['count']++;
    
    // More than 100 requests in 1 minute is suspicious
    if ($_SESSION[$key]['count'] > 100 && (time() - $_SESSION[$key]['start_time']) < 60) {
        $warnings[] = 'High request frequency detected';
        logSecurityEvent('HIGH_REQUEST_FREQUENCY', [
            'count' => $_SESSION[$key]['count'],
            'timeframe' => time() - $_SESSION[$key]['start_time']
        ]);
    }
    
    // Reset counter after 5 minutes
    if (time() - $_SESSION[$key]['start_time'] > 300) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
    }
    
    return $warnings;
}

// Secure file upload validation
function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 5242880): array {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
        return $errors;
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed size';
    }
    
    if (!empty($allowedTypes)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
    }
    
    return $errors;
}

// Generate secure random password
function generateSecurePassword(int $length = 12): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

// Check password strength
function checkPasswordStrength(string $password): array {
    $issues = [];
    
    if (strlen($password) < 8) {
        $issues[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $issues[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $issues[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $issues[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $issues[] = 'Password must contain at least one special character';
    }
    
    return $issues;
}
?>
