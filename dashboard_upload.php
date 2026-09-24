<?php
/**
 * dashboard_upload.php
 * Ultra-Modern Data Upload Center for Student Details Management System
 * Supports Attendance, Mid-Term Marks, Smart Scores, and Course Codes
 * with live batch progress, file queues, auto-metadata extraction, and management.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config.php';

// Detect whether this file is being rendered standalone or included inside dashboard.php
$isStandalone = !defined('EMBEDDED_IN_DASHBOARD');

if ($isStandalone):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Upload Center - Student Management Portal</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: #f8fafc;
            padding: 24px;
        }
    </style>
</head>
<body>
<div class="container-fluid" style="max-width: 1400px;">
<?php endif; ?>

<style>
/* ======== DATA UPLOAD CENTER MODERN DESIGN SYSTEM ======== */
:root {
    --up-primary: #4f46e5;
    --up-primary-light: #eef2ff;
    --up-primary-dark: #4338ca;
    --up-primary-glow: rgba(79, 70, 229, 0.25);
    --up-cyan: #06b6d4;
    --up-emerald: #10b981;
    --up-border: rgba(226, 232, 240, 0.85);
    --up-border-subtle: rgba(0, 0, 0, 0.06);
    --up-text-main: #0f172a;
    --up-text-muted: #64748b;
    --up-radius-md: 14px;
    --up-radius-lg: 20px;
    --up-shadow-sm: 0 2px 4px rgba(0,0,0,0.04);
    --up-shadow-md: 0 10px 30px -5px rgba(0,0,0,0.06), 0 4px 10px -2px rgba(0,0,0,0.02);
}

.upload-center-wrapper {
    font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
    color: var(--up-text-main);
}

/* Header Banner */
.upload-header-banner {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4f46e5 100%);
    border-radius: var(--up-radius-lg);
    padding: 30px 34px;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 20px 40px -15px rgba(49, 46, 129, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.upload-header-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.upload-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #e0e7ff;
    margin-bottom: 10px;
}

.upload-title {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 6px;
    color: #ffffff;
}

.upload-subtitle {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.82);
    max-width: 680px;
    line-height: 1.5;
    margin-bottom: 0;
}

/* Quick Stats Bar */
.upload-stats-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.upload-stat-card {
    background: #ffffff;
    border: 1px solid var(--up-border);
    border-radius: var(--up-radius-md);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--up-shadow-sm);
    transition: all 0.2s ease;
}

.upload-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--up-shadow-md);
    border-color: rgba(79, 70, 229, 0.3);
}

.upload-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.upload-stat-info .stat-value {
    font-size: 20px;
    font-weight: 800;
    line-height: 1.2;
    color: var(--up-text-main);
    font-family: 'JetBrains Mono', monospace;
}

.upload-stat-info .stat-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--up-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

/* Category Switcher Tabs */
.upload-nav-tabs {
    display: flex;
    gap: 6px;
    background: #f1f5f9;
    padding: 6px;
    border-radius: 16px;
    border: 1px solid var(--up-border);
    margin-bottom: 24px;
    overflow-x: auto;
    scrollbar-width: thin;
}

.upload-nav-btn {
    border: none;
    background: transparent;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    color: var(--up-text-muted);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    text-decoration: none;
}

.upload-nav-btn:hover {
    color: var(--up-primary);
    background: rgba(255, 255, 255, 0.7);
}

.upload-nav-btn.active {
    background: #ffffff;
    color: var(--up-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.upload-nav-btn.active .nav-btn-icon {
    background: linear-gradient(135deg, var(--up-primary), var(--up-primary-dark));
    color: #ffffff;
}

.nav-btn-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    background: rgba(100, 116, 139, 0.1);
    color: var(--up-text-muted);
    transition: all 0.2s ease;
}

/* Card Container */
.upload-card {
    background: #ffffff;
    border: 1px solid var(--up-border);
    border-radius: var(--up-radius-lg);
    box-shadow: var(--up-shadow-md);
    overflow: hidden;
    margin-bottom: 24px;
    transition: box-shadow 0.25s ease;
}

.upload-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--up-border);
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.upload-card-header h5 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--up-text-main);
}

.upload-card-body {
    padding: 24px;
}

/* Modern Drop Zone */
.modern-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: var(--up-radius-md);
    padding: 36px 24px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    outline: none;
}

.modern-dropzone:hover {
    border-color: var(--up-primary);
    background: #f5f3ff;
    transform: translateY(-1px);
}

.modern-dropzone.dragover {
    border-color: var(--up-primary);
    background: #eef2ff;
    box-shadow: 0 0 0 4px var(--up-primary-glow);
    transform: scale(1.01);
}

.dropzone-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    color: var(--up-primary);
    font-size: 26px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.2);
}

.modern-dropzone:hover .dropzone-icon-wrap {
    transform: translateY(-3px) scale(1.05);
    background: linear-gradient(135deg, var(--up-primary) 0%, #6366f1 100%);
    color: #ffffff;
    box-shadow: 0 12px 24px -4px rgba(79, 70, 229, 0.35);
}

.dropzone-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--up-text-main);
    margin-bottom: 6px;
}

.dropzone-subtitle {
    font-size: 13px;
    color: var(--up-text-muted);
    margin-bottom: 14px;
}

.dropzone-formats {
    display: inline-flex;
    gap: 6px;
}

.format-badge {
    padding: 3px 8px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    font-size: 11px;
    font-weight: 700;
    color: var(--up-text-muted);
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 0.2px;
}

.file-input-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

/* File Queue Preview */
.file-queue-container {
    margin-top: 20px;
    border: 1px solid var(--up-border);
    border-radius: var(--up-radius-md);
    background: #ffffff;
    overflow: hidden;
}

