<?php
/**
 * Verify OTP and authenticate student
 * POST: { roll_number, otp, csrf_token }
 */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/otp_logger.php';

// Read JSON input or POST
$jsonInput = file_get_contents('php://input');
$postData = json_decode($jsonInput, true);

if (!is_array($postData)) {
    $postData = $_POST;
}

// Validate CSRF token if session token exists
$sessionToken = $_SESSION['csrf_token'] ?? '';
$postedToken = $postData['csrf_token'] ?? '';

if (!empty($sessionToken) && !empty($postedToken) && !hash_equals($sessionToken, $postedToken)) {
    OtpLogger::logValidationError('csrf_token', $postedToken, 'Invalid CSRF token');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$rollNumber = strtoupper(trim($postData['roll_number'] ?? ''));
$otp = trim($postData['otp'] ?? '');

if (empty($rollNumber) || empty($otp)) {
    OtpLogger::logValidationError('credentials', '', 'Roll number and OTP are required');
    echo json_encode(['success' => false, 'message' => 'Roll number and OTP are required']);
    exit;
}

// Check if OTP exists and is not expired
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) {
    OtpLogger::logOtpExpired($rollNumber);
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new OTP.']);
    exit;
}

// Check if roll number matches (case-insensitive)
if (strtoupper($_SESSION['otp_roll_number'] ?? '') !== $rollNumber) {
    OtpLogger::logValidationError('roll_number', $rollNumber, 'Roll number does not match OTP session');
    echo json_encode(['success' => false, 'message' => 'Invalid roll number for active session']);
    exit;
}

// Check OTP attempts (max 3 attempts)
if (isset($_SESSION['otp_attempts']) && $_SESSION['otp_attempts'] >= 3) {
    OtpLogger::logTooManyAttempts($rollNumber, $_SESSION['otp_attempts']);
    echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.']);
    exit;
}

// Verify OTP
if ((string)$_SESSION['otp'] !== (string)$otp) {
    $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
    $remainingAttempts = max(0, 3 - $_SESSION['otp_attempts']);
    OtpLogger::logOtpVerificationAttempt($rollNumber, false, $_SESSION['otp_attempts']);
    echo json_encode([
        'success' => false,
        'message' => "Invalid OTP. $remainingAttempts attempts remaining."
    ]);
    exit;
}

// OTP is valid - authenticate student
$_SESSION['student_authenticated'] = true;
$_SESSION['student_roll_no'] = $rollNumber;
$_SESSION['auth_time'] = time();

// Log successful authentication
OtpLogger::logOtpVerificationAttempt($rollNumber, true);
OtpLogger::logAuthenticationSuccess($rollNumber);

// Clear OTP from session
unset($_SESSION['otp']);
unset($_SESSION['otp_expiry']);
unset($_SESSION['otp_roll_number']);
unset($_SESSION['otp_attempts']);

echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
