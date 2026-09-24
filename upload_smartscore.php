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

if (!isset($_FILES['smartscoreFile']) || $_FILES['smartscoreFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['smartscoreFile'];
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
    // Make sure MySQL doesn't silently truncate/clamp out-of-range numeric values
    $pdo->exec("SET sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

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
        if (stripos($rowStr, 'rank') !== false && stripos($rowStr, 'roll') !== false) {
            $headerRow = $row;
            $headerRowIndex = $index;
            break;
        }
    }

    if (!$headerRow) {
        echo json_encode(['success' => false, 'message' => 'Could not find header row. Expected columns: Rank, Roll Number, Name, Mobile, Overall Score']);
        exit;
    }

    // Map column indices
    $colMap = [];
    foreach ($headerRow as $colIndex => $header) {
        $headerLower = strtolower(trim($header));
        if (strpos($headerLower, 'rank') !== false) {
            $colMap['rank'] = $colIndex;
        } elseif (strpos($headerLower, 'roll') !== false) {
            $colMap['roll_number'] = $colIndex;
        } elseif (strpos($headerLower, 'name') !== false) {
            $colMap['name'] = $colIndex;
        } elseif (strpos($headerLower, 'mobile') !== false) {
            $colMap['mobile'] = $colIndex;
        } elseif (strpos($headerLower, 'score') !== false) {
            $colMap['overall_score'] = $colIndex;
        }
    }

    // Validate required columns
    $requiredCols = ['roll_number', 'name', 'overall_score'];
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
    $stmt = $pdo->prepare("INSERT INTO smartscore (rank, global_rank, roll_number, name, mobile, overall_score) VALUES (?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE rank = VALUES(rank), global_rank = VALUES(global_rank), name = VALUES(name), mobile = VALUES(mobile), overall_score = VALUES(overall_score)");

    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        $rollNumber = isset($colMap['roll_number']) ? trim($row[$colMap['roll_number']] ?? '') : '';
        $name = isset($colMap['name']) ? trim($row[$colMap['name']] ?? '') : '';
        $overallScore = isset($colMap['overall_score']) ? floatval($row[$colMap['overall_score']] ?? 0) : 0;
        $mobile = isset($colMap['mobile']) ? trim($row[$colMap['mobile']] ?? '') : null;

        // --- Rank / Global Rank parsing ---
        // Handles combined cells like "1 (1803)" -> rank=1, global_rank=1803
        // Falls back to a plain number like "1" -> rank=1, global_rank=0
        $rank = 0;
        $globalRank = 0;
        if (isset($colMap['rank'])) {
            $rankRaw = trim($row[$colMap['rank']] ?? '');
            if (preg_match('/^\s*(\d+)\s*\(\s*(\d+)\s*\)/', $rankRaw, $m)) {
                $rank = (int)$m[1];
                $globalRank = (int)$m[2];
            } elseif ($rankRaw !== '') {
                $rank = intval($rankRaw);
                $globalRank = 0;
            }
        }

        if (empty($rollNumber) || empty($name)) {
            $errors++;
            continue;
        }

        try {
            $stmt->execute([$rank, $globalRank, $rollNumber, $name, $mobile, $overallScore]);
            // Check if it was an insert or update by checking affected rows
            if ($stmt->rowCount() === 1) {
                $inserted++;
            } else {
                $updated++;
            }
        } catch (PDOException $e) {
            $errors++;
            if (count($errorDetails) < 5) {
                $errorDetails[] = "Row " . ($i + 1) . " (roll: $rollNumber): " . $e->getMessage();
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