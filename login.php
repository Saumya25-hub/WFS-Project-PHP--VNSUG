<?php
$page_title = "User Login";
require_once __DIR__ . '/config/db.php';

// If already logged in as user, redirect to dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $user_captcha = trim($_POST['captcha'] ?? '');
    $sess_captcha = $_SESSION['captcha'] ?? '';

    // Verify CAPTCHA first
    if (empty($user_captcha) || empty($sess_captcha) || strcasecmp($user_captcha, $sess_captcha) !== 0) {
        $error_msg = "Invalid CAPTCHA.";
    } elseif (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        $sql = "SELECT user_id, full_name, email, mobile, password FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row['password'])) {
                    // Password is correct, initialize session
                    $_SESSION['user_id']    = $row['user_id'];
                    $_SESSION['user_name']  = $row['full_name'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_mobile']= $row['mobile'];

                    // Redirect to intended page (e.g. cart/checkout) or user dashboard
                    $redirect_target = $_SESSION['login_redirect'] ?? 'dashboard.php';
                    unset($_SESSION['login_redirect']);
                    unset($_SESSION['login_notice']);
                    header("Location: " . $redirect_target);
                    exit();
                } else {
                    $error_msg = "Invalid email or password.";
                }
            } else {
                $error_msg = "Invalid email or password.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Database query error.";
        }
    }
}

$notice_msg = $_SESSION['login_notice'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <h2 class="auth-title">User Login</h2>
        <p class="auth-subtitle">Sign in to your WatchStore account</p>

        <?php if (!empty($notice_msg)): ?>
            <div class="alert alert-info" style="background-color: #F5EFEB; color: #4B2E2A; border: 1px solid #E8DFD5; padding: 11px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 13.5px;">
                ℹ️ <?php echo htmlspecialchars($notice_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="e.g. user@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
            </div>

            <div class="form-group">
                <label for="captcha">Security Code</label>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <img src="captcha.php?v=<?php echo time(); ?>" id="login-captcha-img" alt="CAPTCHA" style="border: 1px solid #E8DFD5; border-radius: 5px; height: 36px; vertical-align: middle;">
                    <a href="javascript:void(0);" onclick="document.getElementById('login-captcha-img').src='captcha.php?v='+Date.now();" style="font-size: 13px; color: #7A5645; text-decoration: none; font-weight: 500;" title="Get a new CAPTCHA code">🔄 Refresh</a>
                </div>
                <input type="text" name="captcha" id="captcha" class="form-control" placeholder="Enter CAPTCHA code" required autocomplete="off">
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
