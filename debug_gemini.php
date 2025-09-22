<?php
/**
 * Debug script for Gemini API connection
 * This will help identify the exact issue with the API connection
 */

require_once 'api/config.php';
require_once 'api/google_ai_integration.php';

echo "<h1>Gemini API Debug Test</h1>\n";

// Check if API key is available
echo "<h2>1. API Key Check</h2>\n";
if (defined('GOOGLE_AI_API_KEY') && !empty(GOOGLE_AI_API_KEY)) {
    echo "✅ API Key is defined and not empty<br>";
    echo "Key length: " . strlen(GOOGLE_AI_API_KEY) . " characters<br>";
    echo "Key starts with: " . substr(GOOGLE_AI_API_KEY, 0, 10) . "...<br>";
} else {
    echo "❌ API Key is not defined or empty<br>";
    echo "GOOGLE_AI_API_KEY constant: " . (defined('GOOGLE_AI_API_KEY') ? 'defined' : 'not defined') . "<br>";
    echo "Value: " . (defined('GOOGLE_AI_API_KEY') ? GOOGLE_AI_API_KEY : 'N/A') . "<br>";
    exit;
}

// Check model
echo "<h2>2. Model Check</h2>\n";
echo "Model: " . (defined('GOOGLE_AI_MODEL') ? GOOGLE_AI_MODEL : 'Not defined') . "<br>";

// Test basic cURL functionality
echo "<h2>3. cURL Test</h2>\n";
if (function_exists('curl_init')) {
    echo "✅ cURL is available<br>";
    
    // Test basic HTTPS request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL error: $error<br>";
    } else {
        echo "✅ cURL HTTPS test successful (HTTP $httpCode)<br>";
    }
} else {
    echo "❌ cURL is not available<br>";
    exit;
}

// Test Gemini API directly
echo "<h2>4. Direct Gemini API Test</h2>\n";
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GOOGLE_AI_MODEL . ':generateContent?key=' . GOOGLE_AI_API_KEY;

echo "URL: " . $url . "<br>";

$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Hello, this is a test. Please respond with "Test successful".'
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.1,
        'maxOutputTokens' => 50,
    ]
];

echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "<br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: FotoFix/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "<h3>Response Details:</h3>\n";
echo "HTTP Code: $httpCode<br>";
echo "cURL Error: " . ($error ? $error : 'None') . "<br>";
echo "Response Length: " . strlen($response) . " characters<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

if ($httpCode === 200) {
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "✅ JSON decode successful<br>";
        if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            echo "✅ Response contains expected text: " . $decoded['candidates'][0]['content']['parts'][0]['text'] . "<br>";
        } else {
            echo "❌ Response structure unexpected<br>";
            echo "Available keys: " . implode(', ', array_keys($decoded)) . "<br>";
        }
    } else {
        echo "❌ JSON decode failed: " . json_last_error_msg() . "<br>";
    }
} else {
    echo "❌ HTTP request failed with code $httpCode<br>";
}

// Test using the GoogleAIIntegration class
echo "<h2>5. GoogleAIIntegration Class Test</h2>\n";
try {
    $googleAI = new GoogleAIIntegration(GOOGLE_AI_API_KEY, GOOGLE_AI_MODEL);
    echo "✅ GoogleAIIntegration object created successfully<br>";
    
    if ($googleAI->testConnection()) {
        echo "✅ testConnection() returned true<br>";
    } else {
        echo "❌ testConnection() returned false<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception in GoogleAIIntegration: " . $e->getMessage() . "<br>";
}

echo "<h2>Debug Complete</h2>\n";
echo "Check the error logs at: /mnt/docker/apps/logs/fotofix/error.log for more details<br>";
?>
