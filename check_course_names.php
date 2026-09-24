<?php
require_once 'config.php';

echo "Checking course names in course_codes table...\n\n";

$testCodes = ['23ES1553', '23HS1501', '23IT3501', '23IT3502', '23IT3503', '23IT3551', '23IT3552', '23SO8555', '23TNP31'];

foreach ($testCodes as $code) {
    $stmt = $pdo->prepare("SELECT course_code, course_name, credits FROM course_codes WHERE course_code = ?");
    $stmt->execute([$code]);
    $result = $stmt->fetch();
    if ($result) {
        echo "✓ $code: {$result['course_name']} - {$result['credits']} CR\n";
    } else {
        echo "✗ $code: NOT FOUND\n";
    }
}
?>
