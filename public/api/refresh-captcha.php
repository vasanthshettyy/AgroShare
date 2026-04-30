<?php
/**
 * refresh-captcha.php — Generates a new CAPTCHA code and returns it as JSON.
 */
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$captcha_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_code  = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_code .= $captcha_chars[random_int(0, strlen($captcha_chars) - 1)];
}

$_SESSION['captcha_code'] = $captcha_code;

echo json_encode([
    'success' => true,
    'captcha' => $captcha_code
]);
