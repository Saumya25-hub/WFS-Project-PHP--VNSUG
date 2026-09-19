<?php
$page_title = "Manage Brands";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/header.php';

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Fetch all brands and count of products linked to each
$brands = [];
$query = "SELECT b.id, b.name, b.logo, b.created_at, COUNT(p.id) as total_products 
          FROM brands b 
          LEFT JOIN products p ON b.name = p.brand 
          GROUP BY b.id, b.name, b.logo, b.created_at 
          ORDER BY b.name ASC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $brands[] = $row;
    }
}
?>

<div class="admin-page-header">
    <div>
        <h1>Brand Management</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Manage brand names and logos displayed across the store, brand carousels, and brand pages.</p>
    </div>
    <a href="brand_add.php" class="btn btn-primary">+ Add New Brand</a>
</div>

<?php if ($msg === 'added'): ?>
    <div class="alert alert-success">✅ Brand added successfully! It is now active on the store.</div>
<?php elseif ($msg === 'updated'): ?>
    <div class="alert alert-success">✅ Brand details updated successfully!</div>
<?php elseif ($msg === 'deleted'): ?>
    <div class="alert alert-success">✅ Brand deleted successfully.</div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th style="width: 120px;">Brand Logo</th>
                <th>Brand Name</th>
                <th>Linked Products</th>
                <th>Created Date</th>
                <th style="width: 140px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($brands)): ?>
                <?php foreach ($brands as $b): ?>
                    <tr>
                        <td>#<?php echo $b['id']; ?></td>
                        <td>
                            <img src="../<?php echo htmlspecialchars($b['logo']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?>" class="brand-thumb" onerror="this.onerror=null; this.src='../images/brands/all-brands.png';">
                        </td>
                        <td>
                            <strong style="font-size: 15px; color: #0f172a;"><?php echo htmlspecialchars($b['name']); ?></strong>
                        </td>
                        <td>
                            <a href="../brand.php?brand=<?php echo urlencode($b['name']); ?>" target="_blank" style="text-decoration: none;">
                                <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 12px;">
                                    <?php echo $b['total_products']; ?> watches ↗
                                </span>
                            </a>
                        </td>
                        <td style="color: #64748b; font-size: 13px;">
                            <?php echo date('d M Y', strtotime($b['created_at'])); ?>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 6px; justify-content: center;">
                                <a href="brand_edit.php?id=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="brand_delete.php?id=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete brand \'<?php echo addslashes($b['name']); ?>\'?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 35px;">No brands found in database.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