.file-queue-header {
    background: #f8fafc;
    padding: 10px 16px;
    border-bottom: 1px solid var(--up-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    font-weight: 700;
    color: var(--up-text-muted);
    text-transform: uppercase;
}

.file-queue-list {
    max-height: 240px;
    overflow-y: auto;
}

.file-queue-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    border-bottom: 1px solid var(--up-border-subtle);
    font-size: 13px;
    transition: background 0.15s ease;
}

.file-queue-item:last-child {
    border-bottom: none;
}

.file-queue-item:hover {
    background: #f8fafc;
}

.file-meta-col {
    display: flex;
    align-items: center;
    gap: 12px;
    overflow: hidden;
}

.file-type-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.file-type-icon.excel {
    background: #ecfdf5;
    color: #059669;
}

.file-type-icon.csv {
    background: #eff6ff;
    color: #2563eb;
}

.file-name-text {
    font-weight: 600;
    color: var(--up-text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
}

.file-size-text {
    font-size: 11px;
    color: #94a3b8;
    font-family: 'JetBrains Mono', monospace;
}

.file-remove-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    padding: 4px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.file-remove-btn:hover {
    background: #fee2e2;
    color: #ef4444;
}

/* Upload Progress Hub */
.upload-progress-card {
    background: #ffffff;
    border: 1px solid var(--up-border);
    border-radius: var(--up-radius-md);
    padding: 20px;
    margin-top: 20px;
    display: none;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.progress-header-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--up-text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}

.progress-percent-badge {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    font-weight: 700;
    color: var(--up-primary);
    background: var(--up-primary-light);
    padding: 2px 8px;
    border-radius: 6px;
}

.modern-progress-track {
    height: 8px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
    margin-bottom: 16px;
}

.modern-progress-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--up-primary), #06b6d4);
    border-radius: 999px;
    transition: width 0.3s ease;
}

/* Activity Feed Items */
.upload-activity-feed {
    max-height: 220px;
    overflow-y: auto;
    font-size: 12.5px;
}

.activity-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 6px;
    background: #f8fafc;
    border: 1px solid var(--up-border-subtle);
}

.activity-item.success {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.activity-item.error {
    background: #fef2f2;
    border-color: #fecaca;
}

.activity-filename {
    font-weight: 600;
    color: var(--up-text-main);
}

.activity-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
}

.activity-status-chip.success {
    background: #dcfce7;
    color: #166534;
}

.activity-status-chip.error {
    background: #fee2e2;
    color: #991b1b;
}

.activity-status-chip.pending {
    background: #e0e7ff;
    color: #3730a3;
}

/* Metadata Chips */
.meta-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
}

.meta-chip {
    font-size: 10.5px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 4px;
    background: #e2e8f0;
    color: #475569;
    font-family: 'JetBrains Mono', monospace;
}

/* Primary Action Buttons */
.btn-upload-primary {
    background: linear-gradient(135deg, var(--up-primary) 0%, var(--up-primary-dark) 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    padding: 11px 24px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 14px var(--up-primary-glow);
}

.btn-upload-primary:hover {
    background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.4);
    color: #ffffff;
}

.btn-upload-primary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Modern Management Table */
.mgmt-table-search {
    max-width: 280px;
    position: relative;
}

.mgmt-table-search input {
    border-radius: 10px;
    padding-left: 36px;
    font-size: 13px;
    border: 1px solid var(--up-border);
}

.mgmt-table-search .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

.modern-mgmt-table {
    margin-bottom: 0;
    font-size: 13px;
}

.modern-mgmt-table thead th {
    background: #0f172a;
    color: #f8fafc;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 14px;
    border: none;
}

