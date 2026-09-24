<?php
/**
 * REST API Authentication, Bearer Token & Stateless OTP Middleware
 */

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../../../config.php';

class ApiAuth {
    private static $tableInitialized = false;

    public static function ensureTableExists($pdo) {
        if (self::$tableInitialized) return;
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `api_tokens` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_type` VARCHAR(20) NOT NULL DEFAULT 'student',
                    `identifier` VARCHAR(50) NOT NULL,
                    `token` VARCHAR(128) NOT NULL UNIQUE,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`token`),
                    INDEX (`identifier`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `api_otps` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `roll_number` VARCHAR(50) NOT NULL,
                    `otp` VARCHAR(10) NOT NULL,
                    `attempts` INT DEFAULT 0,
                    `expires_at` INT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`roll_number`),
                    INDEX (`expires_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            self::$tableInitialized = true;
        } catch (Throwable $e) {
            // Ignore if already created
        }
    }

    /**
     * Store OTP in Database (Stateless for Mobile & Web)
     */
    public static function storeOtp($pdo, $rollNumber, $otp, $ttlSeconds = 300) {
        self::ensureTableExists($pdo);
        $cleanRoll = strtoupper(trim($rollNumber));
        $expiresAt = time() + $ttlSeconds;

        // Clean any existing/old OTPs for this roll number
        $cleanStmt = $pdo->prepare("DELETE FROM api_otps WHERE roll_number = ? OR expires_at < ?");
        $cleanStmt->execute([$cleanRoll, time()]);

        $stmt = $pdo->prepare("INSERT INTO api_otps (roll_number, otp, attempts, expires_at) VALUES (?, ?, 0, ?)");
        $stmt->execute([$cleanRoll, (string)$otp, $expiresAt]);

        return true;
    }

    /**
     * Verify OTP from Database
     * Returns: ['success' => bool, 'error' => string|null, 'remaining' => int]
     */
    public static function verifyOtp($pdo, $rollNumber, $otp) {
        self::ensureTableExists($pdo);
        $cleanRoll = strtoupper(trim($rollNumber));
        $now = time();

        $stmt = $pdo->prepare("SELECT id, otp, attempts, expires_at FROM api_otps WHERE roll_number = ? AND expires_at >= ? LIMIT 1");
        $stmt->execute([$cleanRoll, $now]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return [
                'success' => false,
                'error' => 'OTP has expired or has not been requested. Please request a new OTP.',
                'remaining' => 0
            ];
        }

        if ($record['attempts'] >= 3) {
            return [
                'success' => false,
                'error' => 'Too many failed OTP attempts. Please request a new OTP.',
                'remaining' => 0
            ];
        }

        if ((string)$record['otp'] !== (string)$otp) {
            $newAttempts = $record['attempts'] + 1;
            $upd = $pdo->prepare("UPDATE api_otps SET attempts = ? WHERE id = ?");
            $upd->execute([$newAttempts, $record['id']]);
            $remaining = max(0, 3 - $newAttempts);
            return [
                'success' => false,
                'error' => "Invalid OTP. $remaining attempts remaining.",
                'remaining' => $remaining
            ];
        }

        // OTP is valid! Delete record
        $del = $pdo->prepare("DELETE FROM api_otps WHERE roll_number = ?");
        $del->execute([$cleanRoll]);

        return [
            'success' => true,
            'error' => null,
            'remaining' => 3
        ];
    }

    /**
     * Create a new secure Bearer token for a student or admin user
     */
    public static function createToken($pdo, $identifier, $userType = 'student', $expiryDays = 30) {
        self::ensureTableExists($pdo);
        
        $token = bin2hex(random_bytes(32)); // 64 hex characters
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryDays days"));
        $cleanIdentifier = strtoupper(trim($identifier));

        // Delete any expired tokens for this user
        $cleanStmt = $pdo->prepare("DELETE FROM api_tokens WHERE identifier = ? AND expires_at < NOW()");
        $cleanStmt->execute([$cleanIdentifier]);

        $stmt = $pdo->prepare("INSERT INTO api_tokens (user_type, identifier, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userType, $cleanIdentifier, $token, $expiresAt]);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
            'expires_in' => $expiryDays * 86400
        ];
    }

    /**
     * Extract Bearer token from HTTP Authorization Header or request
     */
    public static function getBearerToken() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
                return $matches[1];
            }
        }

        // Fallback: check query parameter or request body for token
        if (!empty($_REQUEST['api_token'])) {
            return trim($_REQUEST['api_token']);
        }
        if (!empty($_REQUEST['token'])) {
            return trim($_REQUEST['token']);
        }

        return null;
    }

    /**
     * Authenticate and return the active user data
     */
    public static function requireAuth($pdo, $requiredType = null) {
        self::ensureTableExists($pdo);
        $token = self::getBearerToken();

        if (!$token) {
            // Check PHP Session fallback
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!empty($_SESSION['student_authenticated']) && !empty($_SESSION['student_roll_no'])) {
                return [
                    'user_type' => 'student',
                    'identifier' => strtoupper($_SESSION['student_roll_no']),
                    'auth_method' => 'session'
                ];
            }
            if (!empty($_SESSION['user_id']) && !empty($_SESSION['email'])) {
                return [
                    'user_type' => 'admin',
                    'identifier' => $_SESSION['email'],
                    'auth_method' => 'session'
                ];
            }

            ApiResponse::error('Authentication required. Missing Bearer token.', 401);
        }

        $stmt = $pdo->prepare("SELECT user_type, identifier, expires_at FROM api_tokens WHERE token = ? AND expires_at >= NOW() LIMIT 1");
        $stmt->execute([$token]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRecord) {
            ApiResponse::error('Invalid or expired authentication token. Please log in again.', 401);
        }

        if ($requiredType && $tokenRecord['user_type'] !== $requiredType && $tokenRecord['user_type'] !== 'admin') {
            ApiResponse::error('Access denied. Insufficient permissions.', 403);
        }

        return [
            'user_type' => $tokenRecord['user_type'],
            'identifier' => strtoupper($tokenRecord['identifier']),
            'auth_method' => 'bearer_token'
        ];
    }

    /**
     * Revoke / logout token
     */
    public static function revokeToken($pdo, $token = null) {
        self::ensureTableExists($pdo);
        if (!$token) {
            $token = self::getBearerToken();
        }
        if ($token) {
            $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE token = ?");
            $stmt->execute([$token]);
        }
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        @session_destroy();
        return true;
    }
}
