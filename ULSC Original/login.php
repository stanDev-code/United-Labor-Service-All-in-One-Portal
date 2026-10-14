<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        logSecurityEvent('CSRF_TOKEN_INVALID', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } else {
        $result = attemptLogin($_POST['username'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) { 
            header('Location: index.php'); 
            exit; 
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | United Labor Service Cooperative</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #d1fae5;
            --secondary: #6b7280;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #fdfdfdff 0%, #b1b1b1ff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-left-content {
            position: relative;
            z-index: 1;
        }

        .cooperative-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .cooperative-logo i {
            font-size: 3rem;
            color: white;
        }

        .cooperative-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .cooperative-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .cooperative-features {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }

        .cooperative-features li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .cooperative-features i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-control {
            height: 60px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            background: white;
        }

        .form-floating label {
            color: #6b7280;
            padding: 1rem 1.25rem;
        }

        .form-floating .form-control:focus ~ label,
        .form-floating .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary);
        }

        .input-group-text {
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 12px 0 0 12px;
        }

        .btn-login {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .login-footer p {
            color: #6b7280;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        @media (max-width: 991px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }

            .login-left {
                padding: 30px 20px;
                min-height: auto;
            }

            .cooperative-title {
                font-size: 1.75rem;
                line-height: 1.1;
            }

            .cooperative-subtitle {
                font-size: 1rem;
            }

            .cooperative-logo {
                width: 80px;
                height: 80px;
                margin-bottom: 20px;
            }

            .cooperative-logo img {
                width: 60px;
                height: 60px;
            }

            .login-right {
                padding: 30px 20px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 5px;
            }

            .login-container {
                max-width: 100%;
                margin: 0;
            }

            .login-left, .login-right {
                padding: 25px 15px;
            }

            .cooperative-title {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }

            .cooperative-subtitle {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }

            .cooperative-logo {
                width: 70px;
                height: 70px;
                margin-bottom: 15px;
            }

            .cooperative-logo img {
                width: 50px;
                height: 50px;
            }

            .login-header h2 {
                font-size: 1.4rem;
                margin-bottom: 8px;
            }

            .login-header p {
                font-size: 0.9rem;
            }

            .form-control {
                height: 55px;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .btn-login {
                height: 55px;
                font-size: 1rem;
            }

            .login-footer {
                margin-top: 20px;
                padding-top: 15px;
            }

            .login-footer p {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 400px) {
            .cooperative-title {
                font-size: 1.3rem;
            }

            .login-left, .login-right {
                padding: 20px 12px;
            }

            .form-control {
                height: 50px;
                font-size: 16px;
            }

            .btn-login {
                height: 50px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-left-content">
                <div class="cooperative-logo">
                    <img src="United Logo 3.png" alt="United Labor Service Cooperative" style="width: 90px; height: 90px; object-fit: cover; border-radius: 20%;">
                </div>
                <h1 class="cooperative-title">United Labor Service Cooperative</h1>
                <p class="cooperative-subtitle">Inventory & Maintenance Management System</p>
    
            </div>
        </div>
        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your account</p>
            </div>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'timeout'): ?>
                <div class="alert alert-warning">Your session has expired due to inactivity. Please login again.</div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <label for="username"><i class="bi bi-person me-2"></i>Username or Email</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                </div>
                
                <button type="submit" class="btn btn-login btn-lg btn-glow">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            
            <div class="register-footer">
                <p>Admin access only. Contact system administrator for assistance.</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
