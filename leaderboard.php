<?php
session_start();
require_once 'config.php';

/* ══════════════════════════════════════════════
   CGPA LEADERBOARD
   Reads from student_results table (same DB as portal)
   Shows CGPA from the most recent semester only
   URL params:
     ?semester_info=   filter by semester
     ?department=      filter by dept
     ?search=          search by name / roll
     ?limit=           top N (default 100)
     ?download=        csv or excel
     ?ranking=         cgpa or weighted (default: cgpa)
══════════════════════════════════════════════ */

// Handle download requests
$downloadFormat = trim($_GET['download'] ?? '');
if ($downloadFormat === 'csv' || $downloadFormat === 'excel') {
    // Re-run the query with current filters
    $semesterInfo = trim($_GET['semester_info'] ?? '');
    $department   = trim($_GET['department']   ?? '');
    $search       = trim($_GET['search']       ?? '');
    $limit        = max(10, min(500, (int)($_GET['limit'] ?? 100)));
    $rankingType  = trim($_GET['ranking'] ?? 'cgpa');

    // Get custom weights for weighted ranking
    $smartWeight  = max(0, min(100, (int)($_GET['smart_weight'] ?? 60)));
    $cgpaWeight   = max(0, min(100, (int)($_GET['cgpa_weight'] ?? 40)));

    // Ensure weights sum to 100, if not normalize them
    $totalWeight = $smartWeight + $cgpaWeight;
    if ($totalWeight > 0 && $totalWeight !== 100) {
        $smartWeight = round(($smartWeight / $totalWeight) * 100);
        $cgpaWeight = 100 - $smartWeight;
    } elseif ($totalWeight === 0) {
        $smartWeight = 60;
        $cgpaWeight = 40;
    }

    $cteWhere  = ["cgpa IS NOT NULL", "cgpa != ''", "cgpa != 'N/A'"];
    $cteParams = [];

    if ($semesterInfo) {
        $cteWhere[] = "semester_info = :sem";
        $cteParams[':sem'] = $semesterInfo;
    }
    if ($department) {
        $cteWhere[] = "(
            department = :dept OR 
            (department IS NULL OR department = '') AND (
                exam_info REGEXP '[A-Z]{2,}-' AND SUBSTRING_INDEX(SUBSTRING_INDEX(exam_info, ' ', -1), '-', 1) = :dept OR
                file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' AND REGEXP_SUBSTR(file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)') = :dept OR
                file_name LIKE CONCAT('%', :dept, '%')
            )
        )";
        $cteParams[':dept'] = $department;
    }
    if ($search) {
        $cteWhere[] = "(roll_no LIKE :s OR student_name LIKE :s2)";
        $cteParams[':s'] = $cteParams[':s2'] = '%'.$search.'%';
    }

    $cteWhereClause = 'WHERE '.implode(' AND ', $cteWhere);

    if ($rankingType === 'weighted') {
        // Weighted ranking with custom weights
        $smartWeightFactor = $smartWeight / 100;
        $cgpaWeightFactor = $cgpaWeight / 100;
        
        $sql = "WITH latest_entry AS (
                SELECT 
                    sr.roll_no,
                    sr.student_name,
                    sr.father_name,
                    CASE 
                        WHEN sr.department IS NOT NULL AND sr.department != '' THEN sr.department
                        WHEN sr.exam_info REGEXP '[A-Z]{2,}-' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(sr.exam_info, ' ', -1), '-', 1)
                        WHEN sr.file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' THEN REGEXP_SUBSTR(sr.file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)')
                        WHEN sr.file_name LIKE '%AIML%' THEN 'AIML'
                        WHEN sr.file_name LIKE '%IT%' AND sr.file_name NOT LIKE '%AR-IT%' THEN 'IT'
                        WHEN sr.file_name LIKE '%AR-IT%' THEN 'IT'
                        ELSE 'Unknown'
                    END as department,
                    sr.semester_info,
                    sr.cgpa,
                    sr.sgpa,
                    sr.scr,
                    sr.tcr,
                    ss.overall_score as smart_score,
                    ROW_NUMBER() OVER (PARTITION BY sr.roll_no ORDER BY sr.created_at DESC) as rn
                FROM student_results sr
                LEFT JOIN smartscore ss ON sr.roll_no = ss.roll_number
                $cteWhereClause
            )
            SELECT roll_no, student_name, father_name, department,
                    semester_info, cgpa, sgpa, scr, tcr, smart_score,
                    (COALESCE(smart_score, 0) * $smartWeightFactor + CAST(cgpa AS DECIMAL(5,2)) * $cgpaWeightFactor) as weighted_score
            FROM latest_entry
            WHERE rn = 1
            ORDER BY weighted_score DESC, student_name ASC
            LIMIT 1000";
    } else {
        // CGPA-only ranking
        $sql = "WITH latest_entry AS (
                SELECT 
                    sr.roll_no,
                    sr.student_name,
                    sr.father_name,
                    CASE 
                        WHEN sr.department IS NOT NULL AND sr.department != '' THEN sr.department
                        WHEN sr.exam_info REGEXP '[A-Z]{2,}-' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(sr.exam_info, ' ', -1), '-', 1)
                        WHEN sr.file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' THEN REGEXP_SUBSTR(sr.file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)')
                        WHEN sr.file_name LIKE '%AIML%' THEN 'AIML'
                        WHEN sr.file_name LIKE '%IT%' AND sr.file_name NOT LIKE '%AR-IT%' THEN 'IT'
                        WHEN sr.file_name LIKE '%AR-IT%' THEN 'IT'
                        ELSE 'Unknown'
                    END as department,
                    sr.semester_info,
                    sr.cgpa,
                    sr.sgpa,
                    sr.scr,
                    sr.tcr,
                    ss.overall_score as smart_score,
                    ROW_NUMBER() OVER (PARTITION BY sr.roll_no ORDER BY sr.created_at DESC) as rn
                FROM student_results sr
                LEFT JOIN smartscore ss ON sr.roll_no = ss.roll_number
                $cteWhereClause
            )
            SELECT roll_no, student_name, father_name, department,
                    semester_info, cgpa, sgpa, scr, tcr, smart_score
            FROM latest_entry
            WHERE rn = 1
            ORDER BY CAST(cgpa AS DECIMAL(5,2)) DESC, student_name ASC
            LIMIT 1000";
    }

    $stmt = $pdo->prepare($sql);
    foreach ($cteParams as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = array_slice($rows, 0, $limit);

    if ($downloadFormat === 'csv') {
        header('Content-Type: text/csv');
        $filename = $rankingType === 'weighted' ? 'weighted_leaderboard_' . date('Y-m-d') . '.csv' : 'cgpa_leaderboard_' . date('Y-m-d') . '.csv';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        if ($rankingType === 'weighted') {
            fputcsv($output, ['Rank', 'Roll No', 'Name', 'CGPA', 'Smart Score', 'Weighted Score']);
        } else {
            fputcsv($output, ['Rank', 'Roll No', 'Name', 'CGPA', 'Smart Score']);
        }
        
        foreach ($rows as $index => $row) {
            if ($rankingType === 'weighted') {
                fputcsv($output, [
                    $index + 1,
                    $row['roll_no'] ?? '',
                    $row['student_name'] ?? '',
                    $row['cgpa'] ?? '',
                    $row['smart_score'] ?? '',
                    number_format($row['weighted_score'] ?? 0, 2)
                ]);
            } else {
                fputcsv($output, [
                    $index + 1,
                    $row['roll_no'] ?? '',
                    $row['student_name'] ?? '',
                    $row['cgpa'] ?? '',
                    $row['smart_score'] ?? ''
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    if ($downloadFormat === 'excel') {
        require_once 'vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'Rank');
        $sheet->setCellValue('B1', 'Roll No');
        $sheet->setCellValue('C1', 'Name');
        $sheet->setCellValue('D1', 'CGPA');
        $sheet->setCellValue('E1', 'Smart Score');
        
        if ($rankingType === 'weighted') {
            $sheet->setCellValue('F1', 'Weighted Score');
            $headerRange = 'A1:F1';
        } else {
            $headerRange = 'A1:E1';
        }
        
        // Style header row
        $headerStyle = $sheet->getStyle($headerRange);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setARGB('FF4472C4');
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');
        
        // Add data
        $row = 2;
        foreach ($rows as $index => $data) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $data['roll_no'] ?? '');
            $sheet->setCellValue('C' . $row, $data['student_name'] ?? '');
            $sheet->setCellValue('D' . $row, $data['cgpa'] ?? '');
            $sheet->setCellValue('E' . $row, $data['smart_score'] ?? '');
            
            if ($rankingType === 'weighted') {
                $sheet->setCellValue('F' . $row, number_format($data['weighted_score'] ?? 0, 2));
            }
            $row++;
        }
        
        // Auto-size columns
        $maxCol = $rankingType === 'weighted' ? 'F' : 'E';
        foreach (range('A', $maxCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $filename = $rankingType === 'weighted' ? 'weighted_leaderboard_' . date('Y-m-d') . '.xlsx' : 'cgpa_leaderboard_' . date('Y-m-d') . '.xlsx';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}

$semesterInfo = trim($_GET['semester_info'] ?? '');
$department   = trim($_GET['department']   ?? '');
$search       = trim($_GET['search']       ?? '');
$limit        = max(10, min(500, (int)($_GET['limit'] ?? 100)));
$rankingType  = trim($_GET['ranking'] ?? 'cgpa');

// Get custom weights for weighted ranking
$smartWeight  = max(0, min(100, (int)($_GET['smart_weight'] ?? 60)));
$cgpaWeight   = max(0, min(100, (int)($_GET['cgpa_weight'] ?? 40)));

// Ensure weights sum to 100, if not normalize them
$totalWeight = $smartWeight + $cgpaWeight;
if ($totalWeight > 0 && $totalWeight !== 100) {
    $smartWeight = round(($smartWeight / $totalWeight) * 100);
    $cgpaWeight = 100 - $smartWeight;
} elseif ($totalWeight === 0) {
    $smartWeight = 60;
    $cgpaWeight = 40;
}

/* ── Build WHERE clause for CTE ── */
$cteWhere  = ["cgpa IS NOT NULL", "cgpa != ''", "cgpa != 'N/A'"];
$cteParams = [];

if ($semesterInfo) {
    $cteWhere[] = "semester_info = :sem";
    $cteParams[':sem'] = $semesterInfo;
}
if ($department) {
    $cteWhere[] = "(
        department = :dept OR 
        (department IS NULL OR department = '') AND (
            exam_info REGEXP '[A-Z]{2,}-' AND SUBSTRING_INDEX(SUBSTRING_INDEX(exam_info, ' ', -1), '-', 1) = :dept OR
            file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' AND REGEXP_SUBSTR(file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)') = :dept OR
            file_name LIKE CONCAT('%', :dept, '%')
        )
    )";
    $cteParams[':dept'] = $department;
}
if ($search) {
    $cteWhere[] = "(roll_no LIKE :s OR student_name LIKE :s2)";
    $cteParams[':s'] = $cteParams[':s2'] = '%'.$search.'%';
}

$cteWhereClause = 'WHERE '.implode(' AND ', $cteWhere);

/* ── Fetch leaderboard with most recent CGPA entry (by created_at) ── */
if ($rankingType === 'weighted') {
    // Weighted ranking with custom weights
    $smartWeightFactor = $smartWeight / 100;
    $cgpaWeightFactor = $cgpaWeight / 100;
    
    $sql = "WITH latest_entry AS (
            SELECT 
                sr.roll_no,
                sr.student_name,
                sr.father_name,
                CASE 
                    WHEN sr.department IS NOT NULL AND sr.department != '' THEN sr.department
                    WHEN sr.exam_info REGEXP '[A-Z]{2,}-' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(sr.exam_info, ' ', -1), '-', 1)
                    WHEN sr.file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' THEN REGEXP_SUBSTR(sr.file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)')
                    WHEN sr.file_name LIKE '%AIML%' THEN 'AIML'
                    WHEN sr.file_name LIKE '%IT%' AND sr.file_name NOT LIKE '%AR-IT%' THEN 'IT'
                    WHEN sr.file_name LIKE '%AR-IT%' THEN 'IT'
                    ELSE 'Unknown'
                END as department,
                sr.semester_info,
                sr.cgpa,
                sr.sgpa,
                sr.scr,
                sr.tcr,
                ss.overall_score as smart_score,
                ROW_NUMBER() OVER (PARTITION BY sr.roll_no ORDER BY sr.created_at DESC) as rn
            FROM student_results sr
            LEFT JOIN smartscore ss ON sr.roll_no = ss.roll_number
            $cteWhereClause
        )
        SELECT roll_no, student_name, father_name, department,
                semester_info, cgpa, sgpa, scr, tcr, smart_score,
                (COALESCE(smart_score, 0) * $smartWeightFactor + CAST(cgpa AS DECIMAL(5,2)) * $cgpaWeightFactor) as weighted_score
        FROM latest_entry
        WHERE rn = 1
        ORDER BY weighted_score DESC, student_name ASC
        LIMIT 1000";
} else {
    // CGPA-only ranking
    $sql = "WITH latest_entry AS (
            SELECT 
                sr.roll_no,
                sr.student_name,
                sr.father_name,
                CASE 
                    WHEN sr.department IS NOT NULL AND sr.department != '' THEN sr.department
                    WHEN sr.exam_info REGEXP '[A-Z]{2,}-' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(sr.exam_info, ' ', -1), '-', 1)
                    WHEN sr.file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' THEN REGEXP_SUBSTR(sr.file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)')
                    WHEN sr.file_name LIKE '%AIML%' THEN 'AIML'
                    WHEN sr.file_name LIKE '%IT%' AND sr.file_name NOT LIKE '%AR-IT%' THEN 'IT'
                    WHEN sr.file_name LIKE '%AR-IT%' THEN 'IT'
                    ELSE 'Unknown'
                END as department,
                sr.semester_info,
                sr.cgpa,
                sr.sgpa,
                sr.scr,
                sr.tcr,
                ss.overall_score as smart_score,
                ROW_NUMBER() OVER (PARTITION BY sr.roll_no ORDER BY sr.created_at DESC) as rn
            FROM student_results sr
            LEFT JOIN smartscore ss ON sr.roll_no = ss.roll_number
            $cteWhereClause
        )
        SELECT roll_no, student_name, father_name, department,
                semester_info, cgpa, sgpa, scr, tcr, smart_score
        FROM latest_entry
        WHERE rn = 1
        ORDER BY CAST(cgpa AS DECIMAL(5,2)) DESC, student_name ASC
        LIMIT 1000";
}

