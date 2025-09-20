<?php
require_once 'api/config.php';

// Get session ID from URL
$sessionId = $_GET['session_id'] ?? '';
$selectedImages = $_GET['selected'] ?? '';

if (empty($sessionId) || empty($selectedImages)) {
    http_response_code(400);
    echo 'Invalid checkout session';
    exit;
}

// Load session data
$sessionFile = TEMP_DIR . 'checkout_' . $sessionId . '.json';
if (!file_exists($sessionFile)) {
    http_response_code(404);
    echo 'Checkout session not found';
    exit;
}

$sessionData = json_decode(file_get_contents($sessionFile), true);
if (!$sessionData) {
    http_response_code(500);
    echo 'Invalid session data';
    exit;
}

// In production, you would verify the payment with Stripe here
// For now, we'll assume payment was successful

$selectedIndices = explode(',', $selectedImages);
$enhancedImages = $sessionData['enhanced_images'];
$downloadFiles = [];

// Prepare download files
foreach ($selectedIndices as $index) {
    if (isset($enhancedImages[$index])) {
        $imageData = $enhancedImages[$index];
        $enhancedPath = ENHANCED_DIR . $imageData['unique_id'] . '_enhanced.jpg';
        
        if (file_exists($enhancedPath)) {
            $downloadFiles[] = [
                'name' => 'enhanced_' . $imageData['original_name'],
                'path' => $enhancedPath
            ];
        }
    }
}

// Clean up session file
unlink($sessionFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - FotoFix</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .download-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .download-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s ease;
        }
        .download-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .download-all-btn {
            background: #28a745;
            font-size: 1.2rem;
            padding: 15px 40px;
        }
        .download-all-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1>Payment Successful!</h1>
        <p>Thank you for your purchase. Your enhanced images are ready for download.</p>
        
        <div class="download-section">
            <h3>Download Your Enhanced Images</h3>
            <p>You can download individual images or all images at once.</p>
            
            <div class="download-buttons">
                <?php foreach ($downloadFiles as $file): ?>
                    <button class="download-btn" onclick="downloadFile('<?php echo $file['name']; ?>', '<?php echo $file['path']; ?>')">
                        <i class="fas fa-download"></i> <?php echo htmlspecialchars($file['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 30px;">
                <button class="download-btn download-all-btn" onclick="downloadAllFiles()">
                    <i class="fas fa-download"></i> Download All Images
                </button>
            </div>
        </div>
        
        <div style="margin-top: 40px;">
            <a href="index.html" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        const downloadFiles = <?php echo json_encode($downloadFiles); ?>;
        
        function downloadFile(filename, filepath) {
            // Create a temporary link to download the file
            const link = document.createElement('a');
            link.href = 'api/download_file.php?file=' + encodeURIComponent(filepath) + '&name=' + encodeURIComponent(filename);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function downloadAllFiles() {
            // Download all files one by one
            downloadFiles.forEach((file, index) => {
                setTimeout(() => {
                    downloadFile(file.name, file.path);
                }, index * 500); // Small delay between downloads
            });
        }
    </script>
</body>
</html>
