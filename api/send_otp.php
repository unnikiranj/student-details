<?php
/**
 * Send OTP to student email
 * POST: { roll_number, csrf_token }
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json');
}

try {
    require_once __DIR__ . '/../config.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Config error: ' . $e->getMessage()]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/otp_logger.php';

// Read JSON or POST input
$jsonInput = file_get_contents('php://input');
$postData = json_decode($jsonInput, true);

if (!is_array($postData)) {
    $postData = $_POST;
}

// Generate session token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$rollNumber = strtoupper(trim($postData['roll_number'] ?? ''));

if (empty($rollNumber)) {
    OtpLogger::logValidationError('roll_number', '', 'Roll number is required');
    echo json_encode(['success' => false, 'message' => 'Roll number is required']);
    exit;
}

// Validate roll number format
if (!preg_match('/^[0-9A-Za-z]+$/', $rollNumber)) {
    OtpLogger::logValidationError('roll_number', $rollNumber, 'Invalid roll number format');
    echo json_encode(['success' => false, 'message' => 'Invalid roll number format']);
    exit;
}

// Check if student exists
try {
    $stmt = $pdo->prepare("SELECT student_name FROM students WHERE roll_no IN (?, ?, ?) LIMIT 1");
    $stmt->execute([$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        // Also check student_results
        $stmtResults = $pdo->prepare("SELECT student_name FROM student_results WHERE roll_no IN (?, ?, ?) LIMIT 1");
        $stmtResults->execute([$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);
        $student = $stmtResults->fetch(PDO::FETCH_ASSOC);
    }

    if (!$student) {
        OtpLogger::logValidationError('roll_number', $rollNumber, 'Student not found in database');
        echo json_encode(['success' => false, 'message' => 'Student not found in college database']);
        exit;
    }
} catch (PDOException $e) {
    OtpLogger::log('ERROR', 'Database error while checking student', [
        'roll_number' => $rollNumber,
        'error' => $e->getMessage()
    ]);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Generate 6-digit OTP
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Store OTP in session with expiry (5 minutes)
$_SESSION['otp'] = $otp;
$_SESSION['otp_roll_number'] = $rollNumber;
$_SESSION['otp_expiry'] = time() + 300; // 5 minutes
$_SESSION['otp_attempts'] = 0;

// Log OTP generation
OtpLogger::logOtpGenerated($rollNumber, $_SESSION['otp_expiry']);

// Email destination
$to = strtolower($rollNumber) . '@pvpsit.ac.in';
$subject = 'PVPSIT Student Portal - OTP Verification';
$message = "
<html>
<head>
    <title>OTP Verification</title>
</head>
<body style='font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px;'>
    <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <h2 style='color: #4a5568; margin-top: 0;'>PVPSIT Student Portal</h2>
        <p style='color: #718096;'>Hello " . htmlspecialchars($student['student_name'] ?? 'Student') . ",</p>
        <p style='color: #718096;'>Your One-Time Password (OTP) for profile authentication is:</p>
        <div style='text-align: center; margin: 25px 0;'>
            <span style='color: #667eea; font-size: 36px; font-weight: bold; letter-spacing: 6px; background: #edf2f7; padding: 10px 20px; border-radius: 8px; display: inline-block;'>$otp</span>
        </div>
        <p style='color: #718096; font-size: 14px;'>This OTP is valid for <strong>5 minutes</strong>.</p>
        <p style='color: #a0aec0; font-size: 13px;'>If you did not request this OTP, please ignore this email.</p>
        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
        <p style='color: #a0aec0; font-size: 12px; text-align: center;'>This is an automated notification from PVPSIT Student Portal.</p>
    </div>
</body>
</html>
";

// Send email using PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings - Gmail SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'unnikiran@pvpsiddhartha.ac.in';
    $mail->Password = 'byxq jgii ievn nhgd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 5;

    // Recipients
    $mail->setFrom('unnikiran@pvpsiddhartha.ac.in', 'PVPSIT Student Portal');
    $mail->addAddress($to);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    $mail->send();
    OtpLogger::logOtpSent($rollNumber, $to);
    echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
} catch (Exception $e) {
    // Log email sending failure
    OtpLogger::logOtpSendFailed($rollNumber, $to, $mail->ErrorInfo ?? $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send OTP to your email. Please try again.'
    ]);
}
