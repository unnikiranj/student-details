<?php
require_once 'config.php';
$tables = ['attendance_summary', 'student_results', 'subject_results', 'smartscore'];
foreach ($tables as $t) {
    echo "=== TABLE: $t ===\n";
    $cols = $pdo->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  - {$c['Field']} ({$c['Type']})\n";
    }
    echo "\n";
}
