<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has placement role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'placement') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = trim($_POST['query'] ?? '');
    $minCgpa = $_POST['min_cgpa'] ?? '';
    $maxCgpa = $_POST['max_cgpa'] ?? '';
    
    if (empty($query) && empty($minCgpa) && empty($maxCgpa)) {
        echo json_encode(['success' => false, 'message' => 'Search criteria is required']);
        exit;
    }
    
    try {
        // Build query with optional filters
        $whereConditions = [];
        $params = [];
        $types = '';
        
        if (!empty($query)) {
            $searchTerm = '%' . $query . '%';
            $whereConditions[] = "(roll_no LIKE ? OR student_name LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        if (!empty($minCgpa)) {
            $whereConditions[] = "CAST(cgpa AS DECIMAL(5,2)) >= ?";
            $params[] = $minCgpa;
            $types .= 'd';
        }
        
        if (!empty($maxCgpa)) {
            $whereConditions[] = "CAST(cgpa AS DECIMAL(5,2)) <= ?";
            $params[] = $maxCgpa;
            $types .= 'd';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get all unique students matching search criteria with their latest CGPA
        $sql = "
            SELECT 
                sr.roll_no, 
                sr.student_name,
                sr.cgpa
            FROM student_results sr
            INNER JOIN (
                SELECT roll_no, MAX(id) as max_id
                FROM student_results
                WHERE $whereClause
                GROUP BY roll_no
            ) latest ON sr.roll_no = latest.roll_no AND sr.id = latest.max_id
            ORDER BY sr.student_name ASC
            LIMIT 200
        ";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'roll_no' => $row['roll_no'],
                'student_name' => $row['student_name'],
                'cgpa' => $row['cgpa'] ?? 'N/A'
            ];
        }
        
        echo json_encode(['success' => true, 'students' => $students]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
