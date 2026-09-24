<?php
require_once 'config.php';

echo "Checking course_codes table...\n";

// Check if the specific course codes exist
$testCodes = ['23AC1301', '23CP21', '23AM3301', '23AM3302', '23AM3351', '23AM3352', '23BS1305', '23ES1305', '23HS1301', '23SO8355'];
foreach ($testCodes as $code) {
    $stmt = $pdo->prepare("SELECT course_code, course_name, credits, semester, year, branch FROM course_codes WHERE course_code = ?");
    $stmt->execute([$code]);
    $result = $stmt->fetch();
    if ($result) {
        echo "✓ Found: $code - {$result['course_name']} - {$result['credits']} CR (Sem: {$result['semester']}, Year: {$result['year']})\n";
    } else {
        echo "✗ Not found: $code\n";
    }
}

// Show all course codes
echo "\nAll course codes in table:\n";
$stmt = $pdo->query("SELECT course_code, course_name, credits FROM course_codes LIMIT 20");
while ($row = $stmt->fetch()) {
    echo "{$row['course_code']} - {$row['course_name']} - {$row['credits']} CR\n";
}

echo "\nTotal records: ";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM course_codes");
$result = $stmt->fetch();
echo $result['count'] . "\n";
?>
