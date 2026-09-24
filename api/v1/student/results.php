<?php
/**
 * REST API v1: Get Student Academic Results & Subject Grades
 * GET /api/v1/student/results.php
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

    // Fetch Semester summary results (deduplicated by semester/semester_info)
    $semStmt = $pdo->prepare("
        SELECT sr.id, sr.roll_no, sr.student_name, sr.father_name, sr.semester, sr.sgpa, sr.cgpa, 
               sr.scr, sr.tcr, sr.semester_info, sr.exam_info, sr.department, sr.academic_year, sr.created_at
        FROM student_results sr
        WHERE sr.roll_no IN (?, ?, ?) 
        ORDER BY sr.id ASC
    ");
    $semStmt->execute($rp);
    $rawSemesterResults = $semStmt->fetchAll(PDO::FETCH_ASSOC);

    // Deduplicate semesters by semester_info or semester name
    $semesterResults = [];
    $seenSem = [];
    foreach ($rawSemesterResults as $s) {
        $key = trim((string)($s['semester_info'] ?: $s['semester'] ?: $s['id']));
        if (!isset($seenSem[$key])) {
            $seenSem[$key] = true;
            $semesterResults[] = $s;
        }
    }

    // Fetch Subject-wise detailed results with deduplicated course_codes
    $subStmt = $pdo->prepare("
        SELECT sr.id, sr.roll_no, sr.subject_code, 
               COALESCE(MAX(sc.subject_name), MAX(cc.course_name), sr.subject_code) as subject_name,
               sr.grade, sr.credits, sr.semester_info, sr.exam_info
        FROM subject_results sr
        LEFT JOIN student_courses sc ON sr.subject_code = sc.subject_code AND sr.roll_no = sc.roll_no
        LEFT JOIN (
            SELECT course_code, MAX(course_name) as course_name FROM course_codes GROUP BY course_code
        ) cc ON sr.subject_code = cc.course_code
        WHERE sr.roll_no IN (?, ?, ?)
        GROUP BY sr.id
        ORDER BY sr.id ASC, sr.subject_code ASC
    ");
    $subStmt->execute($rp);
    $subjectResults = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group subjects by semester_info / exam_info
    $subjectsBySemInfo = [];
    foreach ($subjectResults as $sub) {
        $info = trim((string)($sub['semester_info'] ?: $sub['exam_info'] ?: 'General'));
        if (!isset($subjectsBySemInfo[$info])) {
            $subjectsBySemInfo[$info] = [];
        }
        $subjectsBySemInfo[$info][] = $sub;
    }

    $formattedSemesters = [];
    foreach ($semesterResults as $sem) {
        $info = trim((string)($sem['semester_info'] ?: $sem['exam_info'] ?: 'General'));
        $sem['subjects'] = $subjectsBySemInfo[$info] ?? [];
        $formattedSemesters[] = $sem;
    }

    // Summary calculations
    $latestCgpa = null;
    $totalBacklogs = 0;
    if (!empty($semesterResults)) {
        $last = end($semesterResults);
        $latestCgpa = isset($last['cgpa']) ? (float)$last['cgpa'] : null;
    }

    foreach ($subjectResults as $s) {
        $g = strtoupper(trim($s['grade'] ?? ''));
        if ($g === 'F' || $g === 'FAIL' || $g === 'AB' || $g === 'ABSENT') {
            $totalBacklogs++;
        }
    }

    ApiResponse::success([
        'roll_no' => $rollNumber,
        'cgpa' => $latestCgpa,
        'backlogs_count' => $totalBacklogs,
        'total_semesters' => count($formattedSemesters),
        'semesters' => $formattedSemesters,
        'all_subjects' => $subjectResults
    ], 'Academic results fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
