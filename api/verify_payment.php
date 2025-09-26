<?php
require_once 'config.php';

// Include Stripe PHP library
require_once $autoload;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['session_id'])) {
        throw new Exception('Session ID required');
    }
    
    $sessionId = $input['session_id'];
    
    // Check if payment was already processed
    $paidImagesFile = TEMP_DIR . 'paid_' . $sessionId . '.json';
    
    if (file_exists($paidImagesFile)) {
        $paidImages = json_decode(file_get_contents($paidImagesFile), true);
        echo json_encode([
            'success' => true,
            'paid' => true,
            'images' => $paidImages
        ]);
        exit;
    }
    
    // Verify payment with Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    
    if ($session->payment_status === 'paid') {
        // Process payment manually
        $sessionDataFile = TEMP_DIR . 'checkout_' . $sessionId . '.json';
        
        if (file_exists($sessionDataFile)) {
            $sessionData = json_decode(file_get_contents($sessionDataFile), true);
            
            if ($sessionData) {
                // Verify this is a FotoFix checkout session
                if (!isset($sessionData['session_type']) || $sessionData['session_type'] !== 'fotofix_checkout') {
                    throw new Exception('Not a FotoFix checkout session');
                }
                
                // Mark images as paid
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
                file_put_contents($paidImagesFile, json_encode($paidImages));
                
                // Clean up checkout session file
                unlink($sessionDataFile);
                
                echo json_encode([
                    'success' => true,
                    'paid' => true,
                    'images' => $paidImages
                ]);
            } else {
                throw new Exception('Invalid session data');
            }
        } else {
            throw new Exception('Session data not found');
        }
    } else {
        echo json_encode([
            'success' => true,
            'paid' => false,
            'payment_status' => $session->payment_status
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
