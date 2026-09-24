<?php
require_once 'config/db.php';

echo "Checking student_results table structure:\n";
$result = $conn->query("DESCRIBE student_results");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n\nSample data from student_results:\n";
$result = $conn->query("SELECT * FROM student_results LIMIT 3");
while ($row = $result->fetch_assoc()) {
    print_r($row);
    echo "\n";
}

echo "\n\nChecking subject_results table structure:\n";
$result = $conn->query("DESCRIBE subject_results");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n\nSample data from subject_results:\n";
$result = $conn->query("SELECT * FROM subject_results LIMIT 3");
while ($row = $result->fetch_assoc()) {
    print_r($row);
    echo "\n";
}
?>
