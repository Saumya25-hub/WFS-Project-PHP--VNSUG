<?php
$base_urls = [
    'http://localhost/WFS-Project(PHP)/',
    'http://127.0.0.1:8000/'
];

$endpoints = [
    'index.php',
    'products.php',
    'products.php?category=Luxury+Watches',
    'products.php?search=Titan',
    'product-details.php?id=1',
    'product-details.php?id=5',
    'cart.php',
    'wishlist.php',
    'feedback.php',
    'login.php',
    'register.php'
];

echo "=== TESTING HTTP ENDPOINTS ===\n\n";

foreach ($base_urls as $base) {
    echo "Testing Base: $base\n";
    foreach ($endpoints as $ep) {
        $url = $base . $ep;
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        $content = @file_get_contents($url, false, $context);
        $status = $http_response_header[0] ?? 'UNKNOWN';
        
        $has_header = strpos($content, 'Watch<span>Store</span>') !== false;
        $has_footer = strpos($content, 'WatchStore — Online Watches Website') !== false;

        if (strpos($status, '200') !== false && $has_header) {
            echo "  [200 OK] $ep (Length: " . strlen($content) . " bytes)\n";
        } else {
            echo "  [FAIL] $ep - Status: $status\n";
        }
    }
    echo "\n";
}
