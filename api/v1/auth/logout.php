<?php
/**
 * REST API v1: Logout and Revoke Bearer Token
 * POST /api/v1/auth/logout.php
 * Header: Authorization: Bearer <token>
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

ApiAuth::revokeToken($pdo);
ApiResponse::success(null, 'Logged out and token revoked successfully');
