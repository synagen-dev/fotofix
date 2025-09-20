<?php
// Configuration file for FotoFix application

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'FotoFix');
define('APP_VERSION', '1.0.0');

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_FILES', 10);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Image storage paths
define('IMAGES_DIR', '/var/www/fotofix/images/');
define('TEMP_DIR', IMAGES_DIR . 'temp/');
define('ENHANCED_DIR', IMAGES_DIR . 'enhanced/');
define('PREVIEW_DIR', IMAGES_DIR . 'preview/');

// Image processing settings
define('PREVIEW_WIDTH', 600);
define('PREVIEW_QUALITY', 85);

// Google AI settings
define('GOOGLE_AI_API_KEY', 'YOUR_GOOGLE_AI_API_KEY'); // Replace with actual API key
define('GOOGLE_AI_MODEL', 'nano-banana'); // or whatever the correct model name is

// Stripe settings
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY'); // Replace with actual key
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_STRIPE_SECRET_KEY'); // Replace with actual key
define('PRICE_PER_IMAGE', 2000); // $20.00 in cents

// Default AI instructions
define('DEFAULT_INSTRUCTIONS', 'Make this image look more modern, more attractive, more appealing to a greater range of prospective buyers. Do not change the structure in any way. Do not change the size, position or orientation of any walls, floors or ceilings.');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CLEANUP_INTERVAL', 86400); // 24 hours

// Utility functions
function createDirectories() {
    $dirs = [IMAGES_DIR, TEMP_DIR, ENHANCED_DIR, PREVIEW_DIR];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function generateUniqueId() {
    return uniqid('fotofix_', true);
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

function isValidImageFile($file) {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_EXTENSIONS) && 
           $file['size'] <= MAX_FILE_SIZE &&
           getimagesize($file['tmp_name']) !== false;
}

function cleanupOldFiles() {
    $tempFiles = glob(TEMP_DIR . '*');
    $currentTime = time();
    
    foreach ($tempFiles as $file) {
        if (is_file($file) && ($currentTime - filemtime($file)) > CLEANUP_INTERVAL) {
            unlink($file);
        }
    }
}

// Initialize directories
createDirectories();

// Clean up old files
cleanupOldFiles();
?>
