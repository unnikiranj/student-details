<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'placement') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placement Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .navbar-brand {
            color: white !important;
            font-weight: 700;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-briefcase me-2"></i>Placement Module</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Placement Officer'); ?></span>
                <a href="logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>Student Search</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search roll number/name...">
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" id="minCgpa" placeholder="Min CGPA" min="0" max="10" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" id="maxCgpa" placeholder="Max CGPA" min="0" max="10" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100" onclick="searchStudent()">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" id="resultsRow" style="display: none;">
                    <!-- Left Pane: Search Results -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Search Results</h5>
                                <button class="btn btn-sm btn-light" onclick="addAllToSelected()">
                                    <i class="fas fa-plus me-1"></i>Add All
                                </button>
                            </div>
                            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                                <div id="searchResults"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Pane: Selected Students -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Selected Students</h5>
                                <div>
                                    <span class="text-white small me-2" id="selectedCount">0 selected</span>
                                    <button class="btn btn-sm btn-outline-light me-1" onclick="deselectAll()">
                                        <i class="fas fa-times me-1"></i>Deselect All
                                    </button>
                                    <button class="btn btn-sm btn-light" onclick="exportSelected()">
                                        <i class="fas fa-download me-1"></i>Export
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                                <div id="selectedStudents"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allStudents = []; // Store all search results
        let selectedStudents = []; // Store selected students

        function searchStudent() {
            const query = $('#searchInput').val().trim();
            const minCgpa = $('#minCgpa').val();
            const maxCgpa = $('#maxCgpa').val();
            
            if (!query && !minCgpa && !maxCgpa) {
                alert('Please enter search criteria');
                return;
            }

            $.ajax({
                url: 'placement_search.php',
                method: 'POST',
                dataType: 'json',
                data: { 
                    query: query,
                    min_cgpa: minCgpa,
                    max_cgpa: maxCgpa
                },
                success: function(res) {
                    if (res.success && res.students.length > 0) {
                        allStudents = res.students;
                        displaySearchResults(res.students);
                        $('#resultsRow').show();
                    } else {
                        $('#resultsRow').hide();
                        alert('No students found');
                    }
                },
                error: function() {
                    $('#resultsRow').hide();
                    alert('Search failed');
                }
            });
        }

        function displaySearchResults(students) {
            let html = '<div class="list-group list-group-flush">';
            
            students.forEach(student => {
                const isSelected = selectedStudents.some(s => s.roll_no === student.roll_no);
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">${student.student_name}</div>
                            <div class="small text-muted font-mono">${student.roll_no}</div>
                            <span class="badge ${student.cgpa >= 8 ? 'bg-success' : (student.cgpa >= 6 ? 'bg-warning text-dark' : 'bg-danger')}">CGPA: ${student.cgpa}</span>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="addToSelected('${student.roll_no}')" ${isSelected ? 'disabled' : ''}>
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
            $('#searchResults').html(html);
        }

        function addToSelected(rollNo) {
            const student = allStudents.find(s => s.roll_no === rollNo);
            if (student && !selectedStudents.some(s => s.roll_no === rollNo)) {
                selectedStudents.push(student);
                displaySelectedStudents();
                displaySearchResults(allStudents); // Refresh to disable added button
            }
        }

        function addAllToSelected() {
            allStudents.forEach(student => {
                if (!selectedStudents.some(s => s.roll_no === student.roll_no)) {
                    selectedStudents.push(student);
                }
            });
            displaySelectedStudents();
            displaySearchResults(allStudents);
        }

        function removeFromSelected(rollNo) {
            selectedStudents = selectedStudents.filter(s => s.roll_no !== rollNo);
            displaySelectedStudents();
            displaySearchResults(allStudents);
        }

        function deselectAll() {
            selectedStudents = [];
            displaySelectedStudents();
            displaySearchResults(allStudents);
        }

        function displaySelectedStudents() {
            $('#selectedCount').text(selectedStudents.length + ' selected');
            
            if (selectedStudents.length === 0) {
                $('#selectedStudents').html('<div class="p-4 text-center text-muted">No students selected</div>');
                return;
            }
            
            let html = '<div class="list-group list-group-flush">';
            
            selectedStudents.forEach(student => {
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">${student.student_name}</div>
                            <div class="small text-muted font-mono">${student.roll_no}</div>
                            <span class="badge ${student.cgpa >= 8 ? 'bg-success' : (student.cgpa >= 6 ? 'bg-warning text-dark' : 'bg-danger')}">CGPA: ${student.cgpa}</span>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="removeFromSelected('${student.roll_no}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
            $('#selectedStudents').html(html);
        }

        function exportSelected() {
            if (selectedStudents.length === 0) {
                alert('Please select at least one student');
                return;
            }

            let csv = 'Roll Number,Name,CGPA\n';
            selectedStudents.forEach(student => {
                csv += `${student.roll_no},${student.student_name},${student.cgpa}\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'selected_students.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function showStudentDetails(rollNo, name, cgpa) {
            // This function is no longer needed with the new layout
        }

        // Allow search on Enter key
        $('#searchInput').on('keypress', function(e) {
            if (e.which === 13) {
                searchStudent();
            }
        });
    </script>
</body>
</html>
