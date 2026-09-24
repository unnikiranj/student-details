<?php
/**
 * OTP Logger
 * Handles logging for OTP generation, sending, and verification
 */

class OtpLogger {
    private static $logFile;
    private static $logDir;
    
    public static function init() {
        self::$logDir = __DIR__ . '/../logs';
        self::$logFile = self::$logDir . '/otp_' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    /**
     * Log OTP-related events
     * @param string $level - LOG LEVEL (INFO, SUCCESS, ERROR, WARNING)
     * @param string $message - Log message
     * @param array $context - Additional context data
     */
    public static function log($level, $message, $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
        
        // Write to log file
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log OTP generation
     */
    public static function logOtpGenerated($rollNumber, $expiryTime) {
        self::log('INFO', 'OTP generated', [
            'roll_number' => $rollNumber,
            'expires_at' => date('Y-m-d H:i:s', $expiryTime),
            'expiry_seconds' => $expiryTime - time()
        ]);
    }
    
    /**
     * Log OTP sent successfully
     */
    public static function logOtpSent($rollNumber, $email) {
        self::log('SUCCESS', 'OTP sent successfully', [
            'roll_number' => $rollNumber,
            'email' => $email
        ]);
    }
    
    /**
     * Log OTP sending failure
     */
    public static function logOtpSendFailed($rollNumber, $email, $error) {
        self::log('ERROR', 'OTP sending failed', [
            'roll_number' => $rollNumber,
            'email' => $email,
            'error' => $error
        ]);
    }
    
    /**
     * Log OTP verification attempt
     */
    public static function logOtpVerificationAttempt($rollNumber, $isValid, $attemptNumber = null) {
        $level = $isValid ? 'SUCCESS' : 'WARNING';
        $message = $isValid ? 'OTP verified successfully' : 'OTP verification failed';
        
        $context = [
            'roll_number' => $rollNumber,
            'is_valid' => $isValid
        ];
        
        if ($attemptNumber !== null) {
            $context['attempt_number'] = $attemptNumber;
        }
        
        self::log($level, $message, $context);
    }
    
    /**
     * Log OTP expired
     */
    public static function logOtpExpired($rollNumber) {
        self::log('WARNING', 'OTP expired', [
            'roll_number' => $rollNumber
        ]);
    }
    
    /**
     * Log too many failed attempts
     */
    public static function logTooManyAttempts($rollNumber, $attempts) {
        self::log('WARNING', 'Too many failed OTP attempts', [
            'roll_number' => $rollNumber,
            'attempts' => $attempts
        ]);
    }
    
    /**
     * Log student authentication success
     */
    public static function logAuthenticationSuccess($rollNumber) {
        self::log('SUCCESS', 'Student authenticated via OTP', [
            'roll_number' => $rollNumber,
            'auth_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Log validation errors
     */
    public static function logValidationError($field, $value, $reason) {
        self::log('WARNING', 'Validation error', [
            'field' => $field,
            'value' => $value,
            'reason' => $reason
        ]);
    }
}
