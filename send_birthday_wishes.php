<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    // Get today's date
    $today = date('m-d');
    
    // Get students whose birthday is today and have email addresses
    $stmt = $pdo->prepare("
        SELECT roll_no, student_name, date_of_birth, department, email 
        FROM students 
        WHERE DATE_FORMAT(date_of_birth, '%m-%d') = ? 
        AND date_of_birth IS NOT NULL 
        AND email IS NOT NULL 
        AND email != ''
        ORDER BY student_name ASC
    ");
    $stmt->execute([$today]);
    $birthdayStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($birthdayStudents)) {
        echo json_encode([
            'success' => true,
            'message' => 'No birthdays found today',
            'count' => 0,
            'date' => date('F j, Y')
        ]);
        exit;
    }
    
    // Email configuration
    $mail = new PHPMailer(true);
    
    // SMTP configuration (update these settings according to your email server)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com'; // Change to your email
    $mail->Password = 'your-app-password'; // Change to your email password/app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Set sender
    $mail->setFrom('your-email@gmail.com', 'Student Details System');
    
    $sentCount = 0;
    $failedCount = 0;
    $results = [];
    
    // Send birthday wishes to each student
    foreach ($birthdayStudents as $student) {
        try {
            $mail->clearAddresses();
            $mail->addAddress($student['email'], $student['student_name']);
            
            // Calculate age
            $birthDate = new DateTime($student['date_of_birth']);
            $todayDate = new DateTime('today');
            $age = $todayDate->diff($birthDate)->y;
            
            $mail->Subject = 'Happy Birthday ' . $student['student_name'] . '!';
            
            $mail->Body = "Dear {$student['student_name']},\n\n" .
                          "Wishing you a very Happy Birthday! 🎂\n\n" .
                          "May this special day bring you joy, happiness, and all the success you deserve.\n" .
                          "Happy {$age}th Birthday!\n\n" .
                          "Best regards,\n" .
                          "Student Details System\n" .
                          "Department: {$student['department']}\n" .
                          "Roll No: {$student['roll_no']}";
            
            $mail->AltBody = "Dear {$student['student_name']},\n\n" .
                            "Wishing you a very Happy Birthday!\n\n" .
                            "May this special day bring you joy, happiness, and all the success you deserve.\n" .
                            "Happy {$age}th Birthday!\n\n" .
                            "Best regards,\n" .
                            "Student Details System";
            
            $mail->send();
            $sentCount++;
            $results[] = [
                'roll_no' => $student['roll_no'],
                'name' => $student['student_name'],
                'email' => $student['email'],
                'status' => 'sent'
            ];
            
        } catch (Exception $e) {
            $failedCount++;
            $results[] = [
                'roll_no' => $student['roll_no'],
                'name' => $student['student_name'],
                'email' => $student['email'],
                'status' => 'failed',
                'error' => $mail->ErrorInfo
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Birthday wishes sent to {$sentCount} students. Failed: {$failedCount}",
        'total' => count($birthdayStudents),
        'sent' => $sentCount,
        'failed' => $failedCount,
        'date' => date('F j, Y'),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
