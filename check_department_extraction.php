<?php
require_once 'config.php';

echo "<h2>Check Department Extraction for Specific Files</h2>";

// Check the specific files mentioned
$files = ['AIML II - I.pdf', '2-1 S PVP23 AR-IT 23.pdf'];

foreach ($files as $file) {
    echo "<h3>File: $file</h3>";
    $query = $pdo->prepare("SELECT * FROM student_results WHERE file_name = ?");
    $query->execute([$file]);
    $records = $query->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($records);
    echo "</pre>";
}

// Check all unique file names and their exam_info
echo "<h3>All file names with exam_info:</h3>";
$query = $pdo->query("SELECT DISTINCT file_name, exam_info FROM student_results WHERE file_name IS NOT NULL AND file_name != '' ORDER BY file_name");
$files = $query->fetchAll(PDO::FETCH_ASSOC);
echo "<pre)";
print_r($files);
echo "</pre>";

// Try better department extraction
echo "<h3>Improved department extraction from file_name:</h3>";
$query = $pdo->query("
    SELECT DISTINCT 
        file_name,
        CASE 
            WHEN file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' THEN REGEXP_SUBSTR(file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)')
            WHEN file_name REGEXP '[A-Z]{2,}' THEN REGEXP_SUBSTR(file_name, '[A-Z]{2,}')
            ELSE 'Unknown'
        END as extracted_dept
    FROM student_results 
    WHERE file_name IS NOT NULL AND file_name != ''
    LIMIT 20
");
$results = $query->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($results);
echo "</pre>";
?>