.modern-mgmt-table tbody td {
    padding: 12px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.modern-mgmt-table tbody tr:hover {
    background: #f8fafc;
}

.sem-badge {
    background: #e0e7ff;
    color: #3730a3;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 11px;
}

.branch-badge {
    background: #ecfeff;
    color: #0891b2;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 11px;
}

.btn-delete-row {
    background: #fee2e2;
    color: #dc2626;
    border: none;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-delete-row:hover {
    background: #fecaca;
    color: #b91c1c;
}

.empty-state-box {
    text-align: center;
    padding: 40px 20px;
    color: var(--up-text-muted);
}

.empty-state-icon {
    font-size: 36px;
    color: #cbd5e1;
    margin-bottom: 12px;
}
</style>

<div class="upload-center-wrapper">
    <!-- Header Banner -->
    <div class="upload-header-banner">
        <div class="upload-badge-pill">
            <i class="fas fa-bolt text-warning"></i> Data Engine v2.5
        </div>
        <h2 class="upload-title">
            <i class="fas fa-cloud-arrow-up me-2"></i>Data Upload Center
        </h2>
        <p class="upload-subtitle">
            Upload attendance records, mid-term examinations, smart scores, and course catalogs. Automatic metadata parsing, duplicate prevention, and real-time verification.
        </p>
    </div>

    <!-- Quick Stats Ribbon -->
    <div class="upload-stats-strip">
        <div class="upload-stat-card">
            <div class="upload-stat-icon" style="background: #eef2ff; color: #4f46e5;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="upload-stat-info">
                <div class="stat-value" id="statAttendanceCount">-</div>
                <div class="stat-label">Attendance Files</div>
            </div>
        </div>

        <div class="upload-stat-card">
            <div class="upload-stat-icon" style="background: #ecfeff; color: #0891b2;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="upload-stat-info">
                <div class="stat-value" id="statMidmarksCount">-</div>
                <div class="stat-label">Mid Marks Files</div>
            </div>
        </div>

        <div class="upload-stat-card">
            <div class="upload-stat-icon" style="background: #ecfdf5; color: #059669;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="upload-stat-info">
                <div class="stat-value">SHA-256</div>
                <div class="stat-label">Deduplication</div>
            </div>
        </div>

        <div class="upload-stat-card">
            <div class="upload-stat-icon" style="background: #fff7ed; color: #ea580c;">
                <i class="fas fa-file-excel"></i>
            </div>
            <div class="upload-stat-info">
                <div class="stat-value">XLSX / CSV</div>
                <div class="stat-label">Supported Input</div>
            </div>
        </div>
    </div>

    <!-- Segmented Navigation Tabs -->
    <div class="upload-nav-tabs">
        <button type="button" class="upload-nav-btn active" onclick="switchUploadTab('attendanceTab')">
            <span class="nav-btn-icon"><i class="fas fa-calendar-check"></i></span>
            Attendance Upload
        </button>
        <button type="button" class="upload-nav-btn" onclick="switchUploadTab('midmarksTab')">
            <span class="nav-btn-icon"><i class="fas fa-chart-bar"></i></span>
            Mid Marks Upload
        </button>
        <button type="button" class="upload-nav-btn" onclick="switchUploadTab('smartscoreTab')">
            <span class="nav-btn-icon"><i class="fas fa-trophy"></i></span>
            Smart Score Upload
        </button>
        <button type="button" class="upload-nav-btn" onclick="switchUploadTab('courseCodesTab')">
            <span class="nav-btn-icon"><i class="fas fa-book"></i></span>
            Course Codes Upload
        </button>
        <button type="button" class="upload-nav-btn" onclick="switchUploadTab('manageTab')">
            <span class="nav-btn-icon"><i class="fas fa-database"></i></span>
            Manage Upload History
        </button>
    </div>

    <!-- TAB 1: ATTENDANCE UPLOAD -->
    <div id="attendanceTab" class="upload-tab-pane">
        <div class="upload-card">
            <div class="upload-card-header">
                <h5><i class="fas fa-calendar-check text-primary"></i>Batch Attendance Upload</h5>
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">Multi-File Supported</span>
            </div>
            <div class="upload-card-body">
                <form id="unifiedAttendanceForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ACADEMIC YEAR <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="uAttendanceAcademicYear" name="academic_year" placeholder="e.g. 2025-26" required value="2025-26">
                            <div class="form-text small">Standard academic year format: YYYY-YY</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">SESSION NAME PREVIEW</label>
                            <div class="form-control bg-light fw-bold text-primary font-mono" id="uAttendanceSessionPreview">
                                Auto-generated from file
                            </div>
                        </div>
                    </div>

                    <!-- Dropzone -->
                    <div class="modern-dropzone" id="uAttendanceDrop">
                        <div class="dropzone-icon-wrap">
                            <i class="fas fa-cloud-arrow-up"></i>
                        </div>
                        <div class="dropzone-title">Drag & drop attendance files here</div>
                        <div class="dropzone-subtitle">or click to browse from your computer (select multiple files at once)</div>
                        <div class="dropzone-formats">
                            <span class="format-badge">.XLSX</span>
                            <span class="format-badge">.XLS</span>
                            <span class="format-badge">.CSV</span>
                        </div>
                        <input type="file" class="file-input-hidden" id="uAttendanceFileInput" name="attendanceFile" accept=".csv,.xlsx,.xls" multiple>
                    </div>

                    <!-- Selected Files Queue -->
                    <div class="file-queue-container d-none" id="uAttendanceQueue">
                        <div class="file-queue-header">
                            <span>Selected Files (<span id="uAttendanceQueueCount">0</span>)</span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="clearFileQueue('attendance')">Clear All</button>
                        </div>
                        <div class="file-queue-list" id="uAttendanceQueueList"></div>
                    </div>

                    <!-- Action Bar -->
                    <div class="d-flex align-items-center justify-content-between mt-4">
                        <div class="small text-muted">
                            <i class="fas fa-info-circle me-1 text-primary"></i>Files with matching hashes will automatically be skipped.
                        </div>
                        <button type="submit" class="btn-upload-primary" id="uAttendanceSubmitBtn">
                            <i class="fas fa-upload"></i>Start Upload Process
                        </button>
                    </div>
                </form>

                <!-- Progress Hub -->
                <div class="upload-progress-card" id="uAttendanceProgressCard">
                    <div class="progress-header">
                        <div class="progress-header-title">
                            <i class="fas fa-circle-notch fa-spin text-primary" id="uAttendanceSpinner"></i>
                            <span id="uAttendanceProgressTitle">Uploading files...</span>
                        </div>
                        <span class="progress-percent-badge" id="uAttendancePercent">0%</span>
                    </div>
                    <div class="modern-progress-track">
                        <div class="modern-progress-bar" id="uAttendanceProgressBar"></div>
                    </div>
                    <div class="upload-activity-feed" id="uAttendanceActivityFeed"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: MID MARKS UPLOAD -->
    <div id="midmarksTab" class="upload-tab-pane d-none">
        <div class="upload-card">
            <div class="upload-card-header">
                <h5><i class="fas fa-chart-bar text-primary"></i>Mid-Term Marks CSV / Excel Upload</h5>
                <span class="badge bg-info-subtle text-info fw-bold px-3 py-2 rounded-pill">2-Row Header Auto-Parser</span>
            </div>
            <div class="upload-card-body">
                <form id="unifiedMidmarksForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">ACADEMIC YEAR (OPTIONAL)</label>
                            <input type="text" class="form-control" id="uMidAcademicYear" placeholder="e.g. 2025-26">
                            <div class="form-text small">Auto-extracted if blank</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">SEMESTER INFO (OPTIONAL)</label>
                            <input type="text" class="form-control" id="uMidSemesterInfo" placeholder="e.g. II B.Tech II Sem">
                            <div class="form-text small">Auto-extracted if blank</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">SECTION (OPTIONAL)</label>
                            <input type="text" class="form-control" id="uMidSection" placeholder="e.g. A">
                            <div class="form-text small">Auto-extracted if blank</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">COLLEGE / NOTES</label>
                            <input type="text" class="form-control" id="uMidCollegeName" placeholder="PVPSIT">
                        </div>
                    </div>

                    <!-- Dropzone -->
                    <div class="modern-dropzone" id="uMidmarksDrop">
                        <div class="dropzone-icon-wrap">
                            <i class="fas fa-file-waveform"></i>
                        </div>
                        <div class="dropzone-title">Drag & drop Mid-Term Marks files here</div>
                        <div class="dropzone-subtitle">or click to browse Excel/CSV sheets with 2-row headers</div>
                        <div class="dropzone-formats">
                            <span class="format-badge">.CSV</span>
                            <span class="format-badge">.XLSX</span>
                            <span class="format-badge">.XLS</span>
                        </div>
                        <input type="file" class="file-input-hidden" id="uMidmarksFileInput" accept=".csv,.xlsx,.xls" multiple>
                    </div>

                    <!-- Selected Files Queue -->
                    <div class="file-queue-container d-none" id="uMidmarksQueue">
                        <div class="file-queue-header">
                            <span>Selected Files (<span id="uMidmarksQueueCount">0</span>)</span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="clearFileQueue('midmarks')">Clear All</button>
                        </div>
                        <div class="file-queue-list" id="uMidmarksQueueList"></div>
                    </div>

                    <!-- Action Bar -->
                    <div class="d-flex align-items-center justify-content-between mt-4">
                        <div class="small text-muted">
                            <i class="fas fa-info-circle me-1 text-primary"></i>System maps subjective, objective, assignments, and total internal marks.
                        </div>
                        <button type="submit" class="btn-upload-primary" id="uMidmarksSubmitBtn">
                            <i class="fas fa-upload"></i>Upload Mid Marks
                        </button>
                    </div>
                </form>

                <!-- Progress Hub -->
                <div class="upload-progress-card" id="uMidmarksProgressCard">
                    <div class="progress-header">
                        <div class="progress-header-title">
                            <i class="fas fa-circle-notch fa-spin text-primary" id="uMidmarksSpinner"></i>
                            <span id="uMidmarksProgressTitle">Processing Mid Marks...</span>
                        </div>
                        <span class="progress-percent-badge" id="uMidmarksPercent">0%</span>
                    </div>
                    <div class="modern-progress-track">
                        <div class="modern-progress-bar" id="uMidmarksProgressBar"></div>
                    </div>
                    <div class="upload-activity-feed" id="uMidmarksActivityFeed"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: SMART SCORE UPLOAD -->
    <div id="smartscoreTab" class="upload-tab-pane d-none">
        <div class="upload-card">
            <div class="upload-card-header">
                <h5><i class="fas fa-trophy text-warning"></i>Smart Score Leaderboard Upload</h5>
                <span class="badge bg-warning-subtle text-warning fw-bold px-3 py-2 rounded-pill">Global Ranking Excel</span>
            </div>
            <div class="upload-card-body">
                <form id="unifiedSmartscoreForm">
                    <div class="modern-dropzone" id="uSmartscoreDrop">
                        <div class="dropzone-icon-wrap" style="background: linear-gradient(135deg, #fef3c7, #fed7aa); color: #d97706;">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="dropzone-title">Drag & drop Smart Score Excel file here</div>
                        <div class="dropzone-subtitle">Expected columns: Rank, Roll Number, Name, Mobile, Overall Score</div>
                        <div class="dropzone-formats">
                            <span class="format-badge">.XLSX</span>
                            <span class="format-badge">.XLS</span>
                        </div>
                        <input type="file" class="file-input-hidden" id="uSmartscoreFileInput" accept=".xlsx,.xls">
                    </div>

                    <!-- Single File Preview -->
                    <div class="file-queue-container d-none" id="uSmartscoreQueue">
                        <div class="file-queue-header">Selected File</div>
                        <div class="file-queue-list" id="uSmartscoreQueueList"></div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn-upload-primary" id="uSmartscoreSubmitBtn" style="background: linear-gradient(135deg, #d97706, #b45309);">
                            <i class="fas fa-upload"></i>Upload Smart Scores
                        </button>
                    </div>

                    <div id="uSmartscoreStatusBox" class="mt-3"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 4: COURSE CODES UPLOAD -->
    <div id="courseCodesTab" class="upload-tab-pane d-none">
        <div class="upload-card">
            <div class="upload-card-header">
                <h5><i class="fas fa-book text-success"></i>Course Codes Catalog Upload</h5>
                <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">Curriculum Database</span>
            </div>
            <div class="upload-card-body">
                <form id="unifiedCourseCodesForm">
                    <div class="modern-dropzone" id="uCourseCodesDrop">
                        <div class="dropzone-icon-wrap" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="dropzone-title">Drag & drop Course Codes Excel file here</div>
                        <div class="dropzone-subtitle">Expected columns: Year, Semester, Course Code, Course Name, Credits, Regulation, Branch</div>
                        <div class="dropzone-formats">
                            <span class="format-badge">.XLSX</span>
                            <span class="format-badge">.XLS</span>
                        </div>
                        <input type="file" class="file-input-hidden" id="uCourseCodesFileInput" accept=".xlsx,.xls">
                    </div>

                    <!-- Single File Preview -->
                    <div class="file-queue-container d-none" id="uCourseCodesQueue">
                        <div class="file-queue-header">Selected File</div>
                        <div class="file-queue-list" id="uCourseCodesQueueList"></div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn-upload-primary" id="uCourseCodesSubmitBtn" style="background: linear-gradient(135deg, #059669, #047857);">
                            <i class="fas fa-upload"></i>Upload Course Codes
                        </button>
                    </div>

                    <div id="uCourseCodesStatusBox" class="mt-3"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 5: MANAGE UPLOAD HISTORY -->
    <div id="manageTab" class="upload-tab-pane d-none">
        <div class="row g-4">
            <!-- Attendance Uploads History -->
            <div class="col-12">
                <div class="upload-card">
                    <div class="upload-card-header">
                        <h5><i class="fas fa-calendar-check text-primary"></i>Attendance Uploads Record History</h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="mgmt-table-search">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="form-control form-control-sm" id="searchAttendanceUploads" placeholder="Filter files, branch, sem...">
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" onclick="loadUnifiedAttendanceUploads()">
                                <i class="fas fa-rotate me-1" id="refreshAttIcon"></i>Refresh
                            </button>
                        </div>
                    </div>
                    <div class="upload-card-body p-0">
                        <div id="uAttendanceManageStatus" class="alert d-none m-3"></div>
                        <div class="table-responsive">
                            <table class="table modern-mgmt-table" id="uAttendanceTable">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Session Name</th>
                                        <th>Academic Year</th>
                                        <th>Sem</th>
                                        <th>Branch</th>
                                        <th>Section</th>
                                        <th>Uploaded On</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="uAttendanceTableBody">
                                    <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading records...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mid Marks Uploads History -->
            <div class="col-12">
                <div class="upload-card">
                    <div class="upload-card-header">
                        <h5><i class="fas fa-chart-bar text-primary"></i>Mid Marks Uploads Record History</h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="mgmt-table-search">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="form-control form-control-sm" id="searchMidmarksUploads" placeholder="Filter files, branch, sem...">
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" onclick="loadUnifiedMidmarksUploads()">
                                <i class="fas fa-rotate me-1" id="refreshMidIcon"></i>Refresh
                            </button>
                        </div>
                    </div>
                    <div class="upload-card-body p-0">
                        <div id="uMidmarksManageStatus" class="alert d-none m-3"></div>
                        <div class="table-responsive">
                            <table class="table modern-mgmt-table" id="uMidmarksTable">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Semester Info</th>
                                        <th>Academic Year</th>
                                        <th>Section</th>
                                        <th>Uploaded On</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="uMidmarksTableBody">
                                    <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading records...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Delete Confirmation Modal -->
<div class="modal fade" id="deleteUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="fas fa-triangle-exclamation me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted mb-2">Are you sure you want to permanently delete this upload record and all associated student data?</p>
                <div class="p-3 bg-light rounded-3 font-mono small fw-bold text-break text-danger" id="deleteTargetFileName">-</div>
                <div class="small text-muted mt-2">This action removes all parsed summary rows, subject grades, and marks. It cannot be undone.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="confirmDeleteUploadBtn">
                    <i class="fas fa-trash me-1"></i>Yes, Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ======== DATA UPLOAD CENTER JAVASCRIPT ========
const uploadQueues = {
    attendance: [],
    midmarks: [],
    smartscore: [],
    courseCodes: []
};

// Tab Switching
function switchUploadTab(tabId) {
    $('.upload-tab-pane').addClass('d-none');
    $('#' + tabId).removeClass('d-none');

    $('.upload-nav-btn').removeClass('active');
    $(`[onclick="switchUploadTab('${tabId}')"]`).addClass('active');

    if (tabId === 'manageTab') {
        loadUnifiedAttendanceUploads();
        loadUnifiedMidmarksUploads();
    }
}

// Utility: Format File Size
function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Bind Dropzones
function initDropZone(zoneId, inputId, queueKey, isMultiple) {
    const zone = $('#' + zoneId);
    const input = $('#' + inputId);
    if (!zone.length || !input.length) return;

    zone.off('click').on('click', function(e) {
        if ($(e.target).closest('button, input').length) return;
        input.trigger('click');
    });

    zone.off('dragenter dragover').on('dragenter dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        zone.addClass('dragover');
    });

    zone.off('dragleave drop').on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        zone.removeClass('dragover');
    });

    zone.off('drop').on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        zone.removeClass('dragover');
        const dtFiles = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
        if (dtFiles && dtFiles.length) {
            handleIncomingFiles(queueKey, dtFiles, isMultiple);
        }
    });

    input.off('change').on('change', function() {
        if (this.files && this.files.length) {
            handleIncomingFiles(queueKey, this.files, isMultiple);
        }
    });
}