$stmt = $pdo->prepare($sql);
foreach ($cteParams as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Apply limit in PHP
$rows = array_slice($rows, 0, $limit);

/* ── Dropdown options ── */
$semesters = $pdo->query(
    "SELECT DISTINCT semester_info FROM student_results
     WHERE semester_info IS NOT NULL AND semester_info != ''
     ORDER BY semester_info"
)->fetchAll(PDO::FETCH_COLUMN);

// Extract departments from exam_info and file_name since department column is empty
$departments = $pdo->query(
    "SELECT DISTINCT 
        CASE 
            WHEN department IS NOT NULL AND department != '' THEN department
            WHEN exam_info REGEXP '[A-Z]{2,}-' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(exam_info, ' ', -1), '-', 1)
            WHEN file_name REGEXP '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)' THEN REGEXP_SUBSTR(file_name, '^(AIML|IT|CSE|ECE|EEE|MECH|CIVIL)')
            WHEN file_name LIKE '%AIML%' THEN 'AIML'
            WHEN file_name LIKE '%IT%' AND file_name NOT LIKE '%AR-IT%' THEN 'IT'
            WHEN file_name LIKE '%AR-IT%' THEN 'IT'
            ELSE 'Unknown'
        END as dept
     FROM student_results
     WHERE cgpa IS NOT NULL AND cgpa != '' AND cgpa != 'N/A'
     ORDER BY dept"
)->fetchAll(PDO::FETCH_COLUMN);

