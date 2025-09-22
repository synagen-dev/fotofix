<?php
/**
 * Test script to check which GD filters are available
 * Run this on your server to see what filters are supported
 */

echo "<h1>GD Filter Availability Test</h1>\n";

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    echo "❌ GD extension is not loaded\n";
    exit;
}

echo "✅ GD extension is loaded\n\n";

// List of common GD filters
$filters = [
    'IMG_FILTER_BRIGHTNESS' => 'Brightness adjustment',
    'IMG_FILTER_CONTRAST' => 'Contrast adjustment', 
    'IMG_FILTER_COLORIZE' => 'Color tinting',
    'IMG_FILTER_SATURATE' => 'Saturation adjustment',
    'IMG_FILTER_GAUSSIAN_BLUR' => 'Gaussian blur',
    'IMG_FILTER_EDGEDETECT' => 'Edge detection',
    'IMG_FILTER_EMBOSS' => 'Emboss effect',
    'IMG_FILTER_MEAN_REMOVAL' => 'Mean removal',
    'IMG_FILTER_SMOOTH' => 'Smooth effect',
    'IMG_FILTER_PIXELATE' => 'Pixelate effect',
    'IMG_FILTER_NEGATE' => 'Negate colors',
    'IMG_FILTER_GRAYSCALE' => 'Convert to grayscale',
    'IMG_FILTER_SELECTIVE_BLUR' => 'Selective blur',
    'IMG_FILTER_SCATTER' => 'Scatter effect',
    'IMG_FILTER_SMOOTH_MAKE' => 'Smooth make',
];

echo "<h2>Available GD Filters:</h2>\n";
$availableCount = 0;
foreach ($filters as $constant => $description) {
    if (defined($constant)) {
        echo "✅ $constant - $description\n";
        $availableCount++;
    } else {
        echo "❌ $constant - $description (NOT AVAILABLE)\n";
    }
}

echo "\n<h2>Summary:</h2>\n";
echo "Available filters: $availableCount out of " . count($filters) . "\n";

// Test basic image operations
echo "\n<h2>Basic Image Operations Test:</h2>\n";

try {
    // Create a test image
    $testImage = imagecreate(100, 100);
    if ($testImage) {
        echo "✅ imagecreate() works\n";
        
        // Test basic filters
        if (defined('IMG_FILTER_BRIGHTNESS')) {
            imagefilter($testImage, IMG_FILTER_BRIGHTNESS, 10);
            echo "✅ IMG_FILTER_BRIGHTNESS works\n";
        }
        
        if (defined('IMG_FILTER_CONTRAST')) {
            imagefilter($testImage, IMG_FILTER_CONTRAST, 10);
            echo "✅ IMG_FILTER_CONTRAST works\n";
        }
        
        if (defined('IMG_FILTER_COLORIZE')) {
            imagefilter($testImage, IMG_FILTER_COLORIZE, 0, 10, 0);
            echo "✅ IMG_FILTER_COLORIZE works\n";
        }
        
        if (defined('IMG_FILTER_SATURATE')) {
            imagefilter($testImage, IMG_FILTER_SATURATE, 10);
            echo "✅ IMG_FILTER_SATURATE works\n";
        }
        
        imagedestroy($testImage);
        echo "✅ Image cleanup successful\n";
        
    } else {
        echo "❌ Failed to create test image\n";
    }
} catch (Exception $e) {
    echo "❌ Error during image operations: " . $e->getMessage() . "\n";
}

echo "\n<h2>PHP Version and GD Info:</h2>\n";
echo "PHP Version: " . phpversion() . "\n";
echo "GD Version: " . gd_info()['GD Version'] . "\n";
echo "GD Support: " . (gd_info()['GD Version'] ? 'Yes' : 'No') . "\n";

echo "\n<h2>Recommendations:</h2>\n";
if (!defined('IMG_FILTER_SATURATE')) {
    echo "⚠️ IMG_FILTER_SATURATE is not available. The application will use fallback methods.\n";
}
if (!defined('IMG_FILTER_COLORIZE')) {
    echo "⚠️ IMG_FILTER_COLORIZE is not available. Color enhancement will be limited.\n";
}
if ($availableCount < 5) {
    echo "⚠️ Limited GD filter support. Consider updating PHP or GD extension.\n";
} else {
    echo "✅ Good GD filter support available.\n";
}
?>
