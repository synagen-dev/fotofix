<?php
/**
 * FotoFix Setup Test Script
 * Run this to verify your installation is working correctly
 */

require_once 'api/config.php';
require_once 'api/google_ai_integration.php';

echo "<h1>FotoFix Setup Test</h1>\n";

// Test 1: Check PHP version
echo "<h2>1. PHP Version Check</h2>\n";
$phpVersion = phpversion();
echo "PHP Version: $phpVersion\n";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "✅ PHP version is compatible\n";
} else {
    echo "❌ PHP version is too old. Required: 7.4+\n";
}

// Test 2: Check required extensions
echo "<h2>2. Required Extensions Check</h2>\n";
$requiredExtensions = ['gd', 'curl', 'json', 'fileinfo'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension is loaded\n";
    } else {
        echo "❌ $ext extension is missing\n";
    }
}

// Test 3: Check directory permissions
echo "<h2>3. Directory Permissions Check</h2>\n";
$directories = [IMAGES_DIR, TEMP_DIR, ENHANCED_DIR, PREVIEW_DIR];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ $dir is writable\n";
        } else {
            echo "❌ $dir is not writable\n";
        }
    } else {
        echo "❌ $dir does not exist\n";
    }
}

// Test 4: Check configuration
echo "<h2>4. Configuration Check</h2>\n";
if (defined('GOOGLE_AI_API_KEY') && GOOGLE_AI_API_KEY !== 'YOUR_GOOGLE_AI_API_KEY') {
    echo "✅ Google AI API key is configured\n";
} else {
    echo "⚠️ Google AI API key needs to be configured\n";
}

if (defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY !== 'sk_test_YOUR_STRIPE_SECRET_KEY') {
    echo "✅ Stripe secret key is configured\n";
} else {
    echo "⚠️ Stripe secret key needs to be configured\n";
}

// Test 5: Test Google AI connection (if API key is configured)
echo "<h2>5. Google AI Connection Test</h2>\n";
echo "API Key: " . (defined('GOOGLE_AI_API_KEY') ? (strlen(GOOGLE_AI_API_KEY) > 10 ? substr(GOOGLE_AI_API_KEY, 0, 10) . '...' : GOOGLE_AI_API_KEY) : 'Not defined') . "<br>";
echo "Model: " . (defined('GOOGLE_AI_MODEL') ? GOOGLE_AI_MODEL : 'Not defined') . "<br>";

if (defined('GOOGLE_AI_API_KEY') && !empty(GOOGLE_AI_API_KEY)) {
    $googleAI = new GoogleAIIntegration(GOOGLE_AI_API_KEY, GOOGLE_AI_MODEL);
    if ($googleAI->testConnection() ===true) {
        echo "✅ Google AI connection successful\n";
    } else {
        echo "❌ Google AI connection failed\n";
        echo "Check the error logs for more details: /mnt/docker/apps/logs/fotofix/error.log<br>";
    }
} else {
    echo "⚠️ Skipping Google AI test (API key not configured or empty)\n";
}

// Test 6: Test image processing
echo "<h2>6. Image Processing Test</h2>\n";
$testImagePath = __DIR__ . '/test_image.jpg';
$testOutputPath = TEMP_DIR . 'test_enhanced.jpg';

// Create a simple test image
$testImage = imagecreate(100, 100);
$bgColor = imagecolorallocate($testImage, 255, 255, 255);
imagefill($testImage, 0, 0, $bgColor);
imagejpeg($testImage, $testImagePath, 90);
imagedestroy($testImage);

if (file_exists($testImagePath)) {
    echo "✅ Test image created\n";
    
    // Test fallback enhancement
    if (FallbackImageEnhancement::enhance($testImagePath, $testOutputPath)) {
        echo "✅ Fallback image enhancement works\n";
    } else {
        echo "❌ Fallback image enhancement failed\n";
    }
    
    // Clean up test files
    unlink($testImagePath);
    if (file_exists($testOutputPath)) {
        unlink($testOutputPath);
    }
} else {
    echo "❌ Could not create test image\n";
}

// Test 7: Check file upload limits
echo "<h2>7. File Upload Limits Check</h2>\n";
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxExecutionTime = ini_get('max_execution_time');
$memoryLimit = ini_get('memory_limit');

echo "Upload max filesize: $uploadMaxFilesize\n";
echo "Post max size: $postMaxSize\n";
echo "Max execution time: $maxExecutionTime seconds\n";
echo "Memory limit: $memoryLimit\n";

if (intval($uploadMaxFilesize) >= 10) {
    echo "✅ Upload limit is sufficient\n";
} else {
    echo "⚠️ Upload limit may be too low for 10MB files\n";
}

echo "<h2>Setup Test Complete</h2>\n";
echo "<p>If all tests pass, your FotoFix installation is ready to use!</p>\n";
echo "<p>Remember to configure your API keys in api/config.php before going live.</p>\n";
?>
