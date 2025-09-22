<?php
// Configuration file for FotoFix application

// Error reporting (disable in production)
ini_set('display_errors', 1);
// ini_set('display_errors', 0);  // Don't display errors to user
// ini_set('display_startup_errors', 0);
error_reporting(E_ALL); // Still log all errors, but don't display them

GLOBAL $logdir;
$logdir="/mnt/docker/apps/logs/fotofix";

$error_log="$logdir/error.log";
ini_set('error_log', $error_log);
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR); // either "/" or "\"
GLOBAL $base_dir;
$base_dir="/var/www/synagen/fotofix";
GLOBAL $base_domain;
$base_domain="synagen.net/fotofix";
ini_set('error_log', "$logdir/error.log");

GLOBAL $debugMode;
$debugMode=true;
GLOBAL $debugLevel;
$debugLevel=3; 
GLOBAL $glog;
if($debugMode){
	$glog=fopen("$logdir/debug.log", "a");
	if(!$glog){ echo "FATAL ERROR - UNABLE TO OPEN glog file"; exit(0);}
}

// Application settings
define('APP_NAME', 'FotoFix');
define('APP_VERSION', '1.0.0');
if($debugMode && $glog)fwrite($glog, __FILE__." line ".__LINE__.", --- START v ".APP_VERSION." ---\r\n");

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_FILES', 10);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Image storage paths
define('IMAGES_DIR', '/var/www/synagen/fotofix/images/');
define('TEMP_DIR', IMAGES_DIR . 'temp/');
define('ENHANCED_DIR', IMAGES_DIR . 'enhanced/');
define('PREVIEW_DIR', IMAGES_DIR . 'preview/');

// Image processing settings
define('PREVIEW_WIDTH', 600);
define('PREVIEW_QUALITY', 85);

// Google AI settings
$googleApiKey = getenv('GOOGLE_AISTUDIO_KEY');
if ($debugMode && $glog) {
    fwrite($glog, "Google API Key from environment: " . ($googleApiKey ? substr($googleApiKey, 0, 10) . '...' : 'NOT SET') . "\r\n");
}
define('GOOGLE_AI_API_KEY', $googleApiKey); 
define('GOOGLE_AI_MODEL', 'gemini-1.5-flash'); 

// Default AI instructions
define('DEFAULT_INSTRUCTIONS', 'Make this image look more modern, more attractive, more appealing to a greater range of prospective buyers. Do not change the structure in any way. Do not change the size, position or orientation of any walls, floors or ceilings.');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CLEANUP_INTERVAL', 86400); // 24 hours

// Utility functions

$autoload= "/var/www/vendor/autoload.php"; 
GLOBAL $autoload;
require($autoload);

use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;

// Function to safely get a secret value
function getSecretValue($projectId, $secretId) {
    GLOBAL $glog;
	GLOBAL $debugMode;

    if (empty($projectId) || empty($secretId)) {
        if ($debugMode && $glog) fwrite($glog, "ERROR: Missing projectId or secretId\r\n");
        return "";
    }
    
    try {
        // Create client
        $client = new SecretManagerServiceClient();
        
        // Build the resource name
        $name = "projects/$projectId/secrets/$secretId/versions/latest";
        if ( $debugMode && $glog) fwrite($glog, "getSecretValue(\$projectId=$projectId, \$secretId=$secretId, Secret name=$name\r\n");
        
        try {
            // Try the newer approach with request object
            $request = new AccessSecretVersionRequest();
            $request->setName($name);
            $response = $client->accessSecretVersion($request);
            return $response->getPayload()->getData();
        } catch (Exception $e1) {
            if ($debugMode && $glog) fwrite($glog, "First approach failed: " . $e1->getMessage() . "\r\n");
            
            try {
                // Try direct approach (older API)
                $response = $client->accessSecretVersion($name);
                return $response->getPayload()->getData();
            } catch (Exception $e2) {
                if ($debugMode && $glog) fwrite($glog, "Second approach failed: " . $e2->getMessage() . "\r\n");
                
                try {
                    // Try getSecretVersion approach
                    $response = $client->getSecretVersion($name);
                    // This might not have getPayload method, try alternative approaches
                    if (method_exists($response, 'getPayload')) {
                        return $response->getPayload()->getData();
                    } else if (method_exists($client, 'accessSecretVersion')) {
                        // Try one more approach
                        $accessResponse = $client->accessSecretVersion($name);
                        return $accessResponse->getPayload()->getData();
                    }
                } catch (Exception $e3) {
                    if ($debugMode && $glog) fwrite($glog, "All approaches failed: " . $e3->getMessage() . "\r\n");
                }
            }
        }
    } catch (Exception $e) {
		error_log("getSecretValue(\$projectId=$projectId, \$secretId=$secretId, Secret name=$name\r\n");
        error_log("Secret Manager error: " . $e->getMessage() . "\r\n");
    }
    
    return ""; // Return empty string if all approaches fail
}
// Stripe settings
GLOBAL $stripe_live;
$stripe_live=false;
$projectId = getenv('PROJECTID');  // GCP project ID
if ($stripe_live){
	define('STRIPE_PRODUCT_ID',      'prod_T6CFdw1gQaKIy2');
	define('STRIPE_PRICE_ID',        'price_1S9zqYHa7s4rHLRu8IfrOht3');
	define('STRIPE_SIGNING_SECRET',  getSecretValue($projectId, getenv('STRIPE_SIGNING_TEST')) );
	define('STRIPE_SECRET_KEY',      getSecretValue($projectId, getenv('STRIPE_API_KEY_CHATBOT')) );
	define('STRIPE_PUBLISHABLE_KEY', getSecretValue($projectId, getenv('STRIPE_PUBLIC_CHATBOT_LIVE') ) ); 
} else {
	define('STRIPE_PRODUCT_ID',      'prod_T6CKsyxfHI7odI');
	define('STRIPE_PRICE_ID',        'price_1S9zw6HgZZaGOV9e2t9c6sYz');
	define('STRIPE_SIGNING_SECRET',  getSecretValue($projectId, getenv('STRIPE_SIGNING_LIVE')) );
	define('STRIPE_SECRET_KEY',      getSecretValue($projectId, getenv('STRIPE_KEY_CHATBOT_TEST')) );
	define('STRIPE_PUBLISHABLE_KEY', getSecretValue($projectId, getenv('STRIPE_PUBLIC_CHATBOT_TEST') ) ); 
}
define('PRICE_PER_IMAGE', 2000); // $20.00 in cents

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