/* ── Stats ── */
$total      = count($rows);
$avgCgpa    = $total ? round(array_sum(array_column($rows,'cgpa')) / $total, 2) : 0;
$above8     = count(array_filter($rows, fn($r) => (float)$r['cgpa'] >= 8.0));
$above9     = count(array_filter($rows, fn($r) => (float)$r['cgpa'] >= 9.0));
$top3       = array_slice($rows, 0, 3);
$rest       = array_slice($rows, 3);

/* ── Podium data ── */
$p = [];
foreach ([1=>0,2=>1,3=>2] as $rank => $idx) {
    $p[$rank] = isset($top3[$idx]) ? [
        'name' => $top3[$idx]['student_name'] ?? '-',
        'roll' => $top3[$idx]['roll_no']      ?? '-',
        'dept' => $top3[$idx]['department']   ?? '-',
        'cgpa' => round((float)($top3[$idx]['cgpa'] ?? 0), 2),
        'sgpa' => $top3[$idx]['sgpa'] !== null && $top3[$idx]['sgpa'] !== ''
                    ? round((float)$top3[$idx]['sgpa'], 2) : null,
    ] : null;
}

$showPodium = count($top3) >= 3;
$minimizePodium = $search && count($top3) >= 3;

function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function cgpaCls(float $v): string {
    if ($v >= 9)   return 'cgpa-sg';
    if ($v >= 7.5) return 'cgpa-sw';
    if ($v >= 6)   return 'cgpa-sm-p';
    return 'cgpa-sd-p';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CGPA Leaderboard - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            font-family: 'Sora', sans-serif;
            min-height: 100vh;
        }
        .leaderboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .leaderboard-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .leaderboard-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        .stats-bar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
        }
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 15px;
            margin-bottom: 40px;
            padding: 20px;
        }
        .podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            max-width: 200px;
        }
        .podium-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            width: 100%;
            border: 2px solid transparent;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .podium-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(99,102,241,0.3);
        }
        .podium-item:nth-child(1) .podium-card {
            border-color: #ffd700;
            background: linear-gradient(135deg, rgba(255,215,0,0.1), var(--bg-card));
        }
        .podium-item:nth-child(2) .podium-card {
            border-color: #c0c0c0;
            background: linear-gradient(135deg, rgba(192,192,192,0.1), var(--bg-card));
        }
        .podium-item:nth-child(3) .podium-card {
            border-color: #cd7f32;
            background: linear-gradient(135deg, rgba(205,127,50,0.1), var(--bg-card));
        }
        .podium-rank {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            line-height: 1;
        }
        .podium-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border: 4px solid var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 12px;
            box-shadow: 0 4px 15px rgba(99,102,241,0.4);
        }
        .podium-item:nth-child(1) .podium-avatar {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #000;
        }
        .podium-item:nth-child(2) .podium-avatar {
            background: linear-gradient(135deg, #c0c0c0, #e5e5e5);
            color: #000;
        }
        .podium-item:nth-child(3) .podium-avatar {
            background: linear-gradient(135deg, #cd7f32, #e6a15c);
            color: #fff;
        }
        .podium-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
        }
        .podium-roll {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-family: 'JetBrains Mono', monospace;
        }
        .podium-cgpa {
            font-size: 1.8rem;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            margin-bottom: 5px;
        }
        .podium-item:nth-child(1) .podium-rank { color: #ffd700; }
        .podium-item:nth-child(2) .podium-rank { color: #c0c0c0; }
        .podium-item:nth-child(3) .podium-rank { color: #cd7f32; }
        .podium-item:nth-child(1) .podium-cgpa { color: #ffd700; }
        .podium-item:nth-child(2) .podium-cgpa { color: #c0c0c0; }
        .podium-item:nth-child(3) .podium-cgpa { color: #cd7f32; }
        .filters {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .leaderboard-table {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
        }
        .leaderboard-table thead {
            background: rgba(99,102,241,0.2);
        }
        .leaderboard-table th {
            padding: 15px;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .leaderboard-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #000; }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e5e5e5); color: #000; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #e6a15c); color: #fff; }
        .rank-other { background: var(--bg-dark); color: var(--text-secondary); }
        .cgpa-display {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .cgpa-sg { color: var(--success); }
        .cgpa-sw { color: var(--accent); }
        .cgpa-sm-p { color: var(--warning); }
        .cgpa-sd-p { color: var(--danger); }
        .table-responsive { 
            max-height: 600px; 
            overflow-x: auto; 
            overflow-y: auto; 
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .leaderboard-container {
                padding: 10px;
            }
            .leaderboard-header h1 {
                font-size: 1.5rem;
            }
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
                padding: 15px;
                gap: 15px;
            }
            .stat-value {
                font-size: 1.5rem;
            }
            .podium {
                gap: 10px;
                padding: 10px;
            }
            .podium-item {
                max-width: 120px;
            }
            .podium-card {
                padding: 12px;
            }
            .podium-rank {
                font-size: 1.5rem;
            }
            .podium-avatar {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
            .podium-name {
                font-size: 0.75rem;
            }
            .podium-roll {
                font-size: 0.65rem;
            }
            .podium-cgpa {
                font-size: 1.2rem;
            }
            .filters {
                padding: 15px;
            }
            .leaderboard-table th,
            .leaderboard-table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
            .rank-badge {
                width: 32px;
                height: 32px;
                font-size: 0.85rem;
            }
            .cgpa-display {
                font-size: 1rem;
            }
            /* Hide less important columns on mobile */
            .hide-mobile {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }
            .podium {
                flex-direction: column;
                align-items: center;
            }
            .podium-item {
                width: 100%;
                max-width: 200px;
            }
            .podium-platform {
                height: 30px !important;
            }
        }
    </style>
</head>
<body>
    <div class="leaderboard-container">
        <div class="leaderboard-header">
            <h1>🏆 CGPA Leaderboard</h1>
            <p class="text-muted">Top performers based on most recent semester CGPA</p>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value"><?php echo $total; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $avgCgpa; ?></div>
                <div class="stat-label">Average CGPA</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $above8; ?></div>
                <div class="stat-label">Above 8.0</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $above9; ?></div>
                <div class="stat-label">Above 9.0</div>
            </div>
        </div>

        <?php if ($showPodium): ?>
        <div class="podium">
            <?php for ($i = 0; $i < 3; $i++): ?>
            <?php if (isset($top3[$i])): ?>
            <div class="podium-item">
                <div class="podium-card">
                    <div class="podium-rank"><?php echo $i + 1; ?></div>
                    <div class="podium-avatar">
                        <?php echo strtoupper(substr($top3[$i]['student_name'] ?? '?', 0, 1)); ?>
                    </div>
                    <div class="podium-name"><?php echo esc($top3[$i]['student_name'] ?? 'N/A'); ?></div>
                    <div class="podium-roll"><?php echo esc($top3[$i]['roll_no'] ?? 'N/A'); ?></div>
                    <div class="podium-cgpa"><?php echo number_format((float)($top3[$i]['cgpa'] ?? 0), 2); ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <div class="filters">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-white"><i class="fas fa-filter me-2"></i>Filters</h5>
                <div class="download-buttons">
                    <button onclick="downloadLeaderboard('csv')" class="btn btn-success btn-sm">
                        <i class="fas fa-file-csv me-1"></i>Download CSV
                    </button>
                    <button onclick="downloadLeaderboard('excel')" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Download Excel
                    </button>
                </div>
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label text-muted">Ranking Type</label>
                    <select name="ranking" class="form-select bg-dark text-white border-secondary" id="rankingTypeSelect">
                        <option value="cgpa" <?php echo $rankingType === 'cgpa' ? 'selected' : ''; ?>>CGPA Only</option>
                        <option value="weighted" <?php echo $rankingType === 'weighted' ? 'selected' : ''; ?>>Weighted</option>
                    </select>
                </div>
                <div class="col-md-4" id="weightFields" style="<?php echo $rankingType !== 'weighted' ? 'display:none;' : ''; ?>">
                    <label class="form-label text-muted">Weight Distribution</label>
                    <div class="input-group">
                        <input type="number" name="smart_weight" class="form-control bg-dark text-white border-secondary" 
                               value="<?php echo esc($_GET['smart_weight'] ?? 60); ?>" min="0" max="100" step="5" placeholder="Smart %">
                        <span class="input-group-text bg-dark text-white border-secondary">Smart</span>
                        <input type="number" name="cgpa_weight" class="form-control bg-dark text-white border-secondary" 
                               value="<?php echo esc($_GET['cgpa_weight'] ?? 40); ?>" min="0" max="100" step="5" placeholder="CGPA %">
                        <span class="input-group-text bg-dark text-white border-secondary">CGPA</span>
                    </div>
                    <small class="text-muted" style="font-size: 11px;">Weights must sum to 100%</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Semester</label>
                    <select name="semester_info" class="form-select bg-dark text-white border-secondary">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo esc($sem); ?>" <?php echo $semesterInfo === $sem ? 'selected' : ''; ?>><?php echo esc($sem); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Department</label>
                    <select name="department" class="form-select bg-dark text-white border-secondary">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo esc($dept); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>><?php echo esc($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Search</label>
                    <input type="text" name="search" class="form-control bg-dark text-white border-secondary" placeholder="Name or Roll No" value="<?php echo esc($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Limit</label>
                    <select name="limit" class="form-select bg-dark text-white border-secondary">
                        <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                        <option value="250" <?php echo $limit === 250 ? 'selected' : ''; ?>>250</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <?php if ($total === 0): ?>
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No students found matching the current filters.
        </div>
        <?php else: ?>
        <div class="leaderboard-table table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th class="hide-mobile">Department</th>
                        <th class="hide-mobile">Semester</th>
                        <th>CGPA</th>
                        <th>Smart Score</th>
                        <?php if ($rankingType === 'weighted'): ?>
                        <th>Weighted Score</th>
                        <?php endif; ?>
                        <th class="hide-mobile">SGPA</th>
                        <th class="hide-mobile">Credits</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td>
                            <div class="rank-badge <?php 
                                echo ($index === 0) ? 'rank-1' : (($index === 1) ? 'rank-2' : (($index === 2) ? 'rank-3' : 'rank-other')); 
                            ?>">
                                <?php echo $index + 1; ?>
                            </div>
                        </td>
                        <td><span class="font-mono"><?php echo esc($row['roll_no'] ?? 'N/A'); ?></span></td>
                        <td><?php echo esc($row['student_name'] ?? 'N/A'); ?></td>
                        <td class="hide-mobile"><?php echo esc($row['department'] ?? 'N/A'); ?></td>
                        <td class="hide-mobile"><small><?php echo esc($row['semester_info'] ?? 'N/A'); ?></small></td>
                        <td>
                            <span class="cgpa-display <?php echo cgpaCls((float)($row['cgpa'] ?? 0)); ?>">
                                <?php echo number_format((float)($row['cgpa'] ?? 0), 2); ?>
                            </span>
                        </td>
                        <td>
                            <span class="font-mono">
                                <?php echo ($row['smart_score'] !== null && $row['smart_score'] !== '') ? number_format((float)$row['smart_score'], 2) : '-'; ?>
                            </span>
                        </td>
                        <?php if ($rankingType === 'weighted'): ?>
                        <td>
                            <span class="font-mono fw-bold" style="color: var(--accent);">
                                <?php echo number_format((float)($row['weighted_score'] ?? 0), 2); ?>
                            </span>
                        </td>
                        <td class="hide-mobile">
                            <small class="text-muted"><?php echo $smartWeight; ?>% Smart / <?php echo $cgpaWeight; ?>% CGPA</small>
                        </td>
                        <?php endif; ?>
                        <td class="hide-mobile">
                            <span class="font-mono">
                                <?php echo ($row['sgpa'] !== null && $row['sgpa'] !== '') ? number_format((float)$row['sgpa'], 2) : '-'; ?>
                            </span>
                        </td>
                        <td class="hide-mobile">
                            <span class="font-mono">
                                <?php echo esc($row['scr'] ?? '-'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Search
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function downloadLeaderboard(format) {
        // Get current filter values
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('download', format);
        
        // Open download URL
        window.location.href = 'leaderboard.php?' + urlParams.toString();
    }

    // Toggle weight fields based on ranking type
    document.addEventListener('DOMContentLoaded', function() {
        const rankingSelect = document.getElementById('rankingTypeSelect');
        const weightFields = document.getElementById('weightFields');

        if (rankingSelect && weightFields) {
            rankingSelect.addEventListener('change', function() {
                if (this.value === 'weighted') {
                    weightFields.style.display = 'block';
                } else {
                    weightFields.style.display = 'none';
                }
            });
        }
    });
    </script>
</body>
</html>