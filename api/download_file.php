<?php
require_once 'config.php';

// Get parameters
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$name = $_GET['name'] ?? '';

if (empty($type) || empty($id)) {
    http_response_code(400);
    echo 'Invalid download request';
    exit;
}

// Determine file path based on type
switch ($type) {
    case 'enhanced':
        $filePath = ENHANCED_DIR . $id . '_enhanced.png';
        break;
    case 'preview':
        $filePath = PREVIEW_DIR . $id . '_preview.png';
        break;
    default:
        http_response_code(400);
        echo 'Invalid file type';
        exit;
}

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// For enhanced files, check if payment was completed
if ($type === 'enhanced') {
    // Check if this is a paid download by looking for paid session files
    $paidFiles = glob(TEMP_DIR . 'paid_*.json');
    $isPaid = false;
    
    foreach ($paidFiles as $paidFile) {
        $paidData = json_decode(file_get_contents($paidFile), true);
        if ($paidData) {
            foreach ($paidData as $imageData) {
                if ($imageData['unique_id'] === $id && isset($imageData['paid']) && $imageData['paid']) {
                    $isPaid = true;
                    break 2;
                }
            }
        }
    }
    
    if (!$isPaid) {
        http_response_code(403);
        echo 'Payment required to download enhanced images';
        exit;
    }
}

// Set download headers
$filename = $name ?: basename($filePath);
$mimeType = mime_content_type($filePath);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output file
readfile($filePath);
exit;
?>