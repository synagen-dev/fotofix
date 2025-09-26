<?php
require_once 'api/config.php';

// Include Stripe PHP library
require_once $autoload;

// Get session ID from URL
$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    http_response_code(400);
    echo 'Invalid checkout session';
    exit;
}

// Check if payment was completed (webhook should have processed this)
$paidImagesFile = TEMP_DIR . 'paid_' . $sessionId . '.json';
$waiting = false;
$downloadFiles = [];

if (file_exists($paidImagesFile)) {
    // Webhook already processed the payment
    $paidImages = json_decode(file_get_contents($paidImagesFile), true);
    $waiting = false;
} else {
    // Webhook hasn't processed yet, verify payment directly with Stripe
    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        
        if ($session->payment_status === 'paid') {
            // Payment is confirmed, process it manually
            processPaymentManually($sessionId, $session);
            $paidImages = json_decode(file_get_contents($paidImagesFile), true);
            $waiting = false;
        } else {
            // Payment not yet completed
            $waiting = true;
        }
    } catch (Exception $e) {
        error_log('Error verifying payment: ' . $e->getMessage());
        $waiting = true;
    }
}

// Prepare download files
if (!$waiting && isset($paidImages)) {
    foreach ($paidImages as $imageData) {
        $enhancedPath = ENHANCED_DIR . $imageData['unique_id'] . '_enhanced.png';
        
        if (file_exists($enhancedPath)) {
            $downloadFiles[] = [
                'name' => 'enhanced_' . $imageData['original_name'],
                'path' => $enhancedPath,
                'unique_id' => $imageData['unique_id']
            ];
        }
    }
}

function processPaymentManually($sessionId, $session) {
    // Get session data from our stored file
    $sessionDataFile = TEMP_DIR . 'checkout_' . $sessionId . '.json';
    
    if (!file_exists($sessionDataFile)) {
        error_log('Session data file not found: ' . $sessionDataFile);
        return;
    }
    
    $sessionData = json_decode(file_get_contents($sessionDataFile), true);
    
    if (!$sessionData) {
        error_log('Invalid session data for: ' . $sessionId);
        return;
    }
    
    // Mark images as paid and available for download
    $paidImages = [];
    foreach ($sessionData['selected_images'] as $index) {
        if (isset($sessionData['enhanced_images'][$index])) {
            $imageData = $sessionData['enhanced_images'][$index];
            $imageData['paid'] = true;
            $imageData['payment_session_id'] = $sessionId;
            $imageData['payment_date'] = time();
            $paidImages[] = $imageData;
        }
    }
    
    // Store paid images data
    $paidImagesFile = TEMP_DIR . 'paid_' . $sessionId . '.json';
    file_put_contents($paidImagesFile, json_encode($paidImages));
    
    // Clean up the checkout session file
    unlink($sessionDataFile);
}
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
        <?php if ($waiting): ?>
            <i class="fas fa-spinner fa-spin success-icon" style="color: #007bff;"></i>
            <h1>Processing Payment...</h1>
            <p>Please wait while we process your payment. This page will automatically refresh.</p>
            <div id="countdown" style="margin-top: 20px; font-size: 1.2rem; color: #666;"></div>
        <?php else: ?>
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Payment Successful!</h1>
            <p>Thank you for your purchase. Your enhanced images are ready for download.</p>
            
            <div class="download-section">
                <h3>Download Your Enhanced Images</h3>
                <p>You can download individual images or all images at once.</p>
                
                <div class="download-buttons">
                    <?php foreach ($downloadFiles as $file): ?>
                        <button class="download-btn" onclick="downloadFile('<?php echo $file['name']; ?>', '<?php echo $file['unique_id']; ?>')">
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
        <?php endif; ?>
        
        <div style="margin-top: 40px;">
            <a href="index.html" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        const downloadFiles = <?php echo json_encode($downloadFiles); ?>;
        const waiting = <?php echo $waiting ? 'true' : 'false'; ?>;
        const sessionId = '<?php echo $sessionId; ?>';
        
        <?php if ($waiting): ?>
        // Auto-refresh page every 3 seconds while waiting for payment processing
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            countdownElement.textContent = `Refreshing in ${countdown} seconds...`;
            countdown--;
            
            if (countdown < 0) {
                // Try to verify payment before refreshing
                verifyPayment();
            } else {
                setTimeout(updateCountdown, 1000);
            }
        }
        
        function verifyPayment() {
            fetch('api/verify_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.paid) {
                    // Payment confirmed, reload page to show downloads
                    window.location.reload();
                } else {
                    // Still waiting, continue countdown
                    countdown = 3;
                    updateCountdown();
                }
            })
            .catch(error => {
                console.error('Error verifying payment:', error);
                // Continue with normal refresh
                window.location.reload();
            });
        }
        
        updateCountdown();
        <?php else: ?>
        function downloadFile(filename, uniqueId) {
            // Create a temporary link to download the file
            const link = document.createElement('a');
            link.href = 'api/download_file.php?type=enhanced&id=' + encodeURIComponent(uniqueId) + '&name=' + encodeURIComponent(filename);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function downloadAllFiles() {
            // Download all files one by one
            downloadFiles.forEach((file, index) => {
                setTimeout(() => {
                    downloadFile(file.name, file.unique_id);
                }, index * 500); // Small delay between downloads
            });
        }
        
        // Auto-download all files when page loads (payment successful)
        if (downloadFiles.length > 0) {
            // Show a message that downloads are starting
            const downloadMessage = document.createElement('div');
            downloadMessage.style.cssText = 'background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px; border: 1px solid #c3e6cb;';
            downloadMessage.innerHTML = '<i class="fas fa-download"></i> Downloads starting automatically...';
            document.querySelector('.success-container').insertBefore(downloadMessage, document.querySelector('.download-section'));
            
            // Start downloads after a short delay
            setTimeout(() => {
                downloadAllFiles();
            }, 1000);
        }
        <?php endif; ?>
    </script>
</body>
</html>
