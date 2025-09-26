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

    // Create actual Stripe checkout session
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Store only essential data in metadata (under 500 chars)
    $metadata = [
        'selected_count' => count($selectedImages),
        'total_amount' => $totalAmount,
        'session_type' => 'fotofix_checkout'
    ];
    
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => 'https://' . $base_domain . '/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://' . $base_domain . '/index.html',
        'metadata' => $metadata
    ]);
    
    // Store session data temporarily for webhook processing
    $sessionData = [
        'session_id' => $session->id,
        'selected_images' => $selectedImages,
        'enhanced_images' => $enhancedImages,
        'total_amount' => $totalAmount,
        'session_type' => 'fotofix_checkout',
        'created_at' => time()
    ];
    
    file_put_contents(TEMP_DIR . 'checkout_' . $session->id . '.json', json_encode($sessionData));

    echo json_encode([
        'success' => true,
        'checkout_url' => $session->url,
        'session_id' => $session->id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
