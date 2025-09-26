<?php
/**
 * Payment Verification Test Script
 * Test the payment verification functionality
 */

require_once 'api/config.php';

echo "<h1>Payment Verification Test</h1>\n";

// Test with the session ID from your example
$testSessionId = 'cs_test_a1ATwEwULmmcg0DrpVTW2R30dQIYGTevpQlYLfLGX4i3CgQHn0HaFJHKp6';

echo "<h2>Testing Session: $testSessionId</h2>\n";

// Check if we have session data
$sessionDataFile = TEMP_DIR . 'checkout_' . $testSessionId . '.json';
$paidImagesFile = TEMP_DIR . 'paid_' . $testSessionId . '.json';

echo "<h3>File Status:</h3>\n";
echo "Session data file: " . (file_exists($sessionDataFile) ? "✅ Exists" : "❌ Missing") . "<br>\n";
echo "Paid images file: " . (file_exists($paidImagesFile) ? "✅ Exists" : "❌ Missing") . "<br>\n";

if (file_exists($sessionDataFile)) {
    $sessionData = json_decode(file_get_contents($sessionDataFile), true);
    echo "<h3>Session Data:</h3>\n";
    echo "<pre>" . print_r($sessionData, true) . "</pre>\n";
}

if (file_exists($paidImagesFile)) {
    $paidImages = json_decode(file_get_contents($paidImagesFile), true);
    echo "<h3>Paid Images:</h3>\n";
    echo "<pre>" . print_r($paidImages, true) . "</pre>\n";
}

// Test Stripe API verification
echo "<h3>Stripe API Verification:</h3>\n";
try {
    require_once $autoload;
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $session = \Stripe\Checkout\Session::retrieve($testSessionId);
    
    echo "Session ID: " . $session->id . "<br>\n";
    echo "Payment Status: " . $session->payment_status . "<br>\n";
    echo "Amount Total: " . ($session->amount_total / 100) . "<br>\n";
    echo "Currency: " . $session->currency . "<br>\n";
    
    if ($session->payment_status === 'paid') {
        echo "✅ Payment is confirmed as paid<br>\n";
    } else {
        echo "❌ Payment status: " . $session->payment_status . "<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

// Test verification endpoint
echo "<h3>Verification Endpoint Test:</h3>\n";
$postData = json_encode(['session_id' => $testSessionId]);
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$result = file_get_contents('http://localhost/api/verify_payment.php', false, $context);
if ($result) {
    $response = json_decode($result, true);
    echo "<pre>" . print_r($response, true) . "</pre>\n";
} else {
    echo "❌ Could not reach verification endpoint<br>\n";
}
?>
