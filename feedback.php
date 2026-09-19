<?php
$page_title = "Feedback";
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_msg = "";
$success_msg = "";

// Auto-fill for logged-in user
$default_name  = $_SESSION['user_name'] ?? '';
$default_email = $_SESSION['user_email'] ?? '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO feedback (name, email, message) VALUES (?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Thank you! Your feedback has been submitted successfully.";
            } else {
                $error_msg = "Failed to submit feedback. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Database error. Please try again.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper" style="max-width: 550px;">
        <h2 class="auth-title">Customer Feedback</h2>
        <p class="auth-subtitle">We would love to hear your thoughts and suggestions</p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <form method="POST" action="feedback.php">
            <div class="form-group">
                <label for="name">Your Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? $default_name); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address" value="<?php echo htmlspecialchars($_POST['email'] ?? $default_email); ?>" required>
            </div>

            <div class="form-group">
                <label for="message">Feedback / Message</label>
                <textarea name="message" id="message" rows="5" class="form-control" placeholder="Write your message here..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn">Submit Feedback</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
