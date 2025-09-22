<?php
require_once 'config.php';
require_once 'enhancement_instructions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $options = [
        'exterior' => EnhancementInstructions::getOptions('exterior'),
        'interior' => EnhancementInstructions::getOptions('interior')
    ];

    echo json_encode([
        'success' => true,
        'options' => $options
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
