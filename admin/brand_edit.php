<?php
$page_title = "Edit Brand";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: brands.php?err=Invalid+Brand+ID");
    exit();
}

// Fetch current brand
$stmt = mysqli_prepare($conn, "SELECT * FROM brands WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$brand = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$brand) {
    header("Location: brands.php?err=Brand+not+found");
    exit();
}

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name      = trim($_POST['name'] ?? '');
    $logo_path = trim($_POST['logo_path'] ?? $brand['logo']);

    if (empty($name)) {
        $error_msg = "Brand name cannot be empty.";
    } else {
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

        // Update brand record
        $old_name = $brand['name'];
        $upd = mysqli_prepare($conn, "UPDATE brands SET name = ?, logo = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "ssi", $name, $logo_path, $id);
        if (mysqli_stmt_execute($upd)) {
            mysqli_stmt_close($upd);

            // If brand name changed, cascade update to products table
            if ($old_name !== $name) {
                $sync = mysqli_prepare($conn, "UPDATE products SET brand = ? WHERE brand = ?");
                mysqli_stmt_bind_param($sync, "ss", $name, $old_name);
                mysqli_stmt_execute($sync);
                mysqli_stmt_close($sync);
            }

            header("Location: brands.php?msg=updated");
            exit();
        } else {
            $error_msg = "Failed to update brand: " . mysqli_stmt_error($upd);
            mysqli_stmt_close($upd);
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1>Edit Brand: <?php echo htmlspecialchars($brand['name']); ?></h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Update brand name or change its official logo.</p>
    </div>
    <a href="brands.php" class="btn btn-secondary">&larr; Back to Brands</a>
</div>

<div class="form-card">
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="brand_edit.php?id=<?php echo $brand['id']; ?>" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $brand['id']; ?>">

        <div class="form-group">
            <label for="name">Brand Name *</label>
            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? $brand['name']); ?>" required>
            <small style="color: #64748b; font-size: 12px;">Renaming this brand will automatically update existing watches associated with it.</small>
        </div>

        <div class="form-group">
            <label>Current Logo Preview</label>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 8px;">
                <img src="../<?php echo htmlspecialchars($brand['logo']); ?>" alt="Brand Logo" class="brand-thumb" style="max-height: 55px;" onerror="this.onerror=null; this.src='../images/brands/all-brands.png';">
                <span style="font-size: 13px; color: #64748b;"><code><?php echo htmlspecialchars($brand['logo']); ?></code></span>
            </div>
        </div>

        <div class="form-group">
            <label for="brand_logo">Upload New Logo (Optional)</label>
            <input type="file" name="brand_logo" id="brand_logo" class="form-control" accept="image/*">
            <small style="color: #64748b; font-size: 12px;">Leave blank to keep existing logo.</small>
        </div>

        <div class="form-group">
            <label for="logo_path">Or Change Logo Path</label>
            <input type="text" name="logo_path" id="logo_path" class="form-control" value="<?php echo htmlspecialchars($_POST['logo_path'] ?? $brand['logo']); ?>">
        </div>

        <div style="margin-top: 25px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Update Brand</button>
            <a href="brands.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
