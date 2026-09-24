<?php
require_once 'config.php';

// Fix collation for smartscore table to match student_results
$sql = "ALTER TABLE smartscore 
        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";

try {
    $pdo->exec($sql);
    echo "✓ Successfully updated smartscore table collation to utf8mb4_general_ci<br>";
    echo "<br><a href='leaderboard.php'>Go to Leaderboard</a>";
} catch (PDOException $e) {
    echo "✗ Error updating collation: " . $e->getMessage();
}
?>
