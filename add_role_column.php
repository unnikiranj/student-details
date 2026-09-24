<?php
require_once 'config/db.php';

// Add role column to users table
$sql = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'placement', 'staff') DEFAULT 'staff' AFTER full_name";
if ($conn->query($sql)) {
    echo "Role column added successfully to users table.\n";
} else {
    echo "Error adding role column: " . $conn->error . "\n";
}

// Update existing admin user
$sql = "UPDATE users SET role = 'admin' WHERE email = 'admin@example.com'";
if ($conn->query($sql)) {
    echo "Admin role updated successfully.\n";
} else {
    echo "Error updating admin role: " . $conn->error . "\n";
}

echo "\nUpdated users table structure:\n";
$result = $conn->query('DESCRIBE users');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . PHP_EOL;
}
?>
