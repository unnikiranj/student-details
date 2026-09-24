<?php
require_once 'config.php';

echo "Checking for missing course codes...\n\n";

// Get all unique subject codes from attendance_subjects
$stmt = $pdo->query("SELECT DISTINCT subject_code FROM attendance_subjects");
$attendanceCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Total unique codes in attendance_subjects: " . count($attendanceCodes) . "\n\n";

// Get all course codes from course_codes table
$stmt = $pdo->query("SELECT DISTINCT course_code FROM course_codes");
$courseCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Total codes in course_codes: " . count($courseCodes) . "\n\n";

// Find missing codes
$missingCodes = [];
foreach ($attendanceCodes as $code) {
    $codeUpper = strtoupper($code);
    if (!in_array($codeUpper, $courseCodes)) {
        $missingCodes[] = $codeUpper;
    }
}

echo "Missing course codes (" . count($missingCodes) . "):\n";
foreach ($missingCodes as $code) {
    echo "  - $code\n";
}

if (empty($missingCodes)) {
    echo "No missing course codes found.\n";
}
?>
