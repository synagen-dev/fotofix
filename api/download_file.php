<?php
require_once 'config.php';

// Get file parameters
$filepath = $_GET['file'] ?? '';
$filename = $_GET['name'] ?? '';

if (empty($filepath) || empty($filename)) {
    http_response_code(400);
    exit('Invalid parameters');
}

// Security: validate file path
$realPath = realpath($filepath);
$allowedDir = realpath(ENHANCED_DIR);

if (!$realPath || strpos($realPath, $allowedDir) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

// Check if file exists
if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found');
}

// Sanitize filename for download
$safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file
readfile($filepath);
?>
