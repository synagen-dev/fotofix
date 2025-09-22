<?php
/**
 * Test script to verify Gemini API fix
 */

require_once 'api/config.php';
require_once 'api/google_ai_integration.php';

echo "<h1>Gemini API Fix Test</h1>\n";

// Test 1: Check if API key is available
echo "<h2>1. API Key Check</h2>\n";
if (defined('GOOGLE_AI_API_KEY') && !empty(GOOGLE_AI_API_KEY)) {
    echo "✅ API Key is available\n";
} else {
    echo "❌ API Key is not available\n";
    exit;
}

// Test 2: Test connection with small payload
echo "<h2>2. Connection Test</h2>\n";
$googleAI = new GoogleAIIntegration(GOOGLE_AI_API_KEY, GOOGLE_AI_MODEL);
if ($googleAI->testConnection()) {
    echo "✅ Connection test successful\n";
} else {
    echo "❌ Connection test failed\n";
}

// Test 3: Test image processing with small image
echo "<h2>3. Image Processing Test</h2>\n";

// Create a small test image
$testImagePath = sys_get_temp_dir() . '/test_small.jpg';
$testImage = imagecreate(200, 200);
$bgColor = imagecolorallocate($testImage, 255, 255, 255);
imagefill($testImage, 0, 0, $bgColor);
imagejpeg($testImage, $testImagePath, 90);
imagedestroy($testImage);

if (file_exists($testImagePath)) {
    echo "✅ Test image created\n";
    
    $outputPath = sys_get_temp_dir() . '/test_enhanced.jpg';
    $instructions = "Make this image look more modern and attractive for real estate marketing.";
    
    echo "Testing image enhancement...\n";
    $result = $googleAI->enhanceImage($testImagePath, $instructions, $outputPath);
    
    if ($result) {
        echo "✅ Image enhancement successful\n";
        if (file_exists($outputPath)) {
            echo "✅ Enhanced image saved\n";
            unlink($outputPath);
        }
    } else {
        echo "❌ Image enhancement failed\n";
    }
    
    // Clean up
    unlink($testImagePath);
} else {
    echo "❌ Could not create test image\n";
}

echo "<h2>Test Complete</h2>\n";
echo "Check the error logs for detailed information.\n";
?>


