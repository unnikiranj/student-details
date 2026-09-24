<?php
/**
 * send_daily_attendance.php
 * 
 * Sends daily attendance emails to all students with their latest month attendance
 * Email format: roll_number@pvpsit.ac.in
 * 
 * Run via cron job daily at 4:45 PM: 45 16 * * * php /path/to/send_daily_attendance.php
 */

require_once 'config.php';

// Email configuration (using existing OTP credentials)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'unnikiran@pvpsiddhartha.ac.in');
define('SMTP_PASS', 'byxq jgii ievn nhgd');
define('SMTP_FROM', 'unnikiran@pvpsiddhartha.ac.in');
define('SMTP_FROM_NAME', 'PVPSIT Student Portal');

// SMS configuration (MSG91)
define('MSG91_AUTH_KEY', 'YOUR_MSG91_AUTH_KEY');
define('MSG91_SENDER_ID', 'PVPSIT');
define('MSG91_ROUTE', '4'); // Route 4 for promotional SMS, Route 3 for transactional
define('MSG91_COUNTRY', '91'); // India country code

// Command line options
$options = getopt('', ['send-sms', 'send-email', 'help']);

if (isset($options['help'])) {
    echo "Usage: php send_daily_attendance.php [options]\n";
    echo "Options:\n";
    echo "  --send-sms    Send SMS only (skip email)\n";
    echo "  --send-email  Send email only (skip SMS)\n";
    echo "  --help        Show this help message\n";
    echo "\nIf no options specified, both email and SMS will be sent (if configured).\n";
    exit(0);
}

$sendSmsOnly = isset($options['send-sms']);
$sendEmailOnly = isset($options['send-email']);
$sendBoth = !$sendSmsOnly && !$sendEmailOnly;

