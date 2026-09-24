<?php
/**
 * REST API v1: Student Leaderboard / Toppers
 * GET /api/v1/leaderboard.php?limit=20&department=IT
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/../../config.php';
global $pdo;

ApiResponse::initCors();

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
$department = isset($_GET['department']) ? trim($_GET['department']) : '';

try {
    $where = "";
    $params = [];

    if (!empty($department)) {
        $where = "WHERE s.department = ?";
        $params[] = $department;
    }

    $query = "
        SELECT sr.roll_no, 
               COALESCE(s.student_name, sr.student_name) as student_name,
               s.department,
               s.student_photo,
               MAX(sr.cgpa) as cgpa,
               MAX(sr.semester) as latest_semester
        FROM student_results sr
        LEFT JOIN students s ON sr.roll_no = s.roll_no
        $where
        GROUP BY sr.roll_no, student_name, s.department, s.student_photo
        HAVING cgpa IS NOT NULL AND cgpa > 0
        ORDER BY cgpa DESC
        LIMIT $limit
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rank = 1;
    foreach ($leaders as &$l) {
        $l['rank'] = $rank++;
        $l['cgpa'] = (float)$l['cgpa'];
        $photoUrl = '';
        if (!empty($l['student_photo'])) {
            if (strpos($l['student_photo'], 'http') === 0) {
                $photoUrl = $l['student_photo'];
            } else {
                $photoUrl = 'uploads/student_photos/' . $l['student_photo'];
            }
        }
        $l['photo_url'] = $photoUrl;
    }

    ApiResponse::success([
        'total' => count($leaders),
        'department_filter' => $department ?: 'ALL',
        'leaderboard' => $leaders
    ], 'Leaderboard fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
