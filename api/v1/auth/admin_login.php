<?php
/**
 * REST API v1: Admin / Faculty Email + Password Login
 * POST /api/v1/auth/admin_login.php
 * Body (JSON): { "email": "admin@pvpsit.ac.in", "password": "..." }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../../config.php';
global $pdo;

ApiResponse::initCors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed. Use POST.', 405);
}

$input = ApiResponse::getJsonInput();
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($email) || empty($password)) {
    ApiResponse::error('Email and password are required', 422);
}

try {
    $stmt = $pdo->prepare("SELECT id, email, password, role, full_name FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $tokenData = ApiAuth::createToken($pdo, $user['email'], 'admin', 30);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        unset($user['password']);

        ApiResponse::success([
            'auth' => $tokenData,
            'user' => $user
        ], 'Login successful');
    } else {
        ApiResponse::error('Invalid email or password', 401);
    }
} catch (PDOException $e) {
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
