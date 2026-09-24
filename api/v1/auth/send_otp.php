<?php
/**
 * REST API v1: Send OTP to student email
 * POST /api/v1/auth/send_otp.php
 * Body (JSON): { "roll_number": "24501A1201" }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../otp_logger.php';
global $pdo;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

ApiResponse::initCors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed. Use POST.', 405);
}

$input = ApiResponse::getJsonInput();
$rawRoll = $input['roll_number'] 
    ?? $input['rollNumber'] 
    ?? $input['roll_no'] 
    ?? $input['rollNo'] 
    ?? $input['roll'] 
    ?? $_GET['roll_number'] 
    ?? $_GET['roll'] 
    ?? '';

$rollNumber = strtoupper(trim((string)$rawRoll));

if (empty($rollNumber)) {
    ApiResponse::error('Roll number is required', 422, ['field' => 'roll_number']);
}

if (!preg_match('/^[0-9A-Za-z]+$/', $rollNumber)) {
    ApiResponse::error('Invalid roll number format', 422, ['field' => 'roll_number']);
}

// Verify student existence in database
try {
    $stmt = $pdo->prepare("SELECT roll_no, student_name, department, email FROM students WHERE roll_no IN (?, ?, ?) LIMIT 1");
    $stmt->execute([$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        // Fallback check in student_results
        $stmtResults = $pdo->prepare("SELECT roll_no, student_name, department FROM student_results WHERE roll_no IN (?, ?, ?) LIMIT 1");
        $stmtResults->execute([$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);
        $student = $stmtResults->fetch(PDO::FETCH_ASSOC);
    }

    if (!$student) {
        OtpLogger::logValidationError('roll_number', $rollNumber, 'Student not found in database');
        ApiResponse::error('Student roll number not found in college records', 404);
    }
} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}

// Generate 6-digit OTP
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Store OTP in database (Stateless for Android/Retrofit)
ApiAuth::storeOtp($pdo, $rollNumber, $otp, 300);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$_SESSION['otp'] = $otp;
$_SESSION['otp_roll_number'] = $rollNumber;
$_SESSION['otp_expiry'] = time() + 300; // 5 minutes
$_SESSION['otp_attempts'] = 0;

// Log OTP generation
OtpLogger::logOtpGenerated($rollNumber, $_SESSION['otp_expiry']);

// Determine recipient email:
// 1. Use student's registered email from database if present
// 2. Otherwise default to official institutional email
$cleanRoll = strtolower($rollNumber);
$registeredEmail = !empty($student['email']) ? trim($student['email']) : null;
$primaryEmail = $registeredEmail ?: ($cleanRoll . '@pvpsit.ac.in');
$secondaryEmail = $cleanRoll . '@pvpsiddhartha.ac.in';

$to = $primaryEmail;
$subject = 'PVPSIT Student Portal - Login Verification OTP';
$message = "
<html>
<head><title>OTP Verification</title></head>
<body style='font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px;'>
    <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <h2 style='color: #4a5568; margin-top: 0;'>PVPSIT Student Portal</h2>
        <p style='color: #718096;'>Hello <strong>" . htmlspecialchars($student['student_name'] ?? 'Student') . "</strong> (" . htmlspecialchars($rollNumber) . "),</p>
        <p style='color: #718096;'>Your One-Time Password (OTP) for Android / Web login is:</p>
        <div style='text-align: center; margin: 25px 0;'>
            <span style='color: #667eea; font-size: 36px; font-weight: bold; letter-spacing: 8px; background: #edf2f7; padding: 12px 24px; border-radius: 8px; display: inline-block;'>$otp</span>
        </div>
        <p style='color: #718096; font-size: 14px;'>This OTP is valid for <strong>5 minutes</strong>.</p>
        <p style='color: #a0aec0; font-size: 13px;'>If you did not request this OTP, please ignore this email.</p>
        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
        <p style='color: #a0aec0; font-size: 12px; text-align: center;'>PVPSIT Student Information System</p>
    </div>
</body>
</html>
";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'unnikiran@pvpsiddhartha.ac.in';
    $mail->Password = 'byxq jgii ievn nhgd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 5;

    $mail->setFrom('unnikiran@pvpsiddhartha.ac.in', 'PVPSIT Student Portal');
    $mail->addAddress($to);
    // If destination is @pvpsit.ac.in, also add @pvpsiddhartha.ac.in as CC so email lands regardless of college domain setup
    if (strpos($to, '@pvpsit.ac.in') !== false) {
        $mail->addCC($secondaryEmail);
    }

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    $mail->send();
    OtpLogger::logOtpSent($rollNumber, $to);

    ApiResponse::success([
        'roll_number' => $rollNumber,
        'email' => $to,
        'expires_in' => 300
    ], 'OTP sent successfully to your college email');
} catch (Exception $e) {
    OtpLogger::logOtpSendFailed($rollNumber, $to, $mail->ErrorInfo ?? $e->getMessage());
    ApiResponse::error('Failed to send verification email. Please try again later.', 500);
}
