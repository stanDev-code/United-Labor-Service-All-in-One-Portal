<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        logSecurityEvent('CSRF_TOKEN_INVALID', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } else {
        $result = attemptRegister($_POST['username'] ?? '', $_POST['password'] ?? '', $_POST['password_confirm'] ?? '', $_POST['full_name'] ?? '', $_POST['email'] ?? '');
        if ($result['success']) $success = $result['message'];
        else $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | United Labor Service Cooperative</title>
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

        .register-container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
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

        .register-left {
            flex: 1;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
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

        .register-left::before {
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

        .register-left-content {
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

        .register-benefits {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }

        .register-benefits li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .register-benefits i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .register-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .register-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .register-header p {
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

        .btn-register {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .register-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .register-footer p {
            color: #6b7280;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .register-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-footer a:hover {
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

        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        @media (max-width: 991px) {
            .register-container {
                flex-direction: column;
                max-width: 500px;
            }

            .register-left {
                padding: 40px 30px;
            }

            .cooperative-title {
                font-size: 2rem;
            }

            .register-right {
                padding: 40px 30px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .register-left, .register-right {
                padding: 30px 20px;
            }

            .cooperative-title {
                font-size: 1.5rem;
            }

            .register-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-left">
            <div class="register-left-content">
                <div class="cooperative-logo">
                    <img src="United Logo 3.png" alt="United Labor Service Cooperative" style="width: 90px; height: 90px; object-fit: cover; border-radius: 50%;">
                </div>
                <h1 class="cooperative-title">United Labor Service Cooperative</h1>
                <p class="cooperative-subtitle">Staff Registration Portal</p>
                <ul class="register-benefits">
                    <li><i class="bi bi-shield-check"></i> Secure Account Access</li>
                    <li><i class="bi bi-clipboard-check"></i> Equipment Management</li>
                    <li><i class="bi bi-tools"></i> Maintenance Tracking</li>
                    <li><i class="bi bi-graph-up"></i> Performance Analytics</li>
                    <li><i class="bi bi-award"></i> Professional Development</li>
                </ul>
            </div>
        </div>
        <div class="register-right">
            <div class="register-header">
                <h2>Create Account</h2>
                <p>Join our cooperative team</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <div class="text-center mb-4">
                    <a href="login.php" class="btn btn-success btn-lg btn-glow">Go to Login</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                        <label for="full_name"><i class="bi bi-person-badge me-2"></i>Full Name</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <label for="email"><i class="bi bi-envelope me-2"></i>Email (Optional)</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required minlength="8">
                        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm Password" required minlength="8">
                        <label for="password_confirm"><i class="bi bi-lock-fill me-2"></i>Confirm Password</label>
                    </div>
                    
                    <button type="submit" class="btn btn-register btn-lg btn-glow">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </form>
                
                <div class="register-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
