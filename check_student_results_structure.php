<?php
require_once 'config.php';

echo "<h2>Check student_results table structure</h3>";
$columnsQuery = $pdo->query("DESCRIBE student_results");
$columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";

echo "<h3>Sample data from student_results:</h3>";
$sampleQuery = $pdo->query("SELECT * FROM student_results LIMIT 3");
$sample = $sampleQuery->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($sample);
echo "</pre>";

echo "<h3>Check if there's a branch column:</h3>";
$branchQuery = $pdo->query("SELECT DISTINCT branch FROM student_results WHERE branch IS NOT NULL AND branch != '' LIMIT 10");
$branches = $branchQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($branches);
echo "</pre>";
?>
