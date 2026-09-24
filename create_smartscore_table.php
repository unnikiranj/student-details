<?php
require_once 'config.php';

// SQL to create smartscore table
$sql = "CREATE TABLE IF NOT EXISTS smartscore (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rank INT,
    global_rank INT,
    roll_number VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    mobile VARCHAR(20),
    overall_score DECIMAL(5, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_roll_number (roll_number),
    INDEX idx_overall_score (overall_score DESC),
    INDEX idx_global_rank (global_rank)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $pdo->exec($sql);
    echo "✓ Table 'smartscore' created successfully!<br>";
    echo "<br>Table structure:<br>";
    echo "<ul>";
    echo "<li>id: INT (Primary Key, Auto Increment)</li>";
    echo "<li>rank: INT</li>";
    echo "<li>global_rank: INT</li>";
    echo "<li>roll_number: VARCHAR(255) (Unique)</li>";
    echo "<li>name: VARCHAR(255)</li>";
    echo "<li>mobile: VARCHAR(20)</li>";
    echo "<li>overall_score: DECIMAL(5, 2)</li>";
    echo "<li>created_at: TIMESTAMP</li>";
    echo "<li>updated_at: TIMESTAMP</li>";
    echo "</ul>";
    echo "<br>Indexes created on: roll_number, overall_score (DESC), global_rank<br>";
    echo "<br><a href='index.php'>Back to Home</a>";
} catch (PDOException $e) {
    echo "✗ Error creating table: " . $e->getMessage();
}
?>
