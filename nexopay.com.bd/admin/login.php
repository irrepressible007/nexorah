<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            $query = "SELECT * FROM admin_users WHERE username = :username AND is_active = 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['login_time'] = time();
                
                // Update last login
                $update_query = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':id', $admin['id']);
                $update_stmt->execute();
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = "Invalid username or password.";
                error_log("Failed login attempt for username: " . $username);
            }
        } catch (Exception $e) {
            $error_message = "Login system error. Please contact administrator.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login â€“ Nexopay</title>
    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --surface: rgba(255, 255, 255, 0.9);
            --surface-border: rgba(255, 255, 255, 0.5);
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Premium gradient background */
            background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 50%, #f3e8ff 100%);
            color: var(--text-dark);
            position: relative;
            overflow: hidden;
        }

        /* Decorative Background Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: float 20s infinite ease-in-out alternate;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: rgba(99, 102, 241, 0.4);
            top: -100px; left: -100px;
        }
        .orb-2 {
            width: 500px; height: 500px;
            background: rgba(168, 85, 247, 0.3);
            bottom: -150px; right: -100px;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 50px) scale(1.1); }
        }

        .login-wrap {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: var(--surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--surface-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.05);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--primary), #a855f7);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.8rem;
            margin: 0 auto 16px;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            transform: rotate(-5deg);
        }

        .brand-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .brand-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 24px;
            border: 1px solid #fecaca;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            margin-left: 2px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 16px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 44px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            outline: none;
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .form-control:focus + i {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.4);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 32px;
            text-align: center;
            display: flex; flex-direction: column; gap: 12px;
        }

        .login-footer a {
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-footer a:hover {
            color: var(--primary);
        }

        .login-footer .divider {
            height: 1px;
            background: #e2e8f0;
            width: 100%;
            margin: 8px 0;
        }

        @media (max-width: 480px) {
            .login-card { padding: 30px 24px; }
            .login-wrap { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="login-wrap">
        <div class="login-card">
            <div class="brand-header">
                <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
                <h1>Nexopay Admin</h1>
                <p>Welcome back! Please enter your details.</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Administrator Username</label>
                    <div class="input-wrap">
                        <input type="text" id="username" name="username" class="form-control" autocomplete="username" placeholder="e.g. admin" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    Sign In <i class="fas fa-arrow-right" style="font-size: 0.85rem;"></i>
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php"><i class="fas fa-home" style="margin-right:4px;"></i> Return to Website</a>
                <div class="divider"></div>
                <a href="../admin_setup.php" style="font-weight:500; font-size: 0.82rem;"><i class="fas fa-tools" style="margin-right:4px;"></i> Server Setup or Password Reset</a>
            </div>
        </div>
    </div>
</body>
</html>
