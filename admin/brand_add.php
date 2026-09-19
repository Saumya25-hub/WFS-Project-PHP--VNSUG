<?php
$page_title = "Add Brand";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name      = trim($_POST['name'] ?? '');
    $logo_path = trim($_POST['logo_path'] ?? '');

    if (empty($name)) {
        $error_msg = "Please enter a brand name.";
    } else {
        // Check if brand already exists
        $chk = mysqli_prepare($conn, "SELECT id FROM brands WHERE name = ?");
        mysqli_stmt_bind_param($chk, "s", $name);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error_msg = "A brand named '" . htmlspecialchars($name) . "' already exists.";
            mysqli_stmt_close($chk);
        } else {
            mysqli_stmt_close($chk);

            // Handle file upload
            if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['brand_logo']['tmp_name'];
                $orig_name = basename($_FILES['brand_logo']['name']);
                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                $allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

                if (in_array($ext, $allowed)) {
                    $clean_name = strtolower(trim($name));
                    $clean_name = str_replace([' ', '&', '.'], ['-', '', ''], $clean_name);
                    $dest_filename = $clean_name . '.' . $ext;
                    $dest_dir = __DIR__ . '/../images/brands/';
                    if (!is_dir($dest_dir)) {
                        mkdir($dest_dir, 0777, true);
                    }
                    if (move_uploaded_file($tmp_name, $dest_dir . $dest_filename)) {
                        $logo_path = 'images/brands/' . $dest_filename;
                    }
                }
            }

            // Fallback default logo if none provided
            if (empty($logo_path)) {
                $logo_path = 'images/brands/all-brands.png';
            }

            // Insert into brands table
            $ins = mysqli_prepare($conn, "INSERT INTO brands (name, logo) VALUES (?, ?)");
            if ($ins) {
                mysqli_stmt_bind_param($ins, "ss", $name, $logo_path);
                if (mysqli_stmt_execute($ins)) {
                    mysqli_stmt_close($ins);
                    header("Location: brands.php?msg=added");
                    exit();
                } else {
                    $error_msg = "Failed to add brand: " . mysqli_stmt_error($ins);
                }
                mysqli_stmt_close($ins);
            } else {
                $error_msg = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1>Add New Brand</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Register a new watch brand and upload its official logo.</p>
    </div>
    <a href="brands.php" class="btn btn-secondary">&larr; Back to Brands</a>
</div>

<div class="form-card">
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="brand_add.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Brand Name *</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Omega, Rolex, Hublot" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="brand_logo">Brand Logo Image (Upload file)</label>
            <input type="file" name="brand_logo" id="brand_logo" class="form-control" accept="image/*">
            <small style="color: #64748b; font-size: 12px;">Recommended: Transparent PNG or SVG logo.</small>
        </div>

        <div class="form-group">
            <label for="logo_path">Or Existing Logo Path</label>
            <input type="text" name="logo_path" id="logo_path" class="form-control" placeholder="e.g. images/brands/custom.png" value="<?php echo htmlspecialchars($_POST['logo_path'] ?? ''); ?>">
            <small style="color: #64748b; font-size: 12px;">Leave empty if uploading a file, or enter path.</small>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Create Brand</button>
            <a href="brands.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
