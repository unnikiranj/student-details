<?php
require_once 'config.php';

echo "Checking smartscore table...\n";

// Check total records
$stmt = $pdo->query('SELECT COUNT(*) as count FROM smartscore');
$result = $stmt->fetch();
echo "Total records in smartscore: " . $result['count'] . "\n\n";

// Check sample records
$stmt = $pdo->query('SELECT roll_number, name, overall_score FROM smartscore LIMIT 5');
echo "Sample records:\n";
while ($row = $stmt->fetch()) {
    echo "Roll: " . $row['roll_number'] . ", Name: " . $row['name'] . ", Score: " . $row['overall_score'] . "\n";
}

// Check if roll_number column exists and has data
$stmt = $pdo->query('DESCRIBE smartscore');
echo "\nTable structure:\n";
while ($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

// Check student_results roll_no format
echo "\nChecking student_results roll_no format:\n";
$stmt = $pdo->query('SELECT roll_no FROM student_results LIMIT 5');
while ($row = $stmt->fetch()) {
    echo "Roll: " . $row['roll_no'] . "\n";
}

// Test a specific roll number match
echo "\nTesting roll number match:\n";
$testRoll = '24501A12B6';
$stmt = $pdo->prepare("SELECT * FROM smartscore WHERE roll_number = ?");
$stmt->execute([$testRoll]);
$match = $stmt->fetch();
if ($match) {
    echo "Found match for $testRoll: " . $match['name'] . "\n";
} else {
    echo "No match found for $testRoll\n";
}
?>
