<?php
/**
 * Razorpay Test Mode Configuration & Helpers
 * For WatchStore College Project / Demo Use Only
 * Strictly Test Mode - No Live Mode credentials
 */

// Prevent direct web access
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit("Direct access not permitted.");
}

$razorpay_key_id = '';
$razorpay_key_secret = '';

// Dynamically locate existing API KEY directory under C:/xampp/htdocs/API KEY/
$base_htdocs = dirname(__DIR__, 2); // C:/xampp/htdocs
$csv_file = $base_htdocs . '/API KEY/rzp-key.csv';
$txt_file = $base_htdocs . '/API KEY/key_id.txt';

if (file_exists($csv_file) && is_readable($csv_file)) {
    if (($handle = fopen($csv_file, 'r')) !== false) {
        $header = fgetcsv($handle);
        $row = fgetcsv($handle);
        if ($row && count($row) >= 2) {
            $razorpay_key_id = trim($row[0]);
            $razorpay_key_secret = trim($row[1]);
        }
        fclose($handle);
    }
}

// Fallback to text file if CSV was empty or not found
if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
    if (file_exists($txt_file) && is_readable($txt_file)) {
        $lines = file($txt_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $i => $line) {
            $line_clean = trim($line);
            if (stripos($line_clean, 'key_id') !== false && isset($lines[$i + 1])) {
                $razorpay_key_id = trim($lines[$i + 1]);
            }
            if (stripos($line_clean, 'key_secret') !== false && isset($lines[$i + 1])) {
                $razorpay_key_secret = trim($lines[$i + 1]);
            }
        }
    }
}

// Strict safety check: Must be a Test Mode key starting with rzp_test_
if (!str_starts_with($razorpay_key_id, 'rzp_test_')) {
    die("Security Error: Razorpay integration must use Test Mode credentials only (key must start with 'rzp_test_').");
}

if (!defined('RAZORPAY_KEY_ID')) {
    define('RAZORPAY_KEY_ID', $razorpay_key_id);
}
if (!defined('RAZORPAY_KEY_SECRET')) {
    define('RAZORPAY_KEY_SECRET', $razorpay_key_secret);
}
if (!defined('RAZORPAY_CURRENCY')) {
    define('RAZORPAY_CURRENCY', 'INR');
}

/**
 * Creates an order on Razorpay servers via cURL
 *
 * @param int $amountInPaise Amount in paise (e.g. 50000 for ₹500.00)
 * @param string $receipt Order receipt identifier
 * @return array ['success' => bool, 'order_id' => string|null, 'error' => string|null, 'data' => array]
 */
function createRazorpayOrder($amountInPaise, $receipt) {
    $url = 'https://api.razorpay.com/v1/orders';
    $payload = [
        'amount' => (int)$amountInPaise,
        'currency' => RAZORPAY_CURRENCY,
        'receipt' => (string)$receipt,
        'payment_capture' => 1
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return [
            'success' => false,
            'order_id' => null,
            'error' => 'cURL connection error: ' . $curl_err,
            'data' => null
        ];
    }

    $data = json_decode($response, true);
    if ($http_code === 200 && isset($data['id'])) {
        return [
            'success' => true,
            'order_id' => $data['id'],
            'error' => null,
            'data' => $data
        ];
    }

    $err_desc = $data['error']['description'] ?? 'Unable to create Razorpay order.';
    return [
        'success' => false,
        'order_id' => null,
        'error' => $err_desc . ' (HTTP ' . $http_code . ')',
        'data' => $data
    ];
}

/**
 * Verifies the Razorpay payment signature server-side using HMAC SHA256
 *
 * @param string $razorpayOrderId
 * @param string $razorpayPaymentId
 * @param string $razorpaySignature
 * @return bool True if valid, false otherwise
 */
function verifyRazorpaySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature) {
    if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
        return false;
    }

    $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expectedSignature, $razorpaySignature);
}
