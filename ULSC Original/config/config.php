<?php
/**
 * environment configuration
 * set environment-specific settings
 */

// environment: 'development' or 'production'
define('ENVIRONMENT', 'development');

// security settings
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 300); // 5 minutes
define('MAX_REGISTER_ATTEMPTS', 3);
define('REGISTER_ATTEMPT_WINDOW', 600); // 10 minutes

// file upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'text/plain',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]);

// email settings (for future use)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@ulsc.com');
define('SMTP_FROM_NAME', 'ULSC Inventory System');

// system settings
define('SYSTEM_NAME', 'ULSC Inventory Management System');
define('SYSTEM_VERSION', '1.0.0');
define('SUPPORT_EMAIL', 'admin@ulsc.com');

// password policy
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// logging settings
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('MAX_LOG_SIZE', 10485760); // 10MB
define('LOG_RETENTION_DAYS', 30);

// api settings (for future use)
define('API_RATE_LIMIT', 100); // requests per hour
define('API_TIMEOUT', 30); // seconds

// cache settings (for future use)
define('CACHE_ENABLED', false);
define('CACHE_TTL', 3600); // 1 hour

// backup settings
define('BACKUP_ENABLED', true);
define('BACKUP_SCHEDULE', 'daily'); // daily, weekly, monthly
define('BACKUP_RETENTION_DAYS', 30);

// maintenance mode
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'System is currently under maintenance. Please try again later.');

// feature flags
define('FEATURE_EMAIL_NOTIFICATIONS', false);
define('FEATURE_FILE_UPLOADS', true);
define('FEATURE_REPORTS', true);
define('FEATURE_API', false);

// security headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options: nosniff',
    'X-Frame-Options: DENY',
    'X-XSS-Protection: "1; mode=block"',
    'Referrer-Policy: strict-origin-when-cross-origin',
    'Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; img-src \'self\' data:; font-src \'self\' https://cdn.jsdelivr.net;'
]);

// apply security headers
if (!headers_sent()) {
    foreach (SECURITY_HEADERS as $header) {
        header($header);
    }
}

// maintenance mode check
if (MAINTENANCE_MODE && !isset($_GET['bypass'])) {
    if (!isAdmin()) {
        http_response_code(503);
        echo '<!DOCTYPE html><html><head><title>Maintenance</title></head><body><h1>' . MAINTENANCE_MESSAGE . '</h1></body></html>';
        exit;
    }
}
?>
