<?php
/**
 * REST API v1: Get Student Attendance Breakdown
 * GET /api/v1/student/attendance.php
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

function extractCleanMonthName($sessionName, $rawMonth) {
    $raw = strtolower(trim($sessionName . ' ' . $rawMonth));
    $year = '';
    if (preg_match('/(202\d|2\d)/', $raw, $ym)) {
        $year = strlen($ym[1]) === 2 ? '20' . $ym[1] : $ym[1];
    }
    
    if (strpos($raw, 'oct_nov') !== false || strpos($raw, 'oct-nov') !== false || (strpos($raw, 'oct') !== false && strpos($raw, 'nov') !== false)) {
        return 'Oct-Nov' . ($year ? ' ' . $year : '');
    }
    
    $months = [
        'jan' => 'January',
        'feb' => 'February',
        'mar' => 'March',
        'apr' => 'April',
        'may' => 'May',
        'jun' => 'June',
        'jul' => 'July',
        'aug' => 'August',
        'sep' => 'September',
        'oct' => 'October',
        'nov' => 'November',
        'dec' => 'December'
    ];
    
    foreach ($months as $key => $name) {
        if (strpos($raw, $key) !== false) {
            return $name . ($year ? ' ' . $year : '');
        }
    }
    
    return (!empty($rawMonth) && strtolower($rawMonth) !== 'unknown') ? ucfirst($rawMonth) : 'Session ' . $sessionName;
}

try {
    $rp = [$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)];

    // Fetch Monthly Attendance summary from attendance_summary joined with attendance_uploads
    $stmtSummary = $pdo->prepare("
        SELECT s.id, s.roll_no, s.total_present, s.total_classes, s.percentage, s.session_name,
               u.file_name, u.semester, u.academic_year, u.section, u.month_name, u.uploaded_at
        FROM attendance_summary s
        LEFT JOIN attendance_uploads u ON s.session_name = u.session_name
        WHERE s.roll_no IN (?, ?, ?)
        ORDER BY u.uploaded_at ASC, s.id ASC
    ");
    $stmtSummary->execute($rp);
    $monthlyData = $stmtSummary->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Subject-wise Attendance from attendance_subjects
    $stmtSubjects = $pdo->prepare("
        SELECT s.id, s.roll_no, s.subject_code, s.subject_name, s.present, s.extra, s.total, s.session_name,
               u.semester, u.academic_year, u.section, u.month_name, u.uploaded_at
        FROM attendance_subjects s
        LEFT JOIN attendance_uploads u ON s.session_name = u.session_name
        WHERE s.roll_no IN (?, ?, ?)
        ORDER BY u.uploaded_at ASC, s.id ASC
    ");
    $stmtSubjects->execute($rp);
    $subjectData = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);

    // 1. Process Monthly History
    $formattedMonthly = [];
    $seenSessions = [];

    foreach ($monthlyData as $row) {
        $sess = $row['session_name'] ?? ('sess_' . $row['id']);
        if (isset($seenSessions[$sess])) continue;
        $seenSessions[$sess] = true;

        $p = (int)($row['total_present'] ?? 0);
        $t = (int)($row['total_classes'] ?? 0);
        $pct = isset($row['percentage']) && (float)$row['percentage'] > 0 ? (float)$row['percentage'] : ($t > 0 ? round(($p / $t) * 100, 2) : 0.0);
        $cleanMonth = extractCleanMonthName($row['session_name'] ?? '', $row['month_name'] ?? '');
        $semStr = trim((string)($row['semester'] ?? ''));
        if (!empty($semStr) && !str_starts_with(strtolower($semStr), 'sem')) {
            $semStr = 'Sem ' . $semStr;
        }

        $formattedMonthly[] = [
            'session_name' => $row['session_name'],
            'month' => $cleanMonth,
            'semester' => $semStr ?: 'General',
            'academic_year' => $row['academic_year'] ?? '',
            'present' => $p,
            'total' => $t,
            'percentage' => $pct
        ];
    }

    // 2. Latest Cumulative Subject State per Semester (Accounting for Extra Classes)
    // Since uploads for a semester are cumulative, take the latest upload per subject in each semester
    $latestSubjectsBySem = [];
    foreach ($subjectData as $s) {
        $sem = trim((string)($s['semester'] ?? 'General'));
        if (!empty($sem) && !str_starts_with(strtolower($sem), 'sem')) {
            $sem = 'Sem ' . $sem;
        }
        $code = trim((string)$s['subject_code']);
        $key = $sem . '___' . $code;

        $sp = (int)($s['present'] ?? 0);
        $se = (int)($s['extra'] ?? 0);
        $st = (int)($s['total'] ?? 0);
        $effectivePresent = $sp + $se;
        $pct = $st > 0 ? round(($effectivePresent / $st) * 100, 2) : 0.0;

        $latestSubjectsBySem[$key] = [
            'subject_code' => $code,
            'subject_name' => $s['subject_name'] ?? 'N/A',
            'semester' => $sem,
            'month' => extractCleanMonthName($s['session_name'] ?? '', $s['month_name'] ?? ''),
            'present' => $effectivePresent, // Combined present + extra
            'extra' => $se,
            'total' => $st,
            'percentage' => $pct
        ];
    }

    $formattedSubjects = array_values($latestSubjectsBySem);

    // 3. Semester Breakdown Summary from Official Attendance Summary (Latest cumulative upload per semester)
    $semesterSummaryMap = [];
    foreach ($monthlyData as $row) {
        $sem = trim((string)($row['semester'] ?? 'General'));
        if (!empty($sem) && !str_starts_with(strtolower($sem), 'sem')) {
            $sem = 'Sem ' . $sem;
        }

        $p = (int)($row['total_present'] ?? 0);
        $t = (int)($row['total_classes'] ?? 0);
        $pct = isset($row['percentage']) && (float)$row['percentage'] > 0 ? (float)$row['percentage'] : ($t > 0 ? round(($p / $t) * 100, 2) : 0.0);

        // Later uploads overwrite earlier ones to get the final cumulative status for this semester
        $semesterSummaryMap[$sem] = [
            'semester' => $sem,
            'present' => $p,
            'total' => $t,
            'percentage' => $pct
        ];
    }

    $semesterBreakdown = [];
    foreach ($semesterSummaryMap as $sem => $st) {
        $p = $st['present'];
        $t = $st['total'];
        $pct = $st['percentage'];
        
        $status = 'Safe';
        if ($pct >= 75) {
            $status = 'Safe (>= 75%)';
        } elseif ($pct >= 65) {
            $status = 'Condonation (65% - 75%)';
        } else {
            $status = 'Danger (< 65%)';
        }

        $semesterBreakdown[] = [
            'semester' => $sem,
            'present' => $p,
            'total' => $t,
            'percentage' => $pct,
            'status' => $status
        ];
    }

    // 4. Overall Attendance Summary (Latest / Active Semester Attendance)
    $latestSem = !empty($semesterBreakdown) ? end($semesterBreakdown) : null;
    $overallPresent = $latestSem ? $latestSem['present'] : 0;
    $overallTotal = $latestSem ? $latestSem['total'] : 0;
    $overallPercentage = $latestSem ? $latestSem['percentage'] : 0.0;
    $overallStatus = $latestSem ? $latestSem['status'] : 'Normal';

    // 5. Latest Month Detection
    $latestMonth = !empty($formattedMonthly) ? end($formattedMonthly) : null;

    ApiResponse::success([
        'roll_no' => $rollNumber,
        'overall' => [
            'present' => $overallPresent,
            'total' => $overallTotal,
            'percentage' => $overallPercentage,
            'status' => $overallStatus
        ],
        'latest_month' => $latestMonth,
        'semester_summaries' => $semesterBreakdown,
        'monthly' => $formattedMonthly,
        'subjects' => $formattedSubjects
    ], 'Attendance records fetched successfully');

} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
