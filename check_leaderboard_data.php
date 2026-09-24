<?php
require_once 'config.php';

echo "<h2>Check Leaderboard Data</h2>";

// Check departments
echo "<h3>Departments in student_results table:</h3>";
$deptQuery = $pdo->query("SELECT DISTINCT department FROM student_results WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = $deptQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($departments);
echo "</pre>";
echo "Total departments: " . count($departments) . "<br>";

// Check semesters
echo "<h3>Semesters in student_results table:</h3>";
$semQuery = $pdo->query("SELECT DISTINCT semester_info FROM student_results WHERE semester_info IS NOT NULL AND semester_info != '' ORDER BY semester_info");
$semesters = $semQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($semesters);
echo "</pre>";
echo "Total semesters: " . count($semesters) . "<br>";

// Check raw data with potential whitespace issues
echo "<h3>Departments with whitespace check:</h3>";
$deptRawQuery = $pdo->query("SELECT DISTINCT TRIM(department) as dept FROM student_results WHERE department IS NOT NULL AND TRIM(department) != '' ORDER BY TRIM(department)");
$deptClean = $deptRawQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($deptClean);
echo "</pre>";

echo "<h3>Semesters with whitespace check:</h3>";
$semRawQuery = $pdo->query("SELECT DISTINCT TRIM(semester_info) as sem FROM student_results WHERE semester_info IS NOT NULL AND TRIM(semester_info) != '' ORDER BY TRIM(semester_info)");
$semClean = $semRawQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($semClean);
echo "</pre>";

// Check total records
echo "<h3>Total records in student_results:</h3>";
$countQuery = $pdo->query("SELECT COUNT(*) as total FROM student_results");
$total = $countQuery->fetch(PDO::FETCH_ASSOC);
echo "Total: " . $total['total'] . "<br>";

// Check records with valid CGPA
echo "<h3>Records with valid CGPA:</h3>";
$cgpaQuery = $pdo->query("SELECT COUNT(*) as total FROM student_results WHERE cgpa IS NOT NULL AND cgpa != '' AND cgpa != 'N/A'");
$cgpaTotal = $cgpaQuery->fetch(PDO::FETCH_ASSOC);
echo "Total with valid CGPA: " . $cgpaTotal['total'] . "<br>";
?>
