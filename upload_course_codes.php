<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['courseCodesFile']) || $_FILES['courseCodesFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['courseCodesFile'];
$allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes) && !preg_match('/\.(xlsx|xls)$/i', $file['name'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload an Excel file (.xlsx or .xls)']);
    exit;
}

require_once 'vendor/autoload.php';

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    if (count($rows) < 2) {
        echo json_encode(['success' => false, 'message' => 'Excel file is empty or has no data']);
        exit;
    }

    // Find header row
    $headerRow = null;
    $headerRowIndex = 0;
    foreach ($rows as $index => $row) {
        $rowStr = implode(' ', array_map('strtolower', $row));
        if (stripos($rowStr, 'course') !== false && stripos($rowStr, 'code') !== false) {
            $headerRow = $row;
            $headerRowIndex = $index;
            break;
        }
    }

    if (!$headerRow) {
        echo json_encode(['success' => false, 'message' => 'Could not find header row. Expected columns: Year, Semester, Course Code, Course Name, Credits, Regulation, Branch']);
        exit;
    }

    // Map column indices
    $colMap = [];
    foreach ($headerRow as $colIndex => $header) {
        $headerLower = strtolower(trim($header));
        if (strpos($headerLower, 'year') !== false) {
            $colMap['year'] = $colIndex;
        } elseif (strpos($headerLower, 'semester') !== false) {
            $colMap['semester'] = $colIndex;
        } elseif (strpos($headerLower, 'course code') !== false || strpos($headerLower, 'code') !== false) {
            $colMap['course_code'] = $colIndex;
        } elseif (strpos($headerLower, 'course name') !== false || strpos($headerLower, 'name') !== false) {
            $colMap['course_name'] = $colIndex;
        } elseif (strpos($headerLower, 'credit') !== false) {
            $colMap['credits'] = $colIndex;
        } elseif (strpos($headerLower, 'regulation') !== false) {
            $colMap['regulation'] = $colIndex;
        } elseif (strpos($headerLower, 'branch') !== false) {
            $colMap['branch'] = $colIndex;
        }
    }

    // Validate required columns
    $requiredCols = ['year', 'semester', 'course_code', 'course_name'];
    foreach ($requiredCols as $col) {
        if (!isset($colMap[$col])) {
            echo json_encode(['success' => false, 'message' => "Missing required column: $col"]);
            exit;
        }
    }

    // Insert/Update data using ON DUPLICATE KEY UPDATE
    $inserted = 0;
    $updated = 0;
    $errors = 0;
    $errorDetails = [];
    $stmt = $pdo->prepare("INSERT INTO course_codes (year, semester, course_code, course_name, credits, regulation, branch) VALUES (?, ?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE course_name = VALUES(course_name), credits = VALUES(credits), regulation = VALUES(regulation), branch = VALUES(branch)");

    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        $year = isset($colMap['year']) ? trim($row[$colMap['year']] ?? '') : '';
        $semester = isset($colMap['semester']) ? trim($row[$colMap['semester']] ?? '') : '';
        $courseCode = isset($colMap['course_code']) ? trim($row[$colMap['course_code']] ?? '') : '';
        $courseName = isset($colMap['course_name']) ? trim($row[$colMap['course_name']] ?? '') : '';
        $credits = isset($colMap['credits']) ? floatval($row[$colMap['credits']] ?? 0) : 0;
        $regulation = isset($colMap['regulation']) ? trim($row[$colMap['regulation']] ?? '') : null;
        $branch = isset($colMap['branch']) ? trim($row[$colMap['branch']] ?? '') : null;

        if (empty($year) || empty($semester) || empty($courseCode) || empty($courseName)) {
            $errors++;
            continue;
        }

        try {
            $stmt->execute([$year, $semester, $courseCode, $courseName, $credits, $regulation, $branch]);
            // Check if it was an insert or update by checking affected rows
            if ($stmt->rowCount() === 1) {
                $inserted++;
            } else {
                $updated++;
            }
        } catch (PDOException $e) {
            $errors++;
            if (count($errorDetails) < 5) {
                $errorDetails[] = "Row " . ($i + 1) . " (course: $courseCode): " . $e->getMessage();
            }
        }
    }

    $message = "Successfully processed records: $inserted inserted, $updated updated. " . ($errors > 0 ? "$errors rows skipped due to errors." : '');
    if (!empty($errorDetails)) {
        $message .= " Sample errors: " . implode(' | ', $errorDetails);
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing file: ' . $e->getMessage()]);
}
