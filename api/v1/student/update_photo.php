<?php
/**
 * REST API v1: Update Student Profile Photo
 * POST /api/v1/student/update_photo.php
 * Header: Authorization: Bearer <token> (optional if roll_number provided in $_POST)
 * Body: Multipart Form Data with file field 'profile_photo' and optional 'roll_number'
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

$rollNumber = null;
if (!empty($_POST['roll_number'])) {
    $rollNumber = strtoupper(trim($_POST['roll_number']));
} elseif (!empty($_POST['roll'])) {
    $rollNumber = strtoupper(trim($_POST['roll']));
} else {
    $auth = ApiAuth::requireAuth($pdo);
    $rollNumber = $auth['identifier'];
}

if (empty($rollNumber)) {
    ApiResponse::error('Roll number is required', 400);
}

if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['profile_photo']['error'] ?? 'MISSING';
    ApiResponse::error("No file uploaded or upload error (code: $code)", 422, ['field' => 'profile_photo']);
}

$file = $_FILES['profile_photo'];

// Max size: 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    ApiResponse::error('File size exceeds 5MB limit', 422);
}

// Validate MIME type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp'];
$mimeType = '';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($file['tmp_name']);
}

if (!in_array($mimeType, $allowedTypes)) {
    ApiResponse::error('Only JPG, JPEG, and PNG images are allowed', 422);
}

$imageInfo = @getimagesize($file['tmp_name']);
if (!$imageInfo) {
    ApiResponse::error('Invalid image file', 422);
}

// Resize image to max 400x400 pixels
$maxWidth = 400;
$maxHeight = 400;
$width = $imageInfo[0];
$height = $imageInfo[1];

if ($width > $maxWidth || $height > $maxHeight) {
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)round($width * $ratio);
    $newHeight = (int)round($height * $ratio);
} else {
    $newWidth = (int)$width;
    $newHeight = (int)$height;
}

$fileContent = file_get_contents($file['tmp_name']);
$sourceImage = @imagecreatefromstring($fileContent);
if (!$sourceImage) {
    ApiResponse::error('Failed to process image format', 422);
}

$newImage = imagecreatetruecolor($newWidth, $newHeight);

if ($mimeType === 'image/png') {
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);
    $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
    imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
}

imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

$uploadDir = __DIR__ . '/../../../uploads/student_photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileExtension = ($mimeType === 'image/png') ? 'png' : 'jpg';
$cleanRoll = preg_replace('/[^0-9A-Za-z_-]/', '', $rollNumber);
$fileName = $cleanRoll . '_' . time() . '.' . $fileExtension;
$filePath = $uploadDir . $fileName;

if ($mimeType === 'image/png') {
    imagepng($newImage, $filePath, 9);
} else {
    imagejpeg($newImage, $filePath, 85);
}

imagedestroy($sourceImage);
imagedestroy($newImage);

// Remove old photo if exists
try {
    $stmt = $pdo->prepare("SELECT student_photo FROM students WHERE roll_no IN (?, ?, ?) LIMIT 1");
    $stmt->execute([$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student && !empty($student['student_photo'])) {
        $oldPhotoPath = $uploadDir . basename($student['student_photo']);
        if (file_exists($oldPhotoPath)) {
            @unlink($oldPhotoPath);
        }
    }
} catch (PDOException $e) {
    // Continue
}

// Update DB
try {
    $stmt = $pdo->prepare("UPDATE students SET student_photo = ? WHERE roll_no IN (?, ?, ?)");
    $stmt->execute([$fileName, $rollNumber, strtoupper($rollNumber), strtolower($rollNumber)]);

    $photoUrl = 'uploads/student_photos/' . $fileName;

    ApiResponse::success([
        'roll_no' => $rollNumber,
        'photo_name' => $fileName,
        'photo_url' => $photoUrl
    ], 'Profile photo uploaded and updated successfully');
} catch (PDOException $e) {
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
    ApiResponse::error('Database error occurred: ' . $e->getMessage(), 500);
}
