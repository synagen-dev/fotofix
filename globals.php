<?php
ini_set('display_errors', 0);  // Don't display errors to user
ini_set('display_startup_errors', 0);
error_reporting(E_ALL); // Still log all errors, but don't display them

// Set up GLOBAL variables and open database connection
// ====================================================
GLOBAL $hostmachine;
$hostmachine="PROD";
GLOBAL $logdir;
$logdir='/mnt/docker/apps/logs/synagen';
ini_set('error_log', "$logdir/error.log");

GLOBAL $debugMode;
$debugMode=true;
// $debugLevel: 0=none, 1=log on/off & program execution, 2=SQL, 3=cookies, session vars & run variables & parameters
GLOBAL $debugLevel;
$debugLevel=3; 
GLOBAL $glog;
if($debugMode) $glog=fopen("$logdir/debug.log", "a");
else {echo "FATAL ERROR - UNABLE TO OPEN log file"; exit(0);}
GLOBAL $replyTo;
$replyTo="admin@synagen.net";
GLOBAL $sender;
$sender=getenv("SENDER_EMAIL");
GLOBAL $db_host;
GLOBAL $db_name;
GLOBAL $db_user;
GLOBAL $db_pwd;
GLOBAL $sendGridKeyName;
GLOBAL $sendGridAPIkey;
GLOBAL $projectId;
GLOBAL $account_sid, $auth_token; // Twilio
GLOBAL $pdo;
GLOBAL $hostmachine;
$hostmachine="PROD";
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR); // either "/" or "\"
GLOBAL $base_dir;
$base_dir="/var/www/synagen";
GLOBAL $base_domain;
$base_domain="synagen.net";
GLOBAL $logdir;
$logdir="/mnt/docker/apps/logs/synagen";
ini_set('error_log', "$logdir/error.log");
GLOBAL $organisation;  // The chatbot instance. identified by chatbots.id

// Function to sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    // Remove HTML tags and encode special characters
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// stupid bots
if (isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"],'.php/index.php')!==false ) {sleep(60); echo '404'; exit(0);}
if (isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"],'index.php/documents/documents')!==false ) {sleep(60); echo '404'; exit(0);}
// Remove any javascript embedded in parameters
if (isset($_SERVER["REQUEST_URI"]) && strpos(strtolower($_SERVER["REQUEST_URI"]),'javascript' ) !==false ) {sleep(60);  echo '404'; exit(0);}
if (isset($_SERVER["REQUEST_URI"]) && strpos(strtolower($_SERVER["REQUEST_URI"]), '//' ) !==false )        {sleep(60);  echo '404'; exit(0);}
if (isset($_SERVER["REQUEST_URI"]) && strpos(strtolower($_SERVER["REQUEST_URI"]), '/^' ) !==false )        {sleep(60);  echo '404'; exit(0);}
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] != 'POST' ) {
    http_response_code(200);
    exit();
}
date_default_timezone_set('Australia/Sydney');
$script_tz = date_default_timezone_get();
GLOBAL $today;
$today=date('Y-m-d H:i:s');
GLOBAL $todayDate;
$todayDate=date('d/m/Y');
GLOBAL $todayDateSQL;
$todayDateSQL=date('Y-m-d');
GLOBAL $today_us;
$today_us=date('m/d/Y');

set_time_limit( 300 );
ini_set('memory_limit', '128M');
ini_set('display_errors', 'Off');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_input_time', 300);
ini_set('max_execution_time', 300); 

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

$secretId 		= getenv('SENDGRID_API_KEY'); 	
$sendGridAPIkey = getSecretValue($projectId, $secretId);
// Get the Twilio Verify Service SID
$service_sid = getSecretValue($projectId, 'Twilio_Verify_SID');
// Get the Twilio Account SID
$account_sid = getSecretValue($projectId, 'Twilio_account_sid');
// Get the Twilio Auth Token
$auth_token = getSecretValue($projectId, 'Twilio_Auth_Token');

GLOBAL $stripe_live; // TRUE if using live Stripe. FALSE is using sandbox
$stripe_live=true;
GLOBAL $STRIPE_API_KEY;
GLOBAL $priceId;

if($stripe_live===true) {
	$secretId        = getenv('STRIPE_API_KEY_CHATBOT');
	$STRIPE_API_KEY  = getSecretValue($projectId, $secretId);
	$secretId        = getenv('STRIPE_PUBLIC_CHATBOT_LIVE');
	$publicKey       = getSecretValue($projectId, $secretId);
	$productId       = 'prod_T3G309tuUd5Pnn';  // Live product 
	$priceId         = 'price_1S79XUHreHP5N1ejzg6JBTD8';
	$secretId        = getenv('STRIPE_SIGNING_SECRET_LIVE');
	$stripe_signing  = getSecretValue($projectId, $secretId);
} else {
	$secretId        = getenv('STRIPE_KEY_CHATBOT_TEST');
	$STRIPE_API_KEY  = getSecretValue($projectId, $secretId);
	$secretId        = getenv('STRIPE_PUBLIC_CHATBOT_TEST');
	$publicKey  	 = getSecretValue($projectId, $secretId);
	$productId       = 'prod_T3G1gBBCmUBfsW';  // Test product
	$priceId         = 'price_1S88l1Hlb4nlMcTxkWL83UiB'; // Price of that test product
	$secretId        = getenv('STRIPE_SIGNING_SECRET_TEST');
	$stripe_signing  = getSecretValue($projectId, $secretId);
}
// if($debugMode && $glog)fwrite($glog, __FILE__." line ".__LINE__.", \$secretId=$secretId, \$stripe_signing =$stripe_signing  \r\n");

	
?>