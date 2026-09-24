<?php
require_once 'config.php';

echo "Columns in students table:\n";
$stmt = $pdo->query('DESCRIBE students');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . "\n";
}

echo "\n\nSample student data with phone numbers:\n";
$stmt = $pdo->query('SELECT roll_no, student_name FROM students LIMIT 5');
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($students as $student) {
    echo $student['roll_no'] . " - " . $student['student_name'] . "\n";
}
