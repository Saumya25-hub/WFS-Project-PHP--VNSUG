<?php
/**
 * Simple Gregwar CAPTCHA Generator
 * WatchStore - BCA Semester 5 WFS Project
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';
use Gregwar\Captcha\CaptchaBuilder;

// Initialize and build CAPTCHA image
$builder = new CaptchaBuilder();
$builder->build(130, 36);

// Store the CAPTCHA text in PHP session
$_SESSION['captcha'] = $builder->getPhrase();

// Set anti-caching headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: image/jpeg');

// Output the image stream
$builder->output();
