<?php
require_once 'config/db.php';

echo "Checking users table structure:\n";
$result = $conn->query('DESCRIBE users');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . PHP_EOL;
}

echo "\n\nCurrent users:\n";
$result = $conn->query('SELECT id, email FROM users');
while($row = $result->fetch_assoc()) {
    echo $row['id'] . ' - ' . $row['email'] . PHP_EOL;
}
?>
