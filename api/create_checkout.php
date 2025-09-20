<?php
require_once 'config.php';

// Include Stripe PHP library (you'll need to install this via Composer)
// require_once 'vendor/autoload.php';

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
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['selected_images']) || !isset($input['enhanced_images'])) {
        throw new Exception('Invalid request data');
    }

    $selectedImages = $input['selected_images'];
    $enhancedImages = $input['enhanced_images'];
    
    if (empty($selectedImages)) {
        throw new Exception('No images selected for purchase');
    }

    // Calculate total amount
    $totalAmount = count($selectedImages) * PRICE_PER_IMAGE;
    
    // Create line items for Stripe
    $lineItems = [];
    foreach ($selectedImages as $index) {
        if (isset($enhancedImages[$index])) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Enhanced Real Estate Photo - ' . $enhancedImages[$index]['original_name'],
                        'description' => 'AI-enhanced real estate photograph'
                    ],
                    'unit_amount' => PRICE_PER_IMAGE
                ],
                'quantity' => 1
            ];
        }
    }

    // For now, we'll simulate Stripe checkout creation
    // In production, you would use the actual Stripe API
    $checkoutSessionId = 'cs_test_' . uniqid();
    $checkoutUrl = 'checkout_success.php?session_id=' . $checkoutSessionId . '&selected=' . implode(',', $selectedImages);
    
    // Store session data temporarily (in production, use a database or Redis)
    $sessionData = [
        'session_id' => $checkoutSessionId,
        'selected_images' => $selectedImages,
        'enhanced_images' => $enhancedImages,
        'total_amount' => $totalAmount,
        'created_at' => time()
    ];
    
    file_put_contents(TEMP_DIR . 'checkout_' . $checkoutSessionId . '.json', json_encode($sessionData));

    echo json_encode([
        'success' => true,
        'checkout_url' => $checkoutUrl,
        'session_id' => $checkoutSessionId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/*
// Actual Stripe implementation would look like this:
function createStripeCheckout($lineItems, $selectedImages, $enhancedImages) {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => 'https://yourdomain.com/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://yourdomain.com/index.html',
        'metadata' => [
            'selected_images' => implode(',', $selectedImages),
            'enhanced_images' => json_encode($enhancedImages)
        ]
    ]);
    
    return $session;
}
*/
?>
