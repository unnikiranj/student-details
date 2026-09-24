<?php
/**
 * generate_pdf.php
 * Forwarder to comprehensive PDF generator in api/download_pdf.php
 */
$_GET['roll'] = trim((string)($_POST['roll_no'] ?? $_GET['roll_no'] ?? $_GET['roll'] ?? ''));
require_once __DIR__ . '/api/download_pdf.php';