function handleIncomingFiles(key, fileList, isMultiple) {
    const files = Array.from(fileList);
    if (!isMultiple) {
        uploadQueues[key] = files.slice(0, 1);
    } else {
        files.forEach(f => {
            if (!uploadQueues[key].some(existing => existing.name === f.name && existing.size === f.size)) {
                uploadQueues[key].push(f);
            }
        });
    }
    renderQueue(key);
}

function removeFileFromQueue(key, index) {
    uploadQueues[key].splice(index, 1);
    renderQueue(key);
}

function clearFileQueue(key) {
    uploadQueues[key] = [];
    renderQueue(key);
}

function renderQueue(key) {
    const list = uploadQueues[key];
    const prefix = key === 'attendance' ? 'uAttendance' :
                   key === 'midmarks' ? 'uMidmarks' :
                   key === 'smartscore' ? 'uSmartscore' : 'uCourseCodes';

    const container = $('#' + prefix + 'Queue');
    const listEl = $('#' + prefix + 'QueueList');
    const countEl = $('#' + prefix + 'QueueCount');

    if (countEl.length) countEl.text(list.length);

    if (!list.length) {
        container.addClass('d-none');
        listEl.empty();
        if (key === 'attendance') $('#uAttendanceSessionPreview').text('Auto-generated from file');
        return;
    }

    container.removeClass('d-none');
    listEl.empty();

    list.forEach((file, idx) => {
        const isExcel = file.name.endsWith('.xlsx') || file.name.endsWith('.xls');
        const iconClass = isExcel ? 'excel' : 'csv';
        const iconFa = isExcel ? 'fa-file-excel' : 'fa-file-csv';

        const itemHtml = `
            <div class="file-queue-item">
                <div class="file-meta-col">
                    <div class="file-type-icon ${iconClass}">
                        <i class="fas ${iconFa}"></i>
                    </div>
                    <div>
                        <div class="file-name-text" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
                        <div class="file-size-text">${formatBytes(file.size)}</div>
                    </div>
                </div>
                <button type="button" class="file-remove-btn" onclick="removeFileFromQueue('${key}', ${idx})" title="Remove file">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        listEl.append(itemHtml);
    });

    if (key === 'attendance' && list.length > 0) {
        const preview = list[0].name.replace(/\.[^/.]+$/, "").replace(/[^a-zA-Z0-9_]/g, '_').toLowerCase();
        $('#uAttendanceSessionPreview').text(list.length > 1 ? `${preview} (+${list.length - 1} more)` : preview);
    }
}

// Batch Upload Pipeline for Attendance & Midmarks
async function processBatchUpload(type, files, buildFormData) {
    const isAtt = type === 'attendance';
    const prefix = isAtt ? 'uAttendance' : 'uMidmarks';
    const progressCard = $('#' + prefix + 'ProgressCard');
    const progressBar = $('#' + prefix + 'ProgressBar');
    const percentBadge = $('#' + prefix + 'Percent');
    const titleEl = $('#' + prefix + 'ProgressTitle');
    const activityFeed = $('#' + prefix + 'ActivityFeed');
    const submitBtn = $('#' + prefix + 'SubmitBtn');

    if (!files.length) {
        alert('Please select at least one file to upload.');
        return;
    }

    submitBtn.prop('disabled', true);
    progressCard.show();
    activityFeed.empty();

    let successCount = 0;
    let failCount = 0;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const percent = Math.round(((i) / files.length) * 100);
        progressBar.css('width', percent + '%');
        percentBadge.text(percent + '%');
        titleEl.text(`Uploading ${i + 1} of ${files.length}: ${file.name}`);

        const activityId = 'act_' + i + '_' + Date.now();
        activityFeed.prepend(`
            <div class="activity-item" id="${activityId}">
                <div>
                    <div class="activity-filename">${escapeHtml(file.name)}</div>
                    <div class="small text-muted font-mono" id="${activityId}_msg"><i class="fas fa-spinner fa-spin me-1"></i>Uploading & parsing...</div>
                </div>
                <span class="activity-status-chip pending" id="${activityId}_chip">Processing</span>
            </div>
        `);

        try {
            const formData = buildFormData(file);
            const response = await uploadSingleAjax(type, formData);
            const res = typeof response === 'string' ? JSON.parse(response) : response;

            if (res && res.success) {
                successCount++;
                $(`#${activityId}`).addClass('success');
                $(`#${activityId}_chip`).removeClass('pending').addClass('success').html('<i class="fas fa-check me-1"></i>Success');
                
                let metaHtml = '';
                if (res.extracted) {
                    const ext = res.extracted;
                    metaHtml = `
                        <div class="meta-chip-list">
                            ${ext.branch ? `<span class="meta-chip">Branch: ${escapeHtml(ext.branch)}</span>` : ''}
                            ${ext.semester ? `<span class="meta-chip">Sem: ${escapeHtml(ext.semester)}</span>` : ''}
                            ${ext.semester_info ? `<span class="meta-chip">Sem: ${escapeHtml(ext.semester_info)}</span>` : ''}
                            ${ext.section ? `<span class="meta-chip">Sec: ${escapeHtml(ext.section)}</span>` : ''}
                            ${res.processed ? `<span class="meta-chip text-success">Rows: ${res.processed}</span>` : ''}
                        </div>
                    `;
                }
                $(`#${activityId}_msg`).html(`<span class="text-success">${escapeHtml(res.message || 'File uploaded successfully.')}</span>` + metaHtml);
            } else {
                failCount++;
                $(`#${activityId}`).addClass('error');
                $(`#${activityId}_chip`).removeClass('pending').addClass('error').html('<i class="fas fa-exclamation me-1"></i>Failed');
                $(`#${activityId}_msg`).html(`<span class="text-danger">${escapeHtml((res && res.message) ? res.message : 'Upload rejected.')}</span>`);
            }
        } catch (err) {
            failCount++;
            $(`#${activityId}`).addClass('error');
            $(`#${activityId}_chip`).removeClass('pending').addClass('error').html('<i class="fas fa-times me-1"></i>Error');
            const errText = (err && err.responseJSON && err.responseJSON.message) ? err.responseJSON.message : 'Server or network error';
            $(`#${activityId}_msg`).html(`<span class="text-danger">${escapeHtml(errText)}</span>`);
        }
    }

    progressBar.css('width', '100%');
    percentBadge.text('100%');
    titleEl.text(`Completed: ${successCount} succeeded, ${failCount} failed.`);
    $(`#${prefix}Spinner`).removeClass('fa-circle-notch fa-spin').addClass(failCount ? 'fa-triangle-exclamation text-warning' : 'fa-check-circle text-success');
    submitBtn.prop('disabled', false);

    // Clear queue on completion
    clearFileQueue(type);

    // Reload management tables & stats
    loadUnifiedAttendanceUploads();
    loadUnifiedMidmarksUploads();
}

