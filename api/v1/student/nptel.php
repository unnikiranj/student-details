<?php
/**
 * REST API v1: Get Student NPTEL Certifications
 * GET /api/v1/student/nptel.php
 * Header: Authorization: Bearer <token> (or ?roll=24501A1201)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../../config.php';
global $pdo;

ApiResponse::initCors();

$rollNumber = null;
if (!empty($_GET['roll'])) {
    $rollNumber = strtoupper(trim($_GET['roll']));
} else {
    $auth = ApiAuth::requireAuth($pdo);
    $rollNumber = $auth['identifier'];
}

if (empty($rollNumber)) {
    ApiResponse::error('Roll number is required', 400);
}

try {
    $rp = [$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)];

    $stmt = $pdo->prepare("
        SELECT id, roll_no, student_name, course_name, nptel_roll_no, certificate_no,
               score_percentage, assignments_score, proctored_score, duration, course_weeks,
               institute, credits, total_certified, grade_label, pdf_filename, qr_verified,
               verify_url, uploaded_at
        FROM nptel_certificates 
        WHERE roll_no IN (?, ?, ?) 
        ORDER BY score_percentage DESC
    ");
    $stmt->execute($rp);
    $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ApiResponse::success([
        'roll_no' => $rollNumber,
        'total_certificates' => count($certs),
        'certificates' => $certs
    ], 'NPTEL certificates fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
