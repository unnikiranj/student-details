<?php
require_once 'config.php';

echo "<h2>Check Department Information Sources</h2>";

// Check exam_info for department patterns
echo "<h3>Departments from exam_info field:</h3>";
$examQuery = $pdo->query("SELECT DISTINCT exam_info FROM student_results WHERE exam_info IS NOT NULL AND exam_info != '' LIMIT 20");
$examInfo = $examQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($examInfo);
echo "</pre>";

// Extract departments from exam_info
echo "<h3>Extracted departments from exam_info:</h3>";
$deptPatternQuery = $pdo->query("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(exam_info, ' ', -1), '-', 1) as dept FROM student_results WHERE exam_info IS NOT NULL AND exam_info != '' AND exam_info REGEXP '[A-Z]{2,}-'");
$extractedDepts = $deptPatternQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($extractedDepts);
echo "</pre>";

// Check file_name for department patterns
echo "<h3>File names with department info:</h3>";
$fileQuery = $pdo->query("SELECT DISTINCT file_name FROM student_results WHERE file_name IS NOT NULL AND file_name != '' LIMIT 20");
$fileNames = $fileQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($fileNames);
echo "</pre>";

// Extract departments from file_name
echo "<h3>Extracted departments from file_name:</h3>";
$fileDeptQuery = $pdo->query("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(file_name, ' ', -2), ' ', 1) as dept FROM student_results WHERE file_name IS NOT NULL AND file_name != '' AND file_name REGEXP '[A-Z]{2,}'");
$fileDepts = $fileDeptQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($fileDepts);
echo "</pre>";

// Check roll number patterns
echo "<h3>Roll number patterns:</h3>";
$rollQuery = $pdo->query("SELECT DISTINCT roll_no FROM student_results LIMIT 10");
$rollNos = $rollQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($rollNos);
echo "</pre>";

// Extract department codes from roll numbers (assuming format like 23501A1267 where A might be department)
echo "<h3>Extracted department codes from roll numbers:</h3>";
$rollDeptQuery = $pdo->query("SELECT DISTINCT SUBSTRING(roll_no, 6, 1) as dept_code FROM student_results WHERE roll_no IS NOT NULL AND roll_no != '' AND LENGTH(roll_no) >= 6");
$rollDepts = $rollDeptQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($rollDepts);
echo "</pre>";

// Try to get departments from a combination approach
echo "<h3>Combined department extraction:</h3>";
$combinedQuery = $pdo->query("
    SELECT DISTINCT 
        CASE 
            WHEN department IS NOT NULL AND department != '' THEN department
            WHEN exam_info REGEXP '[A-Z]{2,}-' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(exam_info, ' ', -1), '-', 1)
            WHEN file_name REGEXP '[A-Z]{2,}' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(file_name, ' ', -2), ' ', 1)
            ELSE 'Unknown'
        END as extracted_dept
    FROM student_results 
    WHERE cgpa IS NOT NULL AND cgpa != '' AND cgpa != 'N/A'
    LIMIT 50
");
$combinedDepts = $combinedQuery->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($combinedDepts);
echo "</pre>";
?>
