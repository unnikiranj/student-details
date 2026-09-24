<?php
/**
 * Get current student profile data
 * Requires authenticated session
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config.php';

// Check authentication
if (!isset($_SESSION['student_authenticated']) || $_SESSION['student_authenticated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$rollNumber = strtoupper(trim($_SESSION['student_roll_no'] ?? ''));

if (empty($rollNumber)) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

try {
    // Get student data
    $stmt = $pdo->prepare("SELECT student_name, student_photo, date_of_birth, department, email, phone, father_name FROM students WHERE roll_no IN (?, ?, ?) LIMIT 1");
    $stmt->execute([$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        // Fallback check in student_results
        $stmt2 = $pdo->prepare("SELECT student_name FROM student_results WHERE roll_no IN (?, ?, ?) LIMIT 1");
        $stmt2->execute([$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);
        $res = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        $student = [
            'student_name' => $res['student_name'] ?? 'Student',
            'student_photo' => '',
            'date_of_birth' => '',
            'department' => '',
            'email' => '',
            'phone' => '',
            'father_name' => ''
        ];
    }

    // Build photo path
    $photoPath = '';
    if (!empty($student['student_photo'])) {
        if (strpos($student['student_photo'], 'http') === 0) {
            $photoPath = $student['student_photo'];
        } else {
            $photoPath = 'uploads/student_photos/' . $student['student_photo'];
        }
    }

    echo json_encode([
        'success' => true,
        'roll_no' => $rollNumber,
        'student_name' => $student['student_name'] ?? '',
        'photo_path' => $photoPath,
        'date_of_birth' => $student['date_of_birth'] ?? '',
        'department' => $student['department'] ?? '',
        'father_name' => $student['father_name'] ?? ''
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
