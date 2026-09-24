<?php
/**
 * REST API v1: Verify OTP and issue Bearer Token
 * POST /api/v1/auth/verify_otp.php
 * Body (JSON): { "roll_number": "24501A1201", "otp": "123456" }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../otp_logger.php';
global $pdo;

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
$otp = trim((string)($input['otp'] ?? $input['code'] ?? $_GET['otp'] ?? ''));

if (empty($rollNumber) || empty($otp)) {
    ApiResponse::error('Roll number and OTP are both required', 422);
}

// Primary: Stateless Database OTP Verification (Works on Android / Retrofit / Postman)
$dbVerify = ApiAuth::verifyOtp($pdo, $rollNumber, $otp);

if (!$dbVerify['success']) {
    // Fallback: Check PHP Session for Web clients
    $sessionValid = false;
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry']) && time() <= $_SESSION['otp_expiry']) {
        if (strtoupper($_SESSION['otp_roll_number'] ?? '') === $rollNumber && (string)$_SESSION['otp'] === (string)$otp) {
            $sessionValid = true;
        }
    }

    if (!$sessionValid) {
        $errorCode = ($dbVerify['error'] && strpos($dbVerify['error'], 'Invalid OTP') !== false) ? 401 : 400;
        OtpLogger::logOtpVerificationAttempt($rollNumber, false);
        ApiResponse::error($dbVerify['error'] ?: 'Invalid OTP.', $errorCode, ['remaining_attempts' => $dbVerify['remaining'] ?? 0]);
    }
}

// OTP is verified! Fetch full student profile details
try {
    $rp = [$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)];
    $stmt = $pdo->prepare("SELECT id, roll_no, student_name, department, email, phone, date_of_birth, student_photo, father_name, created_at FROM students WHERE roll_no IN (?, ?, ?) LIMIT 1");
    $stmt->execute($rp);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $stmtResults = $pdo->prepare("SELECT roll_no, student_name, father_name, department FROM student_results WHERE roll_no IN (?, ?, ?) LIMIT 1");
        $stmtResults->execute($rp);
        $res = $stmtResults->fetch(PDO::FETCH_ASSOC);

        $student = [
            'id' => null,
            'roll_no' => $rollNumber,
            'student_name' => $res['student_name'] ?? 'Student',
            'department' => $res['department'] ?? '',
            'email' => strtolower($rollNumber) . '@pvpsit.ac.in',
            'phone' => '',
            'date_of_birth' => null,
            'student_photo' => null,
            'father_name' => $res['father_name'] ?? '',
            'created_at' => null
        ];
    }

    if (empty($student['email'])) {
        $student['email'] = strtolower($student['roll_no']) . '@pvpsit.ac.in';
    }

    // Format photo path if available
    $photoUrl = '';
    if (!empty($student['student_photo'])) {
        if (strpos($student['student_photo'], 'http') === 0) {
            $photoUrl = $student['student_photo'];
        } else {
            $photoUrl = 'uploads/student_photos/' . $student['student_photo'];
        }
    }
    $student['photo_url'] = $photoUrl;

    // Format DOB
    $formattedDob = null;
    $privacyDob = null;
    if (!empty($student['date_of_birth'])) {
        $formattedDob = date('d M Y', strtotime($student['date_of_birth']));
        $privacyDob = date('d-M', strtotime($student['date_of_birth']));
    }
    $student['formatted_dob'] = $formattedDob;
    $student['privacy_dob'] = $privacyDob;

    // Get Current Active Semester Attendance summary (latest upload)
    $attStmt = $pdo->prepare("
        SELECT s.total_present, s.total_classes, s.percentage, u.semester
        FROM attendance_summary s
        LEFT JOIN attendance_uploads u ON s.session_name = u.session_name
        WHERE s.roll_no IN (?, ?, ?)
        ORDER BY u.uploaded_at DESC, s.id DESC
        LIMIT 1
    ");
    $attStmt->execute($rp);
    $att = $attStmt->fetch(PDO::FETCH_ASSOC);
    $totalPresent = (int)($att['total_present'] ?? 0);
    $totalClasses = (int)($att['total_classes'] ?? 0);
    $attendancePercentage = isset($att['percentage']) && (float)$att['percentage'] > 0 
        ? (float)$att['percentage'] 
        : ($totalClasses > 0 ? round(($totalPresent / $totalClasses) * 100, 2) : 0.0);

    $student['attendance_summary'] = [
        'present' => $totalPresent,
        'total' => $totalClasses,
        'percentage' => $attendancePercentage
    ];

    // Get Latest CGPA
    $cgpaStmt = $pdo->prepare("SELECT cgpa, sgpa, semester FROM student_results WHERE roll_no IN (?, ?, ?) ORDER BY id DESC LIMIT 1");
    $cgpaStmt->execute($rp);
    $latestResult = $cgpaStmt->fetch(PDO::FETCH_ASSOC);
    $student['academic_summary'] = [
        'latest_semester' => $latestResult['semester'] ?? null,
        'latest_sgpa' => isset($latestResult['sgpa']) ? (float)$latestResult['sgpa'] : null,
        'latest_cgpa' => isset($latestResult['cgpa']) ? (float)$latestResult['cgpa'] : null
    ];

    // Issue Bearer API Token
    $tokenData = ApiAuth::createToken($pdo, $rollNumber, 'student', 30);

    // Also populate Web Session for hybrid clients
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION['student_authenticated'] = true;
    $_SESSION['student_roll_no'] = $rollNumber;
    $_SESSION['auth_time'] = time();

    // Clear OTP session state
    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_roll_number'], $_SESSION['otp_attempts']);

    OtpLogger::logOtpVerificationAttempt($rollNumber, true);
    OtpLogger::logAuthenticationSuccess($rollNumber);

    ApiResponse::success([
        'auth' => $tokenData,
        'student' => $student
    ], 'Authentication successful');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
