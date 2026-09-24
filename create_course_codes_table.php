<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS course_codes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    credits DECIMAL(3,1) DEFAULT 0,
    regulation VARCHAR(50),
    branch VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_course (year, semester, course_code, branch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

try {
    $pdo->exec($sql);
    echo "✓ Successfully created course_codes table<br>";
    echo "<br><a href='index.php'>Back to Home</a>";
} catch (PDOException $e) {
    echo "✗ Error creating table: " . $e->getMessage();
}
?>