function uploadSingleAjax(type, formData) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'upload_' + type + '.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (data) => resolve(data),
            error: (err) => reject(err)
        });
    });
}

// Attendance Submit
$('#unifiedAttendanceForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    const files = uploadQueues.attendance;
    const ay = ($('#uAttendanceAcademicYear').val() || '').trim();

    if (!ay) {
        alert('Please enter an academic year (e.g. 2025-26)');
        return;
    }

    processBatchUpload('attendance', files, (file) => {
        const fd = new FormData();
        fd.append('attendanceFile', file);
        fd.append('academic_year', ay);
        return fd;
    });
});

// Mid Marks Submit
$('#unifiedMidmarksForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    const files = uploadQueues.midmarks;

    const meta = {
        academic_year: $('#uMidAcademicYear').val() || '',
        semester_info: $('#uMidSemesterInfo').val() || '',
        section: $('#uMidSection').val() || '',
        college_name: $('#uMidCollegeName').val() || ''
    };

    processBatchUpload('midmarks', files, (file) => {
        const fd = new FormData();
        fd.append('midmarksFile', file);
        fd.append('academic_year', meta.academic_year);
        fd.append('semester_info', meta.semester_info);
        fd.append('section', meta.section);
        fd.append('college_name', meta.college_name);
        return fd;
    });
});

