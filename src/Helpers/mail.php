<?php
/**
 * mail.php — PHPMailer helper for sending transactional emails.
 * 
 * Usage: sendOtpEmail('user@example.com', '123456');
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Attempt to load PHPMailer
// 1. Check vendor/autoload.php (Composer)
// 2. Check src/Lib/PHPMailer (Manual include)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../Lib/PHPMailer/src/Exception.php')) {
    require_once __DIR__ . '/../Lib/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../Lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../Lib/PHPMailer/src/SMTP.php';
}

/**
 * Send an OTP email to the user.
 */
function sendOtpEmail(string $toEmail, string $otp): bool
{
    // Ensure PHPMailer classes are available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback to manual require if not autoloaded
        $libPath = __DIR__ . '/../Lib/PHPMailer/src/';
        if (file_exists($libPath . 'PHPMailer.php')) {
            require_once $libPath . 'Exception.php';
            require_once $libPath . 'PHPMailer.php';
            require_once $libPath . 'SMTP.php';
        } else {
            error_log("PHPMailer not found at: " . $libPath);
            return false;
        }
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP — ' . APP_NAME;
        
        $body = "
            <div style='font-family: sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #13532C;'>Password Reset Request</h2>
                <p>Hello,</p>
                <p>We received a request to reset your password. Use the code below to proceed. This code is valid for 15 minutes.</p>
                <div style='background: #f4fdf7; padding: 15px; text-align: center; border-radius: 8px;'>
                    <span style='font-size: 32px; font-weight: 800; color: #13532C; letter-spacing: 5px;'>$otp</span>
                </div>
                <p style='font-size: 13px; color: #666; margin-top: 20px;'>If you didn't request this, you can safely ignore this email.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 11px; color: #999; text-align: center;'>© " . date('Y') . " " . APP_NAME . ". All rights reserved.</p>
            </div>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Your password reset OTP is: $otp. It expires in 15 minutes.";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
