# OTP Logging System

## Overview
A comprehensive logging system has been implemented to track all OTP-related activities in the student portal. This helps in monitoring OTP usage, troubleshooting issues, and maintaining security audit trails.

## Files Created/Modified

### New Files
- `api/otp_logger.php` - OTP logging class with various logging methods
- `OTP_LOGGING.md` - This documentation file

### Modified Files
- `api/send_otp.php` - Added logging for OTP generation and sending
- `api/verify_otp.php` - Added logging for OTP verification attempts

## Log File Location
Logs are stored in the `logs/` directory with the following naming convention:
- `logs/otp_YYYY-MM-DD.log` (e.g., `logs/otp_2026-08-06.log`)

## Log Format
Each log entry follows this format:
```
[YYYY-MM-DD HH:MM:SS] [LOG_LEVEL] Message | Context: {"key":"value"}
```

### Log Levels
- **INFO** - General informational messages
- **SUCCESS** - Successful operations
- **WARNING** - Potential issues (failed attempts, expired OTPs, etc.)
- **ERROR** - Errors and failures

## Logged Events

### 1. OTP Generation
- When an OTP is generated for a student
- Includes roll number, expiry time, and validity duration

### 2. OTP Sending
- **Success**: When OTP is successfully sent via email
- **Failure**: When email sending fails (includes error details)

### 3. OTP Verification Attempts
- **Success**: When OTP is verified correctly
- **Failure**: When OTP verification fails (includes attempt number)

### 4. OTP Expiry
- When a student tries to use an expired OTP

### 5. Too Many Failed Attempts
- When a student exceeds the maximum allowed attempts (3)

### 6. Authentication Success
- When a student is successfully authenticated via OTP

### 7. Validation Errors
- When input validation fails (invalid roll number format, missing fields, etc.)

## Example Log Entries

```
[2026-08-06 07:33:05] [INFO] OTP generated | Context: {"roll_number":"24501A1201","expires_at":"2026-08-06 07:38:05","expiry_seconds":300}
[2026-08-06 07:33:05] [SUCCESS] OTP sent successfully | Context: {"roll_number":"24501A1201","email":"24501A1201@pvpsit.ac.in"}
[2026-08-06 07:33:05] [ERROR] OTP sending failed | Context: {"roll_number":"24501A1201","email":"24501A1201@pvpsit.ac.in","error":"SMTP connection failed"}
[2026-08-06 07:33:05] [SUCCESS] OTP verified successfully | Context: {"roll_number":"24501A1201","is_valid":true}
[2026-08-06 07:33:05] [WARNING] OTP verification failed | Context: {"roll_number":"24501A1201","is_valid":false,"attempt_number":2}
[2026-08-06 07:33:05] [WARNING] OTP expired | Context: {"roll_number":"24501A1201"}
[2026-08-06 07:33:05] [WARNING] Too many failed OTP attempts | Context: {"roll_number":"24501A1201","attempts":3}
[2026-08-06 07:33:05] [SUCCESS] Student authenticated via OTP | Context: {"roll_number":"24501A1201","auth_time":"2026-08-06 07:33:05"}
[2026-08-06 07:33:05] [WARNING] Validation error | Context: {"field":"roll_number","value":"invalid@roll","reason":"Invalid roll number format"}
```

## OtpLogger Class Methods

### Public Methods
- `log($level, $message, $context)` - Generic logging method
- `logOtpGenerated($rollNumber, $expiryTime)` - Log OTP generation
- `logOtpSent($rollNumber, $email)` - Log successful OTP sending
- `logOtpSendFailed($rollNumber, $email, $error)` - Log OTP sending failure
- `logOtpVerificationAttempt($rollNumber, $isValid, $attemptNumber)` - Log verification attempts
- `logOtpExpired($rollNumber)` - Log expired OTP usage
- `logTooManyAttempts($rollNumber, $attempts)` - Log too many failed attempts
- `logAuthenticationSuccess($rollNumber)` - Log successful authentication
- `logValidationError($field, $value, $reason)` - Log validation errors

## Usage Example

```php
require_once 'api/otp_logger.php';

// Log OTP generation
OtpLogger::logOtpGenerated('24501A1201', time() + 300);

// Log successful OTP send
OtpLogger::logOtpSent('24501A1201', '24501A1201@pvpsit.ac.in');

// Log verification attempt
OtpLogger::logOtpVerificationAttempt('24501A1201', true);
```

## Security Considerations

- Logs do not contain actual OTP values for security reasons
- Sensitive information like passwords is never logged
- Log files are stored in the `logs/` directory which is gitignored
- Logs are written with file locking to prevent corruption in concurrent requests

## Maintenance

- Log files are created daily (date-based naming)
- Consider implementing log rotation for long-running deployments
- Regular monitoring of ERROR and WARNING logs is recommended for security
- Old log files can be archived or deleted based on retention policies

## Troubleshooting

If logs are not being created:
1. Check that the `logs/` directory exists and is writable
2. Verify PHP has write permissions to the logs directory
3. Check that `otp_logger.php` is properly included in the OTP files
4. Review PHP error logs for any file system errors
