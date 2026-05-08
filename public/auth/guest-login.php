<?php
require_once __DIR__ . '/../../config/db.php';

$_SESSION['is_guest'] = true;
$_SESSION['role'] = 'farmer'; // Give them a base role for layout compatibility
$_SESSION['full_name'] = 'Guest User';
$_SESSION['user_id'] = 0; // Use 0 to indicate no real user

setFlash('info', 'Welcome! You are now in Demo Mode. Some actions like booking and listing are disabled.');
header('Location: ' . getBasePath() . '/public/dashboard.php');
exit();
