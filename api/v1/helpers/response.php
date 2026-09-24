<?php
/**
 * Standard REST API Response Helper
 */

class ApiResponse {
    public static $exitOnResponse = true;

    public static function initCors() {
        if (!headers_sent()) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");
            header("Content-Type: application/json; charset=UTF-8");
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            if (self::$exitOnResponse) exit;
            return;
        }
    }

    public static function success($data = null, $message = 'Success', $code = 200) {
        self::initCors();
        http_response_code($code);

        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (self::$exitOnResponse) exit;
    }

    public static function error($message = 'An error occurred', $code = 400, $data = null) {
        self::initCors();
        http_response_code($code);

        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (self::$exitOnResponse) exit;
    }

    public static function getJsonInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (is_array($data)) {
            return $data;
        }
        return $_POST;
    }
}
