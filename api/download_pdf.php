<?php
/**
 * download_pdf.php
 * 
 * Generates an executive, comprehensive, full-report PDF for student profile
 * Uses mPDF library
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

$rollNumber = strtoupper(trim($_GET['roll'] ?? $_GET['roll_no'] ?? $_POST['roll_no'] ?? ''));

if (empty($rollNumber)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Roll number is required']);
    exit;
}

try {
    $rp = [$rollNumber, strtoupper($rollNumber), strtolower($rollNumber)];

    // 1. Fetch Student Details
    $studentColumns = ['roll_no', 'student_name', 'father_name', 'date_of_birth', 'student_photo'];
    try {
        $availableStudentColumns = array_map(
            fn($r) => (string)($r['Field'] ?? ''),
            $pdo->query('DESCRIBE students')->fetchAll(PDO::FETCH_ASSOC)
        );
        foreach (['student_photo', 'photo_path', 'profile_photo', 'photo'] as $photoColumn) {
            if (in_array($photoColumn, $availableStudentColumns, true)) {
                $studentColumns[] = $photoColumn;
                break;
            }
        }
    } catch (Throwable $e) {}
    $studentSelect = implode(', ', array_map(fn($col) => "`{$col}`", array_unique($studentColumns)));
    $stmt = $pdo->prepare("SELECT {$studentSelect} FROM students WHERE roll_no IN (?,?,?) LIMIT 1");
    $stmt->execute($rp);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    $photoSrc = '';
    if ($student && !empty($student['student_photo'])) {
        $possiblePaths = [
            __DIR__ . '/../uploads/student_photos/' . $student['student_photo'],
            __DIR__ . '/../' . $student['student_photo'],
            $student['student_photo']
        ];
        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                $photoSrc = $p;
                break;
            }
        }
    }

    // 2. Fetch Semester Results
    $studentResults = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM student_results WHERE roll_no IN (?,?,?) ORDER BY created_at ASC, id ASC");
        $stmt->execute($rp);
        $studentResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $studentResults = []; }

    // 3. Fetch Subject Results with Course Names
    $subjectResults = [];
    try {
        $stmt = $pdo->prepare("
            SELECT sr.*, 
                   COALESCE(sc.subject_name, s.subject_name, sr.subject_name) as subject_name
            FROM subject_results sr
            LEFT JOIN student_courses sc ON sr.subject_code = sc.subject_code AND sr.roll_no = sc.roll_no
            LEFT JOIN subjects s ON sr.subject_code = s.subject_code
            WHERE sr.roll_no IN (?,?,?)
            ORDER BY sr.semester_info ASC, sr.subject_code ASC
        ");
        $stmt->execute($rp);
        $subjectResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $subjectResults = []; }

    // If student not found in students table, fallback to results
    if (!$student && !empty($studentResults)) {
        $student = [
            'roll_no' => $rollNumber,
            'student_name' => $studentResults[0]['student_name'] ?? 'Unknown Student',
            'father_name' => $studentResults[0]['father_name'] ?? '',
            'date_of_birth' => ''
        ];
    }

    if (!$student && empty($studentResults) && empty($subjectResults)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No student data found for this roll number']);
        exit;
    }

    // 4. Fetch Attendance Summary & Subject Attendance
    $attendanceSummary = [];
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.file_name as upload_file_name, u.semester, u.academic_year, u.section, u.month_name
            FROM attendance_summary s
            LEFT JOIN attendance_uploads u ON s.session_name = u.session_name
            WHERE s.roll_no IN (?,?,?)
            ORDER BY u.uploaded_at ASC, s.session_name ASC
        ");
        $stmt->execute($rp);
        $attendanceSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $attendanceSummary = []; }

    $attendanceSubjects = [];
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.file_name as upload_file_name, u.semester, u.academic_year, u.section, u.month_name
            FROM attendance_subjects s
            LEFT JOIN attendance_uploads u ON s.session_name = u.session_name
            WHERE s.roll_no IN (?,?,?)
            ORDER BY u.uploaded_at ASC, s.session_name ASC, s.subject_code ASC
        ");
        $stmt->execute($rp);
        $attendanceSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $attendanceSubjects = []; }

    // 5. Fetch Mid Marks
    $midmarks = [];
    try {
        $stmt = $pdo->prepare("
            SELECT mm.*, 
                   COALESCE(sc.subject_name, s.subject_name, mm.subject_name) as subject_name
            FROM midmarks_subject_marks mm
            LEFT JOIN student_courses sc ON mm.subject_code = sc.subject_code AND mm.roll_no = sc.roll_no
            LEFT JOIN subjects s ON mm.subject_code = s.subject_code
            WHERE mm.roll_no IN (?,?,?)
            ORDER BY mm.subject_code ASC
        ");
        $stmt->execute($rp);
        $midmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $midmarks = []; }

    // 6. Fetch Job Offers
    $jobOffers = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM job_offers WHERE roll_no IN (?,?,?) ORDER BY id DESC");
        $stmt->execute($rp);
        $jobOffers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $jobOffers = []; }

    // 7. Fetch NPTEL Certificates
    $nptelCertificates = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM nptel_certificates WHERE roll_no IN (?,?,?) ORDER BY id DESC");
        $stmt->execute($rp);
        $nptelCertificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $nptelCertificates = []; }

    // Helper to parse credits as number or fraction (e.g. 7/21 or 52/81)
    $parseCreditsFraction = function ($val): array {
        if ($val === null) {
            return ['secured' => null, 'total' => null, 'isFraction' => false];
        }
        $s = trim((string)$val);
        if ($s === '' || $s === '-') {
            return ['secured' => null, 'total' => null, 'isFraction' => false];
        }
        if (strpos($s, '/') !== false) {
            $parts = explode('/', $s);
            $num = isset($parts[0]) && is_numeric(trim($parts[0])) ? (float)trim($parts[0]) : null;
            $den = isset($parts[1]) && is_numeric(trim($parts[1])) ? (float)trim($parts[1]) : null;
            return ['secured' => $num, 'total' => $den, 'isFraction' => true];
        }
        $n = is_numeric($s) ? (float)$s : null;
        return ['secured' => $n, 'total' => $n, 'isFraction' => false];
    };

    // Calculate Overall Metrics
    $latestCgpa = '-';
    $latestSgpa = '-';
    $totalCredits = '-';
    if (!empty($studentResults)) {
        $latestRes = end($studentResults);
        $latestCgpa = $latestRes['cgpa'] ?? '-';
        $latestSgpa = $latestRes['sgpa'] ?? '-';
        $totalCredits = $latestRes['tcr'] ?? $latestRes['scr'] ?? '-';
    }

    $totalAttPresent = 0;
    $totalAttClasses = 0;
    foreach ($attendanceSummary as $att) {
        $totalAttPresent += (int)($att['present'] ?? $att['total_present'] ?? 0);
        $totalAttClasses += (int)($att['total'] ?? $att['total_classes'] ?? 0);
    }
    $overallAttPct = $totalAttClasses > 0 ? round(($totalAttPresent / $totalAttClasses) * 100, 2) : '-';

    // Calculate Backlogs from Subjects and Semester Results
    $backlogCount = 0;
    foreach ($subjectResults as $sr) {
        $grade = strtoupper(trim((string)($sr['grade'] ?? '')));
        if ($grade === 'F' || $grade === 'FAIL' || $grade === 'AB' || $grade === 'ABSENT' || $grade === 'W') {
            $backlogCount++;
        }
    }

    $hasFailedSemesters = false;
    $pendingCredits = 0;
    foreach ($studentResults as $r) {
        $scrP = $parseCreditsFraction($r['scr'] ?? '');
        $sgpaP = is_numeric($r['sgpa'] ?? '') ? (float)$r['sgpa'] : 0;
        
        if ($scrP['isFraction'] && $scrP['secured'] !== null && $scrP['total'] !== null && $scrP['secured'] < $scrP['total']) {
            $hasFailedSemesters = true;
            $pendingCredits += (int)($scrP['total'] - $scrP['secured']);
        } elseif ($sgpaP <= 0 && $scrP['isFraction'] && $scrP['secured'] !== null && $scrP['total'] !== null && $scrP['secured'] < $scrP['total']) {
            $hasFailedSemesters = true;
        }
    }

    if ($backlogCount > 0) {
        $backlogDisplay = '<span class="text-danger font-bold">' . $backlogCount . ' Pending</span>';
    } elseif ($hasFailedSemesters) {
        $backlogDisplay = '<span class="text-danger font-bold">Pending (' . ($pendingCredits > 0 ? $pendingCredits . ' cr' : 'Backlogs') . ')</span>';
    } else {
        $backlogDisplay = '<span class="text-success font-bold">0 (All Clear)</span>';
    }

    // Build the Full HTML Report
    $html = '
    <div class="report-header">
        <table width="100%" cellpadding="0" cellspacing="0" style="border:none;">
            <tr>
                <td width="75%" style="border:none; vertical-align:middle;">
                    <div class="college-title">STUDENT PORTAL</div>
                    <div class="college-subtitle">Comprehensive Academic Dossier & Performance Record</div>
                    <div class="doc-title">OFFICIAL STUDENT ACADEMIC REPORT</div>
                </td>
                <td width="25%" align="right" style="border:none; vertical-align:middle;">
                    <div class="report-meta">
                        <strong>REPORT DATE:</strong> ' . date('d M Y') . '<br>
                        <strong>TIME:</strong> ' . date('h:i A') . '<br>
                        <span class="status-tag">STUDENT PORTAL COPY</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Student Bio & KPI Header -->
    <div class="student-profile-box">
        <table width="100%" cellpadding="4" cellspacing="0" style="border:none;">
            <tr>
                <td width="75%" style="border:none; vertical-align:top;">
                    <table width="100%" class="info-grid-table">
                        <tr>
                            <td class="info-lbl">Roll Number:</td>
                            <td class="info-val font-bold text-primary">' . htmlspecialchars($student['roll_no'] ?? $rollNumber) . '</td>
                            <td class="info-lbl">Overall CGPA:</td>
                            <td class="info-val font-bold text-success">' . htmlspecialchars((string)$latestCgpa) . '</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">Student Name:</td>
                            <td class="info-val font-bold">' . htmlspecialchars($student['student_name'] ?? 'N/A') . '</td>
                            <td class="info-lbl">Overall Attendance:</td>
                            <td class="info-val font-bold">' . htmlspecialchars((string)$overallAttPct) . ($overallAttPct !== '-' ? '%' : '') . '</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">Father\'s Name:</td>
                            <td class="info-val">' . htmlspecialchars($student['father_name'] ?? 'N/A') . '</td>
                            <td class="info-lbl">Credits Earned:</td>
                            <td class="info-val">' . htmlspecialchars((string)$totalCredits) . '</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">Date of Birth:</td>
                            <td class="info-val">' . (!empty($student['date_of_birth']) ? date('d-M-Y', strtotime($student['date_of_birth'])) : 'N/A') . '</td>
                            <td class="info-lbl">Active Backlogs:</td>
                            <td class="info-val">' . $backlogDisplay . '</td>
                        </tr>
                    </table>
                </td>
                <td width="25%" align="center" style="border:none; vertical-align:top;">';
    
    if ($photoSrc && file_exists($photoSrc)) {
        $html .= '<img src="' . htmlspecialchars($photoSrc) . '" class="student-photo" alt="Photo" />';
    } else {
        $initial = strtoupper(substr($student['student_name'] ?? $rollNumber, 0, 1));
        $html .= '<div class="student-photo-placeholder">' . htmlspecialchars($initial) . '</div>';
    }

    $html .= '
                </td>
            </tr>
        </table>
    </div>';

    // 1. SEMESTER RESULTS (SGPA / CGPA PROGRESSION)
    if (!empty($studentResults)) {
        $html .= '<div class="section-heading">1. Semester Progression & SGPA / CGPA History</div>';
        $html .= '<table class="data-table">
            <thead>
                <tr>
                    <th width="28%">Semester / Examination</th>
                    <th width="14%" align="center">SGPA</th>
                    <th width="14%" align="center">CGPA</th>
                    <th width="16%" align="center">Gained Credits (SCR)</th>
                    <th width="16%" align="center">Total Credits (TCR)</th>
                    <th width="12%" align="center">Status</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($studentResults as $r) {
            $semLabel = $r['semester_info'] ?? $r['semester'] ?? $r['sem'] ?? 'Semester';
            $sgpa = $r['sgpa'] ?? '-';
            $cgpa = $r['cgpa'] ?? '-';
            $scr = $r['scr'] ?? '-';
            $tcr = $r['tcr'] ?? '-';
            
            $semSgpa = is_numeric($sgpa) ? (float)$sgpa : 0;
            $semCgpa = is_numeric($cgpa) ? (float)$cgpa : 0;

            $scrParsed = $parseCreditsFraction($scr);
            $tcrParsed = $parseCreditsFraction($tcr);

            // Determine if semester was Passed or Failed
            $isPassed = false;
            if ($scrParsed['isFraction'] && $scrParsed['total'] !== null && $scrParsed['total'] > 0) {
                // If SCR is fraction (e.g. 7/21), it is passed only if secured >= total (e.g. 21/21)
                $isPassed = ($scrParsed['secured'] !== null && $scrParsed['secured'] >= $scrParsed['total']);
            } elseif ($semSgpa > 0 && $semCgpa > 0) {
                if ($scrParsed['isFraction'] && $scrParsed['secured'] !== null && $scrParsed['total'] !== null) {
                    $isPassed = ($scrParsed['secured'] >= $scrParsed['total']);
                } else {
                    $isPassed = true;
                }
            } elseif ($semSgpa > 0) {
                $isPassed = true;
            } else {
                // SGPA is 0 or not available -> FAILED
                $isPassed = false;
            }

            $status = $isPassed ? 'PASSED' : 'FAILED';
            $statusCls = $isPassed ? 'text-success font-bold' : 'text-danger font-bold';

            $html .= '<tr>
                <td>' . htmlspecialchars($semLabel) . '</td>
                <td align="center"><strong>' . htmlspecialchars((string)$sgpa) . '</strong></td>
                <td align="center"><strong>' . htmlspecialchars((string)$cgpa) . '</strong></td>
                <td align="center">' . htmlspecialchars((string)$scr) . '</td>
                <td align="center">' . htmlspecialchars((string)$tcr) . '</td>
                <td align="center" class="' . $statusCls . '">' . htmlspecialchars($status) . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    }

    // 2. DETAILED SUBJECT-WISE GRADES
    if (!empty($subjectResults)) {
        $html .= '<div class="section-heading">2. Detailed Subject-Wise Grades & Credits</div>';
        
        // Group subjects by semester_info
        $groupedSubjects = [];
        foreach ($subjectResults as $sr) {
            $semKey = trim((string)($sr['semester_info'] ?? 'General / Other'));
            if ($semKey === '') $semKey = 'General / Other';
            $groupedSubjects[$semKey][] = $sr;
        }

        foreach ($groupedSubjects as $semKey => $subjects) {
            $html .= '<div class="sub-heading">' . htmlspecialchars($semKey) . '</div>';
            $html .= '<table class="data-table">
                <thead>
                    <tr>
                        <th width="18%">Subject Code</th>
                        <th width="48%">Course Title</th>
                        <th width="12%" align="center">Credits</th>
                        <th width="12%" align="center">Grade</th>
                        <th width="10%" align="center">Result</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($subjects as $s) {
                $code = $s['subject_code'] ?? '-';
                $title = $s['subject_name'] ?? '-';
                $credits = $s['credits'] ?? '-';
                $grade = strtoupper(trim((string)($s['grade'] ?? '-')));
                $isPass = ($grade !== 'F' && $grade !== 'FAIL' && $grade !== 'AB' && $grade !== '-');

                $badgeCls = $isPass ? 'grade-pass' : 'grade-fail';
                $resultText = $isPass ? 'PASS' : ($grade === 'AB' ? 'ABSENT' : 'FAIL');

                $html .= '<tr>
                    <td class="font-mono"><strong>' . htmlspecialchars($code) . '</strong></td>
                    <td>' . htmlspecialchars($title) . '</td>
                    <td align="center">' . htmlspecialchars((string)$credits) . '</td>
                    <td align="center"><span class="' . $badgeCls . '">' . htmlspecialchars($grade) . '</span></td>
                    <td align="center" class="' . ($isPass ? 'text-success' : 'text-danger') . ' font-bold">' . htmlspecialchars($resultText) . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        }
    }

    // 3. ATTENDANCE SUMMARY & MONTHLY SESSIONS
    if (!empty($attendanceSummary)) {
        $html .= '<div class="section-heading">3. Attendance Records & Monthly Breakdown</div>';
        $html .= '<table class="data-table">
            <thead>
                <tr>
                    <th width="35%">Session / Month</th>
                    <th width="20%">Semester</th>
                    <th width="15%" align="center">Present</th>
                    <th width="15%" align="center">Total</th>
                    <th width="15%" align="center">Percentage</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($attendanceSummary as $att) {
            $sess = $att['session_name'] ?? $att['month_name'] ?? 'Session';
            $sem = $att['semester'] ?? '-';
            $present = (int)($att['present'] ?? $att['total_present'] ?? 0);
            $total = (int)($att['total'] ?? $att['total_classes'] ?? 0);
            $pct = $total > 0 ? round(($present / $total) * 100, 2) : 0;
            $pctCls = $pct >= 75 ? 'text-success' : ($pct >= 65 ? 'text-warning' : 'text-danger');

            $html .= '<tr>
                <td><strong>' . htmlspecialchars($sess) . '</strong></td>
                <td>' . htmlspecialchars($sem) . '</td>
                <td align="center">' . $present . '</td>
                <td align="center">' . $total . '</td>
                <td align="center" class="' . $pctCls . ' font-bold">' . $pct . '%</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    }

    // 4. MID MARKS (if available)
    if (!empty($midmarks)) {
        $html .= '<div class="section-heading">4. Internal / Mid Examinations Assessment</div>';
        $html .= '<table class="data-table">
            <thead>
                <tr>
                    <th width="18%">Code</th>
                    <th width="42%">Subject Name</th>
                    <th width="10%" align="center">DES1</th>
                    <th width="10%" align="center">OBJ1</th>
                    <th width="10%" align="center">DES2</th>
                    <th width="10%" align="center">Total / 30</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($midmarks as $mm) {
            $html .= '<tr>
                <td class="font-mono"><strong>' . htmlspecialchars($mm['subject_code'] ?? '') . '</strong></td>
                <td>' . htmlspecialchars($mm['subject_name'] ?? '') . '</td>
                <td align="center">' . htmlspecialchars((string)($mm['des1'] ?? '-')) . '</td>
                <td align="center">' . htmlspecialchars((string)($mm['obj1'] ?? '-')) . '</td>
                <td align="center">' . htmlspecialchars((string)($mm['des2'] ?? '-')) . '</td>
                <td align="center" class="font-bold">' . htmlspecialchars((string)($mm['total'] ?? '-')) . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    }

    // 5. PLACEMENTS & CERTIFICATIONS (if any)
    if (!empty($jobOffers) || !empty($nptelCertificates)) {
        $html .= '<div class="section-heading">5. Placements & Technical Certifications</div>';
        if (!empty($jobOffers)) {
            $html .= '<div class="sub-heading">Campus Placement Offers</div>';
            $html .= '<table class="data-table">
                <thead>
                    <tr>
                        <th width="40%">Company Name</th>
                        <th width="30%">Package / CTC</th>
                        <th width="30%">Role / Designation</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($jobOffers as $job) {
                $html .= '<tr>
                    <td><strong>' . htmlspecialchars($job['company_name'] ?? '') . '</strong></td>
                    <td class="text-success font-bold">' . htmlspecialchars($job['package'] ?? 'Selected') . '</td>
                    <td>' . htmlspecialchars($job['designation'] ?? 'Graduate Trainee') . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        }

        if (!empty($nptelCertificates)) {
            $html .= '<div class="sub-heading">NPTEL & Verified Certifications</div>';
            $html .= '<table class="data-table">
                <thead>
                    <tr>
                        <th width="60%">Course Name</th>
                        <th width="20%" align="center">Score / Grade</th>
                        <th width="20%" align="center">Certificate Type</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($nptelCertificates as $cert) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($cert['course_name'] ?? '') . '</td>
                    <td align="center" class="font-bold">' . htmlspecialchars((string)($cert['score'] ?? '-')) . '</td>
                    <td align="center">' . htmlspecialchars($cert['certificate_type'] ?? 'Elite / Silver') . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        }
    }

    // Footer
    $html .= '
    <div class="report-footer">
        <table width="100%" style="border:none;">
            <tr>
                <td width="70%" style="border:none;">
                    This document is a computer-generated comprehensive academic report. For official university transcripts, contact the Examination Branch.
                </td>
                <td width="30%" align="right" style="border:none;">
                    <strong>Student Details Portal</strong>
                </td>
            </tr>
        </table>
    </div>';

    // CSS Styling for High-Resolution PDF
    $css = '
    body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; }
    .report-header { border-bottom: 2.5px solid #3b82f6; padding-bottom: 10px; margin-bottom: 12px; }
    .college-title { font-size: 13px; font-weight: bold; color: #1e3a8a; letter-spacing: 0.2px; }
    .college-subtitle { font-size: 8.5px; color: #64748b; margin: 2px 0 4px; }
    .doc-title { font-size: 10.5px; font-weight: bold; color: #0f172a; margin-top: 4px; }
    .report-meta { font-size: 8.5px; color: #475569; line-height: 1.3; }
    .status-tag { display: inline-block; background: #2563eb; color: #ffffff; padding: 2px 6px; font-size: 7.5px; font-weight: bold; border-radius: 3px; margin-top: 3px; }
    
    .student-profile-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; margin-bottom: 14px; }
    .info-grid-table td { padding: 3px 6px; font-size: 9.5px; border: none; }
    .info-lbl { color: #64748b; font-weight: normal; width: 22%; }
    .info-val { color: #0f172a; width: 28%; }
    .student-photo { width: 85px; height: 95px; border: 1px solid #cbd5e1; border-radius: 4px; object-fit: cover; }
    .student-photo-placeholder { width: 85px; height: 95px; background: #e2e8f0; color: #64748b; font-size: 32px; font-weight: bold; text-align: center; line-height: 95px; border-radius: 4px; border: 1px solid #cbd5e1; }

    .section-heading { background: #1e3a8a; color: #ffffff; padding: 5px 8px; font-size: 10px; font-weight: bold; margin: 12px 0 6px 0; border-radius: 3px; }
    .sub-heading { font-size: 9.5px; font-weight: bold; color: #1e40af; margin: 6px 0 3px 0; border-left: 3px solid #3b82f6; padding-left: 5px; }

    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .data-table th { background: #f1f5f9; color: #1e293b; font-size: 8.5px; font-weight: bold; padding: 5px 6px; border: 1px solid #cbd5e1; text-transform: uppercase; }
    .data-table td { padding: 4px 6px; border: 1px solid #e2e8f0; font-size: 8.5px; }
    .data-table tr:nth-child(even) td { background: #fafafa; }

    .grade-pass { color: #15803d; font-weight: bold; }
    .grade-fail { color: #b91c1c; font-weight: bold; }
    .font-mono { font-family: monospace; }
    .font-bold { font-weight: bold; }
    .text-primary { color: #2563eb; }
    .text-success { color: #16a34a; }
    .text-warning { color: #d97706; }
    .text-danger { color: #dc2626; }

    .report-footer { margin-top: 15px; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: 8px; color: #64748b; }
    ';

    require_once __DIR__ . '/../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 12,
        'margin_right' => 12,
        'margin_bottom' => 12,
        'margin_left' => 12,
    ]);

    $mpdf->SetTitle('Student Dossier - ' . $rollNumber);
    $mpdf->SetAuthor('Student Details Portal');
    $mpdf->SetFooter('{PAGENO} of {nbpg}');

    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html);

    if (ob_get_length()) {
        ob_end_clean();
    }

    $filename = 'Student_Report_' . preg_replace('/[^0-9A-Za-z_-]/', '', $rollNumber) . '_' . date('Ymd') . '.pdf';
    $mpdf->Output($filename, 'D');
    exit;

} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error generating PDF report: ' . $e->getMessage()]);
    exit;
}
