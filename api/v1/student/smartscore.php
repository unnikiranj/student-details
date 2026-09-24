<?php
/**
 * REST API v1: Get Student SmartScore / Placement Readiness
 * GET /api/v1/student/smartscore.php
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

    // smartscore table uses `roll_number`
    $stmt = $pdo->prepare("SELECT * FROM smartscore WHERE roll_number IN (?, ?, ?) LIMIT 1");
    $stmt->execute($rp);
    $smartScore = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$smartScore) {
        ApiResponse::success([
            'roll_no' => $rollNumber,
            'available' => false,
            'overall_score' => null,
            'rank' => null,
            'global_rank' => null,
            'message' => 'SmartScore assessment data not yet published for this student'
        ], 'SmartScore data');
    }

    $smartScore['available'] = true;
    ApiResponse::success($smartScore, 'SmartScore fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
