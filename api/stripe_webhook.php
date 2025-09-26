<?php
require_once 'config.php';

// Include Stripe PHP library
require_once $autoload;

header('Content-Type: application/json');

// Get the webhook payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$endpoint_secret = STRIPE_SIGNING_SECRET;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        handleCheckoutCompleted($session);
        break;
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;
        handlePaymentSucceeded($paymentIntent);
        break;
    default:
        // Unexpected event type
        error_log('Received unknown event type: ' . $event->type);
}

http_response_code(200);

function handleCheckoutCompleted($session) {
    GLOBAL $debugMode;
    GLOBAL $glog;
    
    if ($debugMode && $glog) {
        fwrite($glog, __FILE__ . ", line " . __LINE__ . ", handleCheckoutCompleted: " . $session->id . "\r\n");
    }
    
    try {
        // Get session data from our stored file
        $sessionDataFile = TEMP_DIR . 'checkout_' . $session->id . '.json';
        
        if (!file_exists($sessionDataFile)) {
            error_log('Session data file not found: ' . $sessionDataFile);
            return;
        }
        
        $sessionData = json_decode(file_get_contents($sessionDataFile), true);
        
        if (!$sessionData) {
            error_log('Invalid session data for: ' . $session->id);
            return;
        }
        
        // Verify this is a FotoFix checkout session
        if (!isset($sessionData['session_type']) || $sessionData['session_type'] !== 'fotofix_checkout') {
            error_log('Not a FotoFix checkout session: ' . $session->id);
            return;
        }
        
        // Mark images as paid and available for download
        $paidImages = [];
        foreach ($sessionData['selected_images'] as $index) {
            if (isset($sessionData['enhanced_images'][$index])) {
                $imageData = $sessionData['enhanced_images'][$index];
                $imageData['paid'] = true;
                $imageData['payment_session_id'] = $session->id;
                $imageData['payment_date'] = time();
                $paidImages[] = $imageData;
            }
        }
        
        // Store paid images data
        $paidImagesFile = TEMP_DIR . 'paid_' . $session->id . '.json';
        file_put_contents($paidImagesFile, json_encode($paidImages));
        
        // Clean up the checkout session file
        unlink($sessionDataFile);
        
        if ($debugMode && $glog) {
            fwrite($glog, __FILE__ . ", line " . __LINE__ . ", Payment completed for session: " . $session->id . "\r\n");
        }
        
    } catch (Exception $e) {
        error_log('Error handling checkout completed: ' . $e->getMessage());
    }
}

function handlePaymentSucceeded($paymentIntent) {
    GLOBAL $debugMode;
    GLOBAL $glog;
    
    if ($debugMode && $glog) {
        fwrite($glog, __FILE__ . ", line " . __LINE__ . ", handlePaymentSucceeded: " . $paymentIntent->id . "\r\n");
    }
    
    // Additional payment processing logic can be added here
    // For now, the checkout.session.completed event is sufficient
}
?>
