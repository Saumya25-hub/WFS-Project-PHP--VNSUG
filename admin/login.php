<?php
$page_title = "Admin Login";
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password          = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $error_msg = "Please enter both username/email and password.";
    } else {
        $sql = "SELECT admin_id, username, email, password FROM admins WHERE username = ? OR email = ? LIMIT 1";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['admin_id']       = $row['admin_id'];
                    $_SESSION['admin_username'] = $row['username'];
                    $_SESSION['admin_email']    = $row['email'];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_msg = "Invalid admin credentials.";
                }
            } else {
                $error_msg = "Admin user not found.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Database query error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - WatchStore</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">

<div class="admin-login-wrapper">
    <div class="admin-login-box">
        <h2>⌚ Watch<span>Store</span></h2>
        <p class="subtitle">Administrator Control Panel</p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username_or_email">Username or Email</label>
                <input type="text" name="username_or_email" id="username_or_email" class="form-control" placeholder="e.g. admin or admin@watches.com" value="<?php echo isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email']) : 'admin'; ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter admin password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 15px;">Login to Admin</button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 15px;">
            <a href="../index.php" style="color: #0284c7; text-decoration: none;">&larr; Return to Customer Store</a>
        </div>
    </div>
</div>

</body>
</html>
