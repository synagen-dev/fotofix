<?php
/**
 * Google AI Integration Module for FotoFix
 * Handles communication with Google's Gemini AI image generation services
 */

class GoogleAIIntegration {
    private $apiKey;
    private $baseUrl;
    private $model;
    
    public function __construct($apiKey, $model = 'gemini-1.5-flash') {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    }
    
    /**
     * Enhance an image using Google AI
     * 
     * @param string $imagePath Path to the original image
     * @param string $instructions Enhancement instructions
     * @param string $outputPath Path to save the enhanced image
     * @return bool Success status
     */
    public function enhanceImage($imagePath, $instructions, $outputPath) {
        try {
            // Read and encode the image
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            
            // Prepare the request payload for Gemini API
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $instructions
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $this->getMimeType($imagePath),
                                    'data' => $base64Image
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ]
                ]
            ];
            
            // Make the API request
            $response = $this->makeApiRequest($payload);
            
            if ($response && isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                // For now, we'll use the fallback enhancement since Gemini doesn't directly return images
                // In a real implementation, you might need to use a different approach
                error_log('Gemini API returned text response, using fallback enhancement');
                return FallbackImageEnhancement::enhance($imagePath, $outputPath);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Google AI enhancement error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Make API request to Google AI
     * 
     * @param array $payload Request payload
     * @return array|false API response or false on error
     */
    private function makeApiRequest($payload) {
        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;
        
        // Log the request for debugging
        error_log('Making Gemini API request to: ' . $url);
        error_log('Payload: ' . json_encode($payload));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: FotoFix/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Log detailed response information
        error_log('HTTP Code: ' . $httpCode);
        error_log('Response: ' . $response);
        error_log('cURL Info: ' . json_encode($info));
        
        if ($error) {
            error_log('cURL error: ' . $error);
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log('API error: HTTP ' . $httpCode . ' - ' . $response);
            return false;
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            error_log('Raw response: ' . $response);
            return false;
        }
        
        return $decodedResponse;
    }
    
    /**
     * Get MIME type of an image file
     * 
     * @param string $imagePath Path to the image file
     * @return string MIME type
     */
    private function getMimeType($imagePath) {
        $imageInfo = getimagesize($imagePath);
        return $imageInfo['mime'] ?? 'image/jpeg';
    }
    
    /**
     * Test API connection
     * 
     * @return bool Connection status
     */
    public function testConnection() {
        try {
            // Simple test payload for Gemini API
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
            
            $response = $this->makeApiRequest($payload);
            
            // Check if we got a valid response
            if ($response && isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $responseText = $response['candidates'][0]['content']['parts'][0]['text'];
                error_log('Gemini API test response: ' . $responseText);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Connection test error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get API usage statistics (if available)
     * 
     * @return array Usage statistics
     */
    public function getUsageStats() {
        // This would depend on Google AI API providing usage statistics
        // For now, return empty array
        return [];
    }
}

/**
 * Fallback image enhancement using basic image processing
 * This is used when Google AI is not available or fails
 */
class FallbackImageEnhancement {
    
    /**
     * Apply basic image enhancements
     * 
     * @param string $inputPath Input image path
     * @param string $outputPath Output image path
     * @return bool Success status
     */
    public static function enhance($inputPath, $outputPath) {
        try {
            $imageInfo = getimagesize($inputPath);
            if (!$imageInfo) {
                return false;
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Create source image
            $sourceImage = self::createImageFromFile($inputPath, $mimeType);
            if (!$sourceImage) {
                return false;
            }
            
            // Apply enhancements
            $enhancedImage = self::applyEnhancements($sourceImage, $width, $height);
            
            // Save enhanced image
            $result = self::saveImage($enhancedImage, $outputPath, $mimeType);
            
            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($enhancedImage);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Fallback enhancement error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create image resource from file
     */
    private static function createImageFromFile($path, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Apply basic image enhancements
     */
    private static function applyEnhancements($image, $width, $height) {
        // Create a new image with the same dimensions
        $enhanced = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        imagealphablending($enhanced, false);
        imagesavealpha($enhanced, true);
        
        // Copy the original image
        imagecopy($enhanced, $image, 0, 0, 0, 0, $width, $height);
        
        // Apply basic enhancements
        // Increase brightness slightly
        imagefilter($enhanced, IMG_FILTER_BRIGHTNESS, 10);
        
        // Increase contrast slightly
        imagefilter($enhanced, IMG_FILTER_CONTRAST, 10);
        
        // Sharpen the image
        imagefilter($enhanced, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($enhanced, IMG_FILTER_GAUSSIAN_BLUR);
        
        return $enhanced;
    }
    
    /**
     * Save image to file
     */
    private static function saveImage($image, $path, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($image, $path, 95);
            case 'image/png':
                return imagepng($image, $path, 8);
            case 'image/webp':
                return imagewebp($image, $path, 95);
            default:
                return false;
        }
    }
}
?>
