<?php
/**
 * Database Configuration & Connection File
 * Project: Online Watches Website (BCA Sem 5 WFS Project - Week 1)
 * Technology: PHP 8.x + MySQL / MariaDB (XAMPP)
 */

// Define database connection constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'watches_db');

// Establish MySQL database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if connection failed
if (!$conn) {
    die("Database Connection Error: " . mysqli_connect_error() . "<br>Please ensure MySQL service is running in XAMPP control panel and 'watches_db' is created/imported.");
}

// Set character set to utf8mb4 for full Unicode support
mysqli_set_charset($conn, "utf8mb4");

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

/**
 * Helper: Get product average star rating and review count
 */
if (!function_exists('getProductRatingData')) {
    function getProductRatingData($product_id) {
        global $conn;
        $product_id = intval($product_id);
        if (isset($conn) && $conn) {
            $q = mysqli_query($conn, "SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE product_id = $product_id");
            if ($q && $row = mysqli_fetch_assoc($q)) {
                if ($row['cnt'] > 0) {
                    return [
                        'rating' => round(floatval($row['avg_r']), 1),
                        'count'  => intval($row['cnt'])
                    ];
                }
            }
        }
        // Fallback rating so all cards show stars immediately
        $fallbacks = [
            1  => [5.0, 1],
            2  => [4.0, 1],
            3  => [4.8, 18],
            4  => [4.9, 26],
            5  => [4.7, 15],
            6  => [4.6, 12],
            7  => [4.8, 22],
            8  => [4.5, 14],
            9  => [4.8, 19],
            10 => [4.7, 11],
            11 => [4.6, 9],
            12 => [4.9, 31],
        ];
        $fb = $fallbacks[$product_id] ?? [4.8, 15];
        return [
            'rating' => $fb[0],
            'count'  => $fb[1]
        ];
    }
}
?>
