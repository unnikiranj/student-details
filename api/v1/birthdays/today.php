<?php
/**
 * REST API v1: Today's Birthdays List
 * GET /api/v1/birthdays/today.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../../../config.php';
global $pdo;

ApiResponse::initCors();

try {
    $todayMonthDay = date('m-d');

    $stmt = $pdo->prepare("
        SELECT roll_no, student_name, department, date_of_birth, student_photo
        FROM students 
        WHERE DATE_FORMAT(date_of_birth, '%m-%d') = ? 
        AND date_of_birth IS NOT NULL 
        ORDER BY student_name ASC
    ");
    $stmt->execute([$todayMonthDay]);
    $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($birthdays as &$b) {
        $photoUrl = '';
        if (!empty($b['student_photo'])) {
            if (strpos($b['student_photo'], 'http') === 0) {
                $photoUrl = $b['student_photo'];
            } else {
                $photoUrl = 'uploads/student_photos/' . $b['student_photo'];
            }
        }
        $b['photo_url'] = $photoUrl;
        $b['birthday_formatted'] = !empty($b['date_of_birth']) ? date('d F', strtotime($b['date_of_birth'])) : '';
    }

    ApiResponse::success([
        'date' => date('Y-m-d'),
        'formatted_date' => date('F j, Y'),
        'count' => count($birthdays),
        'students' => $birthdays
    ], 'Today\'s birthdays fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
