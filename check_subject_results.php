<?php
require_once 'config.php';

echo "Checking subject_results table...\n";

// Get sample subject results
$stmt = $pdo->prepare("SELECT DISTINCT subject_code, subject_name, credits FROM subject_results WHERE roll_no = '23501A4228' LIMIT 10");
$stmt->execute();
$results = $stmt->fetchAll();

echo "\nSubject results for roll_no 23501A4228:\n";
foreach ($results as $row) {
    echo "Code: {$row['subject_code']}, Name: {$row['subject_name']}, Credits: {$row['credits']}\n";
}

// Check if these codes exist in course_codes
echo "\nChecking course_codes table for these codes:\n";
foreach ($results as $row) {
    $code = $row['subject_code'];
    $stmt = $pdo->prepare("SELECT course_code, course_name, credits FROM course_codes WHERE course_code = ?");
    $stmt->execute([$code]);
    $course = $stmt->fetch();
    if ($course) {
        echo "✓ Found in course_codes: {$course['course_code']} - {$course['course_name']} - {$course['credits']} CR\n";
    } else {
        echo "✗ NOT in course_codes: $code\n";
    }
}
?>
