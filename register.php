<?php
$page_title = "User Registration";
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_msg = "";
$success_msg = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $mobile           = trim($_POST['mobile'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_captcha     = trim($_POST['captcha'] ?? '');
    $sess_captcha     = $_SESSION['captcha'] ?? '';

    // Verify CAPTCHA first
    if (empty($user_captcha) || empty($sess_captcha) || strcasecmp($user_captcha, $sess_captcha) !== 0) {
        $error_msg = "Invalid CAPTCHA.";
    } elseif (empty($full_name) || empty($email) || empty($mobile) || empty($password)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (strlen($mobile) < 10) {
        $error_msg = "Please enter a valid 10-digit mobile number.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_msg = "An account with this email already exists.";
            } else {
                mysqli_stmt_close($stmt);

                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insert_sql = "INSERT INTO users (full_name, email, mobile, password) VALUES (?, ?, ?, ?)";
                
                if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                    mysqli_stmt_bind_param($insert_stmt, "ssss", $full_name, $email, $mobile, $hashed_password);
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $success_msg = "Registration successful! You can now log in with your credentials.";
                    } else {
                        $error_msg = "Registration failed. Please try again.";
                    }
                    mysqli_stmt_close($insert_stmt);
                } else {
                    $error_msg = "Database query error.";
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <h2 class="auth-title">Create an Account</h2>
        <p class="auth-subtitle">Join WatchStore today</p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_msg); ?>
                <div style="margin-top: 10px;">
                    <a href="login.php" style="font-weight: bold; color: #166534; text-decoration: underline;">Click here to Login</a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="e.g. rahul@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input type="tel" name="mobile" id="mobile" class="form-control" placeholder="e.g. 9876543210" value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
            </div>

            <div class="form-group">
                <label for="captcha">Security Code</label>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <img src="captcha.php?v=<?php echo time(); ?>" id="reg-captcha-img" alt="CAPTCHA" style="border: 1px solid #E8DFD5; border-radius: 5px; height: 36px; vertical-align: middle;">
                    <a href="javascript:void(0);" onclick="document.getElementById('reg-captcha-img').src='captcha.php?v='+Date.now();" style="font-size: 13px; color: #7A5645; text-decoration: none; font-weight: 500;" title="Get a new CAPTCHA code">🔄 Refresh</a>
                </div>
                <input type="text" name="captcha" id="captcha" class="form-control" placeholder="Enter CAPTCHA code" required autocomplete="off">
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
