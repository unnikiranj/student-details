<?php
/**
 * REST API v1: Get Student Mid-Term Exam Marks
 * GET /api/v1/student/midmarks.php
 * Header: Authorization: Bearer <token> (or ?roll=23501A1267)
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

    // Fetch mid marks with upload metadata and resolved subject names
    $stmt = $pdo->prepare("
        SELECT mm.id, mm.upload_id, mm.roll_no, mm.subject_code,
               COALESCE(NULLIF(mm.subject_name, ''), sc.subject_name, cc.course_name, mm.subject_code) AS subject_name,
               mm.marks,
               mm.descriptive_marks1, mm.objective_marks1, mm.assignment_marks1,
               mm.descriptive_marks2, mm.objective_marks2, mm.assignment_marks2,
               mm.total_marks, mm.max_marks, mm.raw_text,
               mu.semester_info, mu.academic_year, mu.section, mu.department
        FROM midmarks_subject_marks mm
        LEFT JOIN midmarks_uploads mu ON mm.upload_id = mu.id
        LEFT JOIN student_courses sc ON mm.subject_code = sc.subject_code AND mm.roll_no = sc.roll_no
        LEFT JOIN course_codes cc ON mm.subject_code = cc.course_code
        WHERE mm.roll_no IN (?, ?, ?)
        GROUP BY mm.id
        ORDER BY mm.subject_code ASC, mm.id ASC
    ");
    $stmt->execute($rp);
    $rawMidmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedSubjects = [];
    $totalMarksSum = 0;
    $evaluatedCount = 0;

    foreach ($rawMidmarks as $row) {
        $desc1 = $row['descriptive_marks1'] !== null ? (float)$row['descriptive_marks1'] : null;
        $obj1 = $row['objective_marks1'] !== null ? (float)$row['objective_marks1'] : null;
        $asg1 = $row['assignment_marks1'] !== null ? (float)$row['assignment_marks1'] : null;

        $desc2 = $row['descriptive_marks2'] !== null ? (float)$row['descriptive_marks2'] : null;
        $obj2 = $row['objective_marks2'] !== null ? (float)$row['objective_marks2'] : null;
        $asg2 = $row['assignment_marks2'] !== null ? (float)$row['assignment_marks2'] : null;

        $mid1Total = null;
        if ($desc1 !== null || $obj1 !== null || $asg1 !== null) {
            $mid1Total = ($desc1 ?? 0) + ($obj1 ?? 0) + ($asg1 ?? 0);
        }

        $mid2Total = null;
        if ($desc2 !== null || $obj2 !== null || $asg2 !== null) {
            $mid2Total = ($desc2 ?? 0) + ($obj2 ?? 0) + ($asg2 ?? 0);
        }

        $totalMarks = $row['total_marks'] !== null ? (float)$row['total_marks'] : ($row['marks'] !== null ? (float)$row['marks'] : null);
        $maxMarks = $row['max_marks'] !== null ? (float)$row['max_marks'] : 30.0;

        if ($totalMarks !== null) {
            $totalMarksSum += $totalMarks;
            $evaluatedCount++;
        }

        $formattedSubjects[] = [
            'id' => (int)$row['id'],
            'subject_code' => $row['subject_code'],
            'subject_name' => $row['subject_name'],
            'mid1' => [
                'descriptive' => $desc1,
                'objective' => $obj1,
                'assignment' => $asg1,
                'total' => $mid1Total
            ],
            'mid2' => [
                'descriptive' => $desc2,
                'objective' => $obj2,
                'assignment' => $asg2,
                'total' => $mid2Total
            ],
            'total_marks' => $totalMarks,
            'max_marks' => $maxMarks,
            'raw_breakdown' => $row['raw_text'],
            'semester_info' => $row['semester_info'],
            'academic_year' => $row['academic_year']
        ];
    }

    $avgScore = $evaluatedCount > 0 ? round($totalMarksSum / $evaluatedCount, 2) : 0;

    ApiResponse::success([
        'roll_no' => $rollNumber,
        'total_subjects' => count($formattedSubjects),
        'evaluated_count' => $evaluatedCount,
        'average_internal_marks' => $avgScore,
        'subjects' => $formattedSubjects
    ], 'Mid-term marks fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