// Smart Score Submit
$('#unifiedSmartscoreForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    const files = uploadQueues.smartscore;
    if (!files.length) {
        alert('Please select a Smart Score Excel file.');
        return;
    }

    const fd = new FormData();
    fd.append('smartscoreFile', files[0]);

    const statusBox = $('#uSmartscoreStatusBox');
    const submitBtn = $('#uSmartscoreSubmitBtn');

    submitBtn.prop('disabled', true);
    statusBox.html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Uploading and calculating Smart Scores...</div>');

    $.ajax({
        url: 'upload_smartscore.php',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(response) {
            const res = typeof response === 'string' ? JSON.parse(response) : response;
            if (res.success) {
                statusBox.html('<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + escapeHtml(res.message) + '</div>');
                clearFileQueue('smartscore');
            } else {
                statusBox.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + escapeHtml(res.message) + '</div>');
            }
        },
        error: function() {
            statusBox.html('<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Server error during Smart Score upload.</div>');
        },
        complete: function() {
            submitBtn.prop('disabled', false);
        }
    });
});

// Course Codes Submit
$('#unifiedCourseCodesForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    const files = uploadQueues.courseCodes;
    if (!files.length) {
        alert('Please select a Course Codes Excel file.');
        return;
    }

    const fd = new FormData();
    fd.append('courseCodesFile', files[0]);

    const statusBox = $('#uCourseCodesStatusBox');
    const submitBtn = $('#uCourseCodesSubmitBtn');

    submitBtn.prop('disabled', true);
    statusBox.html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Uploading curriculum course codes...</div>');

    $.ajax({
        url: 'upload_course_codes.php',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(response) {
            const res = typeof response === 'string' ? JSON.parse(response) : response;
            if (res.success) {
                statusBox.html('<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + escapeHtml(res.message) + '</div>');
                clearFileQueue('courseCodes');
            } else {
                statusBox.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + escapeHtml(res.message) + '</div>');
            }
        },
        error: function() {
            statusBox.html('<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Server error during Course Codes upload.</div>');
        },
        complete: function() {
            submitBtn.prop('disabled', false);
        }
    });
});

