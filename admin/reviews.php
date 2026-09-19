<?php
$page_title = "Customer Reviews";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/header.php';

// Handle flash messages
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Fetch all reviews with product and user details
$reviews = [];
$query = "SELECT r.id, r.rating, r.comment, r.created_at,
                 p.id AS product_id, p.name AS product_name, p.image AS product_image,
                 u.full_name AS user_name, u.email AS user_email
          FROM reviews r
          JOIN products p ON r.product_id = p.id
          JOIN users u ON r.user_id = u.user_id
          ORDER BY r.created_at DESC, r.id DESC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
}

// Star display helper
function adminStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rating) ? '★' : '☆';
    }
    return $stars;
}
?>

<div class="admin-page-header">
    <div>
        <h1>Customer Reviews</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">View and manage customer reviews and star ratings for watches.</p>
    </div>
</div>

<?php if ($msg === 'deleted'): ?>
    <div class="alert alert-success">✅ Review has been deleted successfully.</div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 220px;">Product</th>
                <th style="width: 180px;">User</th>
                <th style="width: 120px;">Rating</th>
                <th>Feedback / Comment</th>
                <th style="width: 130px;">Date</th>
                <th style="width: 80px; text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $rev): ?>
                    <tr>
                        <td>#<?php echo $rev['id']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="../<?php echo htmlspecialchars($rev['product_image']); ?>" alt="watch" class="thumb" onerror="this.onerror=null; this.src='https://via.placeholder.com/50';">
                                <div>
                                    <a href="../product-details.php?id=<?php echo $rev['product_id']; ?>" target="_blank" style="color: #0284c7; font-weight: 600; text-decoration: none;">
                                        <?php echo htmlspecialchars($rev['product_name']); ?>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($rev['user_name']); ?></strong>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                <?php echo htmlspecialchars($rev['user_email']); ?>
                            </div>
                        </td>
                        <td>
                            <span style="color: #eab308; font-size: 15px; letter-spacing: 1px;">
                                <?php echo adminStars($rev['rating']); ?>
                            </span>
                            <span style="font-size: 12px; font-weight: 600; color: #475569; margin-left: 4px;">
                                (<?php echo $rev['rating']; ?>/5)
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 13.5px; color: #334155; line-height: 1.4;">
                                "<?php echo htmlspecialchars($rev['comment']); ?>"
                            </div>
                        </td>
                        <td style="font-size: 13px; color: #64748b;">
                            <?php echo date('d M Y, h:i A', strtotime($rev['created_at'])); ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="review_delete.php?id=<?php echo $rev['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this review?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 35px;">
                        No customer reviews found in the database.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
