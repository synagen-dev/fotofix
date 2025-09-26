<?php
require_once 'config.php';

// Get parameters
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$mimeType = urldecode($_GET['mimeType']) ?? '';

if (empty($type) || empty($id)) {
    http_response_code(400);
    exit('Invalid parameters');
}

// Validate type
if (!in_array($type, ['preview', 'enhanced'])) {
    http_response_code(400);
    exit('Invalid image type');
}

// Security: validate ID format
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $id)) {
    http_response_code(400);
    exit('Invalid image ID');
}

// Determine file path
$filename = '';
switch ($type) {
    case 'preview':
        if ($mimeType==='image/png') $filename = PREVIEW_DIR . $id . '_preview.png';
		else $filename = PREVIEW_DIR . $id . '_preview.jpg';
        break;
    case 'enhanced':
        if ($mimeType==='image/png') $filename = ENHANCED_DIR . $id . '_enhanced.png';
		else  $filename = ENHANCED_DIR . $id . '_enhanced.jpg';
        break;
}

// Check if file exists
if (!file_exists($filename)) {
    http_response_code(404);
    exit('Image not found');
}

// Set appropriate headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filename));
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Output file
readfile($filename);
?>