// Manage Records & Deletion Modals
let pendingDeleteTarget = null;

function promptDeleteUpload(type, id, fileName) {
    pendingDeleteTarget = { type, id, fileName };
    $('#deleteTargetFileName').text(fileName || `ID: ${id}`);
    const modalEl = document.getElementById('deleteUploadModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

$('#confirmDeleteUploadBtn').off('click').on('click', function() {
    if (!pendingDeleteTarget) return;

    const { type, id, fileName } = pendingDeleteTarget;
    const isAtt = type === 'attendance';
    const url = isAtt ? 'attendance_manage.php?action=delete' : 'midmarks_manage.php?action=delete';
    const statusEl = isAtt ? $('#uAttendanceManageStatus') : $('#uMidmarksManageStatus');

    statusEl.removeClass('d-none alert-success alert-danger').addClass('alert-info').text('Deleting records...').show();
    const modalEl = document.getElementById('deleteUploadModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    }

    $.ajax({
        url: url,
        method: 'POST',
        data: { upload_id: id },
        dataType: 'json',
        success: function(res) {
            if (res && res.success) {
                statusEl.removeClass('alert-info').addClass('alert-success').text(res.message || 'Successfully deleted.');
                if (isAtt) loadUnifiedAttendanceUploads();
                else loadUnifiedMidmarksUploads();
            } else {
                statusEl.removeClass('alert-info').addClass('alert-danger').text((res && res.message) ? res.message : 'Delete failed.');
            }
        },
        error: function() {
            statusEl.removeClass('alert-info').addClass('alert-danger').text('Server error during record deletion.');
        }
    });
});

// Load Attendance Uploads Table
let cachedAttendanceRows = [];

function loadUnifiedAttendanceUploads() {
    const body = $('#uAttendanceTableBody');
    const refreshIcon = $('#refreshAttIcon');
    if (refreshIcon.length) refreshIcon.addClass('fa-spin');

    $.ajax({
        url: 'attendance_manage.php',
        method: 'GET',
        data: { action: 'list' },
        dataType: 'json',
        success: function(res) {
            if (refreshIcon.length) refreshIcon.removeClass('fa-spin');
            if (!res || !res.success) {
                body.html('<tr><td colspan="8" class="text-danger text-center py-4">Failed to load attendance records.</td></tr>');
                return;
            }
            cachedAttendanceRows = Array.isArray(res.data) ? res.data : [];
            $('#statAttendanceCount').text(cachedAttendanceRows.length);
            renderAttendanceRows(cachedAttendanceRows);
        },
        error: function() {
            if (refreshIcon.length) refreshIcon.removeClass('fa-spin');
            body.html('<tr><td colspan="8" class="text-danger text-center py-4">Error connecting to database.</td></tr>');
        }
    });
}

function renderAttendanceRows(rows) {
    const body = $('#uAttendanceTableBody');
    if (!body.length) return;

    if (!rows.length) {
        body.html(`
            <tr>
                <td colspan="8" class="empty-state-box">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <div class="fw-bold">No attendance records found</div>
                    <div class="small">Upload an attendance CSV or Excel sheet to get started.</div>
                </td>
            </tr>
        `);
        return;
    }

    const html = rows.map(r => {
        const id = r.id;
        const fileName = r.file_name || '-';
        const session = r.session_name || '-';
        const ay = r.academic_year || '-';
        const sem = r.semester || '-';
        const branch = r.branch || '-';
        const sec = r.section || '-';
        const date = r.created_at || r.uploaded_at || '-';
        const cleanName = String(fileName).replace(/'/g, "\\'");

        return `
            <tr>
                <td class="fw-bold text-dark">${escapeHtml(fileName)}</td>
                <td class="font-mono text-muted small">${escapeHtml(session)}</td>
                <td><span class="badge bg-light text-dark border">${escapeHtml(ay)}</span></td>
                <td><span class="sem-badge">${escapeHtml(sem)}</span></td>
                <td><span class="branch-badge">${escapeHtml(branch)}</span></td>
                <td><span class="badge bg-light text-secondary">${escapeHtml(sec)}</span></td>
                <td class="text-muted small">${escapeHtml(date)}</td>
                <td class="text-end">
                    <button class="btn-delete-row" onclick="promptDeleteUpload('attendance', ${id}, '${cleanName}')">
                        <i class="fas fa-trash-can me-1"></i>Delete
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    body.html(html);
}

// Live filter attendance
$('#searchAttendanceUploads').off('input').on('input', function() {
    const q = $(this).val().toLowerCase().trim();
    if (!q) {
        renderAttendanceRows(cachedAttendanceRows);
        return;
    }
    const filtered = cachedAttendanceRows.filter(r => {
        return (r.file_name || '').toLowerCase().includes(q) ||
               (r.session_name || '').toLowerCase().includes(q) ||
               (r.academic_year || '').toLowerCase().includes(q) ||
               (r.semester || '').toLowerCase().includes(q) ||
               (r.branch || '').toLowerCase().includes(q) ||
               (r.section || '').toLowerCase().includes(q);
    });
    renderAttendanceRows(filtered);
});

// Load Mid Marks Table
let cachedMidmarksRows = [];

function loadUnifiedMidmarksUploads() {
    const body = $('#uMidmarksTableBody');
    const refreshIcon = $('#refreshMidIcon');
    if (refreshIcon.length) refreshIcon.addClass('fa-spin');

    $.ajax({
        url: 'midmarks_manage.php',
        method: 'GET',
        data: { action: 'list' },
        dataType: 'json',
        success: function(res) {
            if (refreshIcon.length) refreshIcon.removeClass('fa-spin');
            if (!res || !res.success) {
                body.html('<tr><td colspan="6" class="text-danger text-center py-4">Failed to load midmarks records.</td></tr>');
                return;
            }
            cachedMidmarksRows = Array.isArray(res.data) ? res.data : [];
            $('#statMidmarksCount').text(cachedMidmarksRows.length);
            renderMidmarksRows(cachedMidmarksRows);
        },
        error: function() {
            if (refreshIcon.length) refreshIcon.removeClass('fa-spin');
            body.html('<tr><td colspan="6" class="text-danger text-center py-4">Error connecting to database.</td></tr>');
        }
    });
}

function renderMidmarksRows(rows) {
    const body = $('#uMidmarksTableBody');
    if (!body.length) return;

    if (!rows.length) {
        body.html(`
            <tr>
                <td colspan="6" class="empty-state-box">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <div class="fw-bold">No mid marks records found</div>
                    <div class="small">Upload a mid-term examination CSV/Excel sheet to get started.</div>
                </td>
            </tr>
        `);
        return;
    }

    const html = rows.map(r => {
        const id = r.id;
        const fileName = r.file_name || '-';
        const semInfo = r.semester_info || '-';
        const ay = r.academic_year || '-';
        const sec = r.section || '-';
        const date = r.created_at || '-';
        const cleanName = String(fileName).replace(/'/g, "\\'");

        return `
            <tr>
                <td class="fw-bold text-dark">${escapeHtml(fileName)}</td>
                <td><span class="sem-badge">${escapeHtml(semInfo)}</span></td>
                <td><span class="badge bg-light text-dark border">${escapeHtml(ay)}</span></td>
                <td><span class="branch-badge">${escapeHtml(sec)}</span></td>
                <td class="text-muted small">${escapeHtml(date)}</td>
                <td class="text-end">
                    <button class="btn-delete-row" onclick="promptDeleteUpload('midmarks', ${id}, '${cleanName}')">
                        <i class="fas fa-trash-can me-1"></i>Delete
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    body.html(html);
}

// Live filter midmarks
$('#searchMidmarksUploads').off('input').on('input', function() {
    const q = $(this).val().toLowerCase().trim();
    if (!q) {
        renderMidmarksRows(cachedMidmarksRows);
        return;
    }
    const filtered = cachedMidmarksRows.filter(r => {
        return (r.file_name || '').toLowerCase().includes(q) ||
               (r.semester_info || '').toLowerCase().includes(q) ||
               (r.academic_year || '').toLowerCase().includes(q) ||
               (r.section || '').toLowerCase().includes(q);
    });
    renderMidmarksRows(filtered);
});

function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
    });
}

// Map unified and legacy functions globally
window.loadUnifiedAttendanceUploads = loadUnifiedAttendanceUploads;
window.loadUnifiedMidmarksUploads = loadUnifiedMidmarksUploads;
window.loadAttendanceUploads = loadUnifiedAttendanceUploads;
window.loadMidmarksUploads = loadUnifiedMidmarksUploads;

// Initialize when document is ready
$(function() {
    initDropZone('uAttendanceDrop', 'uAttendanceFileInput', 'attendance', true);
    initDropZone('uMidmarksDrop', 'uMidmarksFileInput', 'midmarks', true);
    initDropZone('uSmartscoreDrop', 'uSmartscoreFileInput', 'smartscore', false);
    initDropZone('uCourseCodesDrop', 'uCourseCodesFileInput', 'courseCodes', false);

    loadUnifiedAttendanceUploads();
    loadUnifiedMidmarksUploads();
});
</script>

<?php if ($isStandalone): ?>
</div>
</body>
</html>
<?php endif; ?>
