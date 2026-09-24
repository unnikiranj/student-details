<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'list') {
        // Get all users
        $stmt = $pdo->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users]);
    } elseif ($action === 'add') {
        // Add new user
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        if (empty($fullName) || empty($email) || empty($password) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullName, $email, $hashedPassword, $role]);
        
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } elseif ($action === 'update') {
        // Update existing user
        $userId = $_POST['user_id'];
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'] ?? '';

        if (empty($userId) || empty($fullName) || empty($email) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        // Check if email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        // Update user
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$fullName, $email, $hashedPassword, $role, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$fullName, $email, $role, $userId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } elseif ($action === 'delete') {
        // Delete user
        $userId = $_POST['user_id'];

        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }

        // Prevent deleting the current admin
        if ($userId == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
