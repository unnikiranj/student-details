<?php
/**
 * REST API v1: Update Student Date of Birth
 * POST /api/v1/student/update_dob.php
 * Header: Authorization: Bearer <token> (optional if roll_number provided in body)
 * Body (JSON): { "roll_number": "24501A1201", "date_of_birth": "2004-05-15" }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../../config.php';
global $pdo;

ApiResponse::initCors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed. Use POST.', 405);
}

$input = ApiResponse::getJsonInput();
$rollNumber = null;
if (!empty($input['roll_number'])) {
    $rollNumber = strtoupper(trim($input['roll_number']));
} elseif (!empty($input['roll'])) {
    $rollNumber = strtoupper(trim($input['roll']));
} else {
    $auth = ApiAuth::requireAuth($pdo);
    $rollNumber = $auth['identifier'];
}

if (empty($rollNumber)) {
    ApiResponse::error('Roll number is required', 400);
}

$dateOfBirth = trim($input['date_of_birth'] ?? '');

if (empty($dateOfBirth)) {
    ApiResponse::error('Date of birth is required', 422, ['field' => 'date_of_birth']);
}

// Convert various date formats (e.g. 20-09-2004 or 20 Sep 2004) to YYYY-MM-DD
$dobTimestamp = strtotime($dateOfBirth);
if ($dobTimestamp === false) {
    ApiResponse::error('Invalid date format. Expected YYYY-MM-DD (e.g. 2004-09-20).', 422, ['field' => 'date_of_birth']);
}
$formattedYmd = date('Y-m-d', $dobTimestamp);

if ($dobTimestamp > time()) {
    ApiResponse::error('Invalid date of birth. Future dates are not allowed.', 422, ['field' => 'date_of_birth']);
}

// Age validation: 10 to 100 years
$minAge = strtotime('-100 years');
$maxAge = strtotime('-10 years');
if ($dobTimestamp < $minAge || $dobTimestamp > $maxAge) {
    ApiResponse::error('Invalid date of birth. Age must be between 10 and 100 years.', 422);
}

try {
    $stmt = $pdo->prepare("UPDATE students SET date_of_birth = ? WHERE roll_no IN (?, ?, ?)");
    $stmt->execute([$formattedYmd, $rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);

    ApiResponse::success([
        'roll_no' => $rollNumber,
        'date_of_birth' => $formattedYmd,
        'formatted_dob' => date('d M Y', $dobTimestamp),
        'privacy_dob' => date('d-M', $dobTimestamp)
    ], 'Date of birth updated successfully');
} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