// Log file
$logFile = __DIR__ . '/logs/daily_attendance_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    logMessage("Starting daily attendance email process");
    
    // Get all unique roll numbers from attendance_summary
    $stmt = $pdo->query("SELECT DISTINCT roll_no FROM attendance_summary WHERE roll_no IS NOT NULL AND roll_no != ''");
    $rollNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    logMessage("Found " . count($rollNumbers) . " students with attendance data");
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($rollNumbers as $rollNo) {
        try {
            $rollNo = trim($rollNo);
            if (empty($rollNo)) continue;
            
            // Skip roll numbers starting with 22
            if (stripos($rollNo, '22') === 0) {
                logMessage("Skipped roll no starting with 22: $rollNo");
                continue;
            }
            
            // Get latest attendance for this student
            $stmt = $pdo->prepare("
                SELECT 
                    roll_no,
                    session_name,
                    total_present,
                    total_classes,
                    percentage,
                    created_at
                FROM attendance_summary 
                WHERE roll_no = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$rollNo]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$attendance) {
                logMessage("No attendance found for roll no: $rollNo");
                continue;
            }

            // Get subject-wise attendance for the same session
            $stmt = $pdo->prepare("
                SELECT 
                    subject_code,
                    subject_name,
                    present,
                    extra,
                    total
                FROM attendance_subjects 
                WHERE roll_no = ? AND session_name = ?
                ORDER BY subject_code
            ");
            $stmt->execute([$rollNo, $attendance['session_name']]);
            $subjectAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get student name if available
            $stmt = $pdo->prepare("SELECT student_name, phone FROM students WHERE roll_no = ? LIMIT 1");
            $stmt->execute([$rollNo]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            $studentName = $student['student_name'] ?? 'Student';
            $studentPhone = $student['phone'] ?? '';
            
            // Create email address from roll number
            $email = strtolower($rollNo) . '@pvpsit.ac.in';
            
            // Calculate attendance status
            $percentage = floatval($attendance['percentage']);
            $status = $percentage >= 75 ? 'Safe' : ($percentage >= 65 ? 'Condonation' : 'Detention');
            $statusColor = $percentage >= 75 ? 'green' : ($percentage >= 65 ? 'orange' : 'red');
            
            // Prepare email content
            $subject = "Daily Attendance Update - $rollNo - " . date('d M Y');
            
            // Prepare SMS message (shorter format)
            $smsMessage = "PVPSIT Attendance Update: $studentName ($rollNo). Attendance: {$attendance['percentage']}%. Status: $status. Check email for details.";
            
            // Build subject-wise attendance table
            $subjectTable = '';
            if (!empty($subjectAttendance)) {
                $subjectTable = "
                <div class='attendance-card'>
                    <h4 style='margin-top: 0; color: #667eea;'>Subject-wise Attendance</h4>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                        <thead>
                            <tr style='background: #f0f0f0;'>
                                <th style='padding: 8px; text-align: left; border: 1px solid #ddd;'>Subject Code</th>
                                <th style='padding: 8px; text-align: left; border: 1px solid #ddd;'>Subject Name</th>
                                <th style='padding: 8px; text-align: center; border: 1px solid #ddd;'>Present</th>
                                <th style='padding: 8px; text-align: center; border: 1px solid #ddd;'>Total</th>
                                <th style='padding: 8px; text-align: center; border: 1px solid #ddd;'>%</th>
                            </tr>
                        </thead>
                        <tbody>";
                foreach ($subjectAttendance as $subject) {
                    $subjectPercentage = $subject['total'] > 0 ? round(($subject['present'] / $subject['total']) * 100, 2) : 0;
                    $subjectStatusColor = $subjectPercentage >= 75 ? '#28a745' : ($subjectPercentage >= 65 ? '#ffc107' : '#dc3545');
                    $subjectTable .= "
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($subject['subject_code']) . "</td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($subject['subject_name']) . "</td>
                            <td style='padding: 8px; text-align: center; border: 1px solid #ddd;'>{$subject['present']}</td>
                            <td style='padding: 8px; text-align: center; border: 1px solid #ddd;'>{$subject['total']}</td>
                            <td style='padding: 8px; text-align: center; border: 1px solid #ddd; color: $subjectStatusColor; font-weight: bold;'>$subjectPercentage%</td>
                        </tr>";
                }
                $subjectTable .= "
                        </tbody>
                    </table>
                </div>";
            }

            $message = "
            <html>
            <head>
                <title>Daily Attendance Update</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 700px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                    .attendance-card { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .stat-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                    .stat-label { font-weight: bold; color: #666; }
                    .stat-value { font-weight: bold; }
                    .status-safe { color: #28a745; }
                    .status-warning { color: #ffc107; }
                    .status-danger { color: #dc3545; }
                    .footer { background: #666; color: white; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Daily Attendance Update</h2>
                        <p>PVPSIT Attendance System</p>
                    </div>
                    <div class='content'>
                        <h3>Hello, $studentName ($rollNo)</h3>
                        <p>Here is your latest attendance update:</p>

                        <div class='attendance-card'>
                            <div class='stat-row'>
                                <span class='stat-label'>Session:</span>
                                <span class='stat-value'>" . htmlspecialchars($attendance['session_name'] ?? 'N/A') . "</span>
                            </div>
                            <div class='stat-row'>
                                <span class='stat-label'>Present:</span>
                                <span class='stat-value'>" . ($attendance['total_present'] ?? 0) . "</span>
                            </div>
                            <div class='stat-row'>
                                <span class='stat-label'>Total Classes:</span>
                                <span class='stat-value'>" . ($attendance['total_classes'] ?? 0) . "</span>
                            </div>
                            <div class='stat-row'>
                                <span class='stat-label'>Percentage:</span>
                                <span class='stat-value'>" . ($attendance['percentage'] ?? 0) . "%</span>
                            </div>
                            <div class='stat-row' style='border-bottom: none;'>
                                <span class='stat-label'>Status:</span>
                                <span class='stat-value status-$statusColor'>$status</span>
                            </div>
                        </div>

                        $subjectTable

                        <p><strong>Note:</strong> This is an automated email. Please check the student portal for detailed attendance information.</p>

                        <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 15px; margin: 15px 0; border-radius: 4px;'>
                            <p style='margin: 0; font-size: 13px; color: #856404;'>
                                <strong><i class='fas fa-exclamation-triangle'></i> Disclaimer:</strong> Unofficial view for convenience only. Final authority is the original/official records. Accuracy not guaranteed.
                            </p>
                        </div>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " PVPSIT Attendance System | Generated on " . date('d M Y H:i') . "</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Send email using PHPMailer (if not SMS-only mode)
            if (!$sendSmsOnly) {
                $emailResult = sendEmail($email, $subject, $message);

                if ($emailResult) {
                    logMessage("Email sent successfully to: $email");
                    $successCount++;
                } else {
                    logMessage("Failed to send email to: $email");
                    $failCount++;
                }
            } else {
                logMessage("Email skipped (SMS-only mode): $email");
            }

            // Send SMS if phone number is available and MSG91 is configured (if not email-only mode)
            if (!$sendEmailOnly) {
                if (!empty($studentPhone) && MSG91_AUTH_KEY !== 'YOUR_MSG91_AUTH_KEY') {
                    $smsResult = sendSMS($studentPhone, $smsMessage);
                    if ($smsResult) {
                        logMessage("SMS sent successfully to: $studentPhone");
                    } else {
                        logMessage("Failed to send SMS to: $studentPhone");
                    }
                } elseif (empty($studentPhone)) {
                    logMessage("No phone number found for: $rollNo");
                } else {
                    logMessage("SMS not sent - MSG91 not configured for: $rollNo");
                }
            } else {
                logMessage("SMS skipped (email-only mode): $rollNo");
            }
            
            // Small delay to prevent overwhelming the mail server
            usleep(100000); // 0.1 second
            
        } catch (Exception $e) {
            logMessage("Error processing roll no $rollNo: " . $e->getMessage());
            $failCount++;
        }
    }
    
    logMessage("Process completed. Success: $successCount, Failed: $failCount");
    
} catch (Exception $e) {
    logMessage("Fatal error: " . $e->getMessage());
}

/**
 * Send SMS using MSG91 API
 */
function sendSMS($phoneNumber, $message) {
    $authKey = MSG91_AUTH_KEY;
    $senderId = MSG91_SENDER_ID;
    $route = MSG91_ROUTE;
    $country = MSG91_COUNTRY;
    
    // Clean phone number - remove spaces, dashes, and ensure it starts with country code
    $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
    if (strlen($phone) == 10 && strpos($phone, '91') !== 0) {
        $phone = $country . $phone;
    }
    
    // Prepare API request
    $postData = [
        'authkey' => $authKey,
        'mobiles' => $phone,
        'message' => urlencode($message),
        'sender' => $senderId,
        'route' => $route,
        'country' => $country
    ];
    
    $url = "https://api.msg91.com/api/sendhttp.php";
    
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && strpos($response, 'success') !== false) {
            return true;
        } else {
            logMessage("SMS API Error: HTTP $httpCode - Response: $response");
            return false;
        }
    } catch (Exception $e) {
        logMessage("SMS Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email using PHPMailer
 */
function sendEmail($to, $subject, $message) {
    // Check if PHPMailer is available
    if (!file_exists(__DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
        logMessage("PHPMailer not found. Please install: composer require phpmailer/phpmailer");
        return false;
    }
    
    require __DIR__ . '/vendor/autoload.php';
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        logMessage("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
