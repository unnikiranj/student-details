<?php
require_once 'config.php';

echo "Columns in attendance_summary table:\n";
$stmt = $pdo->query('DESCRIBE attendance_summary');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . "\n";
}
