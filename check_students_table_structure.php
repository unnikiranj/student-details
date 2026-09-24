<?php
require_once 'config/db.php';

echo "Checking available tables:\n";
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}

echo "\n\nChecking if 'students' table exists:\n";
$result = $conn->query("SHOW TABLES LIKE 'students'");
if ($result->num_rows > 0) {
    echo "Table 'students' exists\n\n";
    
    echo "Structure of 'students' table:\n";
    $result = $conn->query("DESCRIBE students");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . "\n";
    }
    
    echo "\n\nSample data from 'students' table:\n";
    $result = $conn->query("SELECT * FROM students LIMIT 5");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            print_r($row);
            echo "\n";
        }
    } else {
        echo "No data in students table\n";
    }
} else {
    echo "Table 'students' does not exist\n";
}
?>
