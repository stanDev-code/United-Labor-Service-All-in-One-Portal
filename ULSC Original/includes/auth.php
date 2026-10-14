<?php
/**
 * authentication & authorization
 * Handles login, session, and role-based access control
 */ 

// Enhanced session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Regenerate session ID on login
if (!isset($_SESSION['session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = true;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

// Detect suspicious activity
$suspicious = detectSuspiciousActivity();
if (!empty($suspicious)) {
    logSecurityEvent('SUSPICIOUS_ACTIVITY', ['warnings' => $suspicious]);
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/login.php');
        exit;
    }
    
    // Check session timeout
    if (!checkSessionTimeout()) {
        header('Location: ' . getBaseUrl() . '/login.php?msg=timeout');
        exit;
    }
}

function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . getBaseUrl() . '/index.php?error=unauthorized');
        exit;
    }
}

function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $path = rtrim(str_replace('\\', '/', $script), '/');
    return $protocol . '://' . $host . ($path ?: '');
}

function attemptLogin(string $username, string $password): array {
    $pdo = getDBConnection();
    $username = trim($username);
    $password = trim($password);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Input validation
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username/Email and password are required.'];
    }
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'message' => 'Invalid username/email format.'];
    }
    
    if (strlen($password) > 255) {
        return ['success' => false, 'message' => 'Invalid password format.'];
    }

    // Rate limiting
    $rateLimit = checkRateLimit($ip . '_' . $username);
    if (!$rateLimit['allowed']) {
        logSecurityEvent('LOGIN_RATE_LIMIT_EXCEEDED', [
            'username' => $username, 
            'ip' => $ip,
            'attempts' => $rateLimit['attempts']
        ]);
        return ['success' => false, 'message' => 'Too many login attempts. Please try again in ' . $rateLimit['reset_time'] . ' seconds.'];
    }

    try {
        // Check if input is email format
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        if ($isEmail) {
            // Login with email
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role, is_active FROM users WHERE email = ? AND is_active = 1");
        } else {
            // Login with username
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role, is_active FROM users WHERE username = ? AND is_active = 1");
        }
        
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            logSecurityEvent('LOGIN_FAILED_USER_NOT_FOUND', ['username' => $username, 'ip' => $ip]);
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }
        
        if (!$user['is_active']) {
            logSecurityEvent('LOGIN_FAILED_INACTIVE_USER', ['username' => $username, 'ip' => $ip]);
            return ['success' => false, 'message' => 'Account is deactivated.'];
        }
        
        if (!password_verify($password, $user['password'])) {
            logSecurityEvent('LOGIN_FAILED_INVALID_PASSWORD', ['username' => $username, 'ip' => $ip]);
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }

        // Successful login - regenerate session ID
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        logSecurityEvent('LOGIN_SUCCESS', ['username' => $user['username'], 'ip' => $ip]);
        
        return ['success' => true, 'message' => 'Login successful.'];
        
    } catch (PDOException $e) {
        logSecurityEvent('LOGIN_DATABASE_ERROR', ['username' => $username, 'ip' => $ip, 'error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'System error. Please try again later.'];
    }
}

function logout(): void {
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    logSecurityEvent('LOGOUT', ['user_id' => $userId, 'ip' => $ip]);
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, 
            $params["path"], $params["domain"], 
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Check session timeout
function checkSessionTimeout(): bool {
    $timeout = 3600; // 1 hour
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        logout();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function attemptRegister(string $username, string $password, string $password_confirm, string $full_name, string $email = ''): array {
    // Registration disabled - only admin users can be created directly in database
    logSecurityEvent('REGISTER_ATTEMPT_DISABLED', [
        'username' => $username, 
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    return ['success' => false, 'message' => 'User registration is disabled. Contact system administrator.'];
}
