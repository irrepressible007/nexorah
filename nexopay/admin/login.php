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
    <title>Admin Login - Nexopay</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-form">
            <h2>Nexopay Admin</h2>
            <p>Please login to access the admin panel</p>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary btn-full">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Having trouble? <a href="../admin_setup.php">Run Admin Setup</a></p>
                <a href="../index.php">← Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>