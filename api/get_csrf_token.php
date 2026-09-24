<?php
/**
 * Get CSRF token
 * Returns a fresh CSRF token for the session
 */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// Generate new token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
