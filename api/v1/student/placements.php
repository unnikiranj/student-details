<?php
/**
 * REST API v1: Get Student Placement / Job Offers
 * GET /api/v1/student/placements.php
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
        SELECT id, roll_no, student_name, company_name, package as package_lpa, 
               job_role, offer_date, academic_year, semester, upload_date
        FROM job_offers 
        WHERE roll_no IN (?, ?, ?) 
        ORDER BY package DESC, offer_date DESC
    ");
    $stmt->execute($rp);
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $highestPackage = 0.0;
    foreach ($offers as $o) {
        $p = (float)($o['package_lpa'] ?? 0);
        if ($p > $highestPackage) $highestPackage = $p;
    }

    ApiResponse::success([
        'roll_no' => $rollNumber,
        'total_offers' => count($offers),
        'highest_package_lpa' => $highestPackage > 0 ? $highestPackage : null,
        'offers' => $offers
    ], 'Placement offers fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
