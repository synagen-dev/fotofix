<?php
/**
 * Simple test script for Gemini API
 * Run this on your Linux server to debug the connection
 */

// Test 1: Check if environment variable is set
echo "=== Environment Variable Test ===\n";
$apiKey = getenv('GOOGLE_AISTUDIO_KEY');
if ($apiKey) {
    echo "✅ GOOGLE_AISTUDIO_KEY is set\n";
    echo "Key length: " . strlen($apiKey) . " characters\n";
    echo "Key starts with: " . substr($apiKey, 0, 10) . "...\n";
} else {
    echo "❌ GOOGLE_AISTUDIO_KEY is not set\n";
    echo "Available environment variables containing 'GOOGLE':\n";
    foreach ($_ENV as $key => $value) {
        if (stripos($key, 'google') !== false) {
            echo "  $key = " . substr($value, 0, 20) . "...\n";
        }
    }
}

// Test 2: Simple cURL test
echo "\n=== cURL Test ===\n";
if (function_exists('curl_init')) {
    echo "✅ cURL is available\n";
    
    // Test with a simple request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/get');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL error: $error\n";
    } else {
        echo "✅ cURL test successful (HTTP $httpCode)\n";
    }
} else {
    echo "❌ cURL is not available\n";
}

// Test 3: Test Gemini API if we have a key
if ($apiKey) {
    echo "\n=== Gemini API Test ===\n";
    
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
    
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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($error) {
        echo "cURL Error: $error\n";
    } else {
        echo "Response: " . substr($response, 0, 500) . "...\n";
        
        if ($httpCode === 200) {
            $decoded = json_decode($response, true);
            if ($decoded && isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                echo "✅ API call successful!\n";
                echo "Response: " . $decoded['candidates'][0]['content']['parts'][0]['text'] . "\n";
            } else {
                echo "❌ Unexpected response format\n";
                echo "Decoded response: " . print_r($decoded, true) . "\n";
            }
        } else {
            echo "❌ API call failed with HTTP $httpCode\n";
        }
    }
} else {
    echo "\n=== Skipping Gemini API Test (no API key) ===\n";
}

echo "\n=== Test Complete ===\n";
?>
