<?php
require_once 'config.php';

$stmt = $pdo->prepare('UPDATE course_codes SET credits = 2.0 WHERE course_code = ?');
$stmt->execute(['23AC1301']);
echo 'Updated 23AC1301 credits to 2.0 CR';
?>
