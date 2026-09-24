<?php
/**
 * REST API v1: Get Student Profile Details
 * GET /api/v1/student/profile.php
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

    // Fetch primary student profile
    $stmt = $pdo->prepare("SELECT * FROM students WHERE roll_no IN (?, ?, ?) LIMIT 1");
    $stmt->execute($rp);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        // Fallback: check student_results
        $stmtRes = $pdo->prepare("SELECT student_name, roll_no, father_name, department FROM student_results WHERE roll_no IN (?, ?, ?) LIMIT 1");
        $stmtRes->execute($rp);
        $res = $stmtRes->fetch(PDO::FETCH_ASSOC);

        if (!$res) {
            ApiResponse::error('Student profile not found', 404);
        }

        $student = [
            'id' => null,
            'roll_no' => $res['roll_no'],
            'student_name' => $res['student_name'],
            'email' => strtolower($res['roll_no']) . '@pvpsit.ac.in',
            'phone' => null,
            'date_of_birth' => null,
            'student_photo' => null,
            'father_name' => $res['father_name'] ?? '',
            'department' => $res['department'] ?? '',
            'created_at' => null
        ];
    }

    if (empty($student['email'])) {
        $student['email'] = strtolower($student['roll_no']) . '@pvpsit.ac.in';
    }

    // Format photo URL
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

    // Get Current Active Semester Attendance summary from attendance_summary (latest upload)
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

    // Get Latest CGPA from student_results
    $cgpaStmt = $pdo->prepare("SELECT cgpa, sgpa, semester FROM student_results WHERE roll_no IN (?, ?, ?) ORDER BY id DESC LIMIT 1");
    $cgpaStmt->execute($rp);
    $latestResult = $cgpaStmt->fetch(PDO::FETCH_ASSOC);
    $student['academic_summary'] = [
        'latest_semester' => $latestResult['semester'] ?? null,
        'latest_sgpa' => isset($latestResult['sgpa']) ? (float)$latestResult['sgpa'] : null,
        'latest_cgpa' => isset($latestResult['cgpa']) ? (float)$latestResult['cgpa'] : null
    ];

    ApiResponse::success($student, 'Student profile fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
