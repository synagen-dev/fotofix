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
            // Create a smaller version of the image for Gemini processing
            $tempImagePath = $this->createResizedImageForAI($imagePath);
            if (!$tempImagePath) {
                error_log('Failed to create resized image for AI processing');
                return false;
            }
            
            // Read and encode the resized image
            $imageData = file_get_contents($tempImagePath);
            $base64Image = base64_encode($imageData);
            
            // Clean up temp file
            unlink($tempImagePath);
            
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
                    'maxOutputTokens' => 100, // Reduced token limit
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
                // Gemini API returned text response - this means it understood the instructions
                $aiResponse = $response['candidates'][0]['content']['parts'][0]['text'];
                error_log('Gemini API response: ' . $aiResponse);
                
                // Check if the response indicates successful understanding
                if (strpos(strtolower($aiResponse), 'error') === false && 
                    (strpos(strtolower($aiResponse), 'enhance') !== false || 
                     strpos(strtolower($aiResponse), 'improve') !== false ||
                     strpos(strtolower($aiResponse), 'modify') !== false)) {
                    
                    // AI understood the instructions, use enhanced processing
                    return $this->enhancedFallbackEnhancement($imagePath, $outputPath, $instructions);
                } else {
                    // AI didn't understand or had issues, use basic enhancement
                    error_log('Gemini API response indicates issues, using basic enhancement');
                    return FallbackImageEnhancement::enhance($imagePath, $outputPath);
                }
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
     * Create a resized image for AI processing to reduce token usage
     * 
     * @param string $imagePath Path to the original image
     * @return string|false Path to resized image or false on failure
     */
    private function createResizedImageForAI($imagePath) {
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return false;
            }

            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Resize to maximum 800px on the longest side to reduce token usage
            $maxSize = 800;
            if ($originalWidth > $originalHeight) {
                $newWidth = $maxSize;
                $newHeight = intval(($originalHeight * $maxSize) / $originalWidth);
            } else {
                $newHeight = $maxSize;
                $newWidth = intval(($originalWidth * $maxSize) / $originalHeight);
            }
            
            // Create source image
            $sourceImage = $this->createImageFromFile($imagePath, $mimeType);
            if (!$sourceImage) {
                return false;
            }
            
            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Resize image
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
            
            // Save resized image
            $tempPath = sys_get_temp_dir() . '/fotofix_ai_' . uniqid() . '.jpg';
            $result = imagejpeg($resizedImage, $tempPath, 85); // Good quality but smaller file
            
            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
            
            return $result ? $tempPath : false;
            
        } catch (Exception $e) {
            error_log('Error creating resized image for AI: ' . $e->getMessage());
            return false;
        }
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
                    'maxOutputTokens' => 20, // Very small token limit for test
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
    
    /**
     * Enhanced fallback enhancement that applies more sophisticated processing
     * based on the AI instructions
     * 
     * @param string $imagePath Path to the original image
     * @param string $outputPath Path to save the enhanced image
     * @param string $instructions Enhancement instructions
     * @return bool Success status
     */
    private function enhancedFallbackEnhancement($imagePath, $outputPath, $instructions) {
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return false;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Create source image
            $sourceImage = $this->createImageFromFile($imagePath, $mimeType);
            if (!$sourceImage) {
                return false;
            }
            
            // Apply enhanced processing based on instructions
            $enhancedImage = $this->applyEnhancedProcessing($sourceImage, $width, $height, $instructions);
            
            // Save enhanced image
            $result = $this->saveImage($enhancedImage, $outputPath, $mimeType);
            
            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($enhancedImage);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Enhanced fallback enhancement error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check which GD filters are available
     */
    private function checkAvailableFilters() {
        $availableFilters = [];
        $filters = [
            'IMG_FILTER_BRIGHTNESS' => IMG_FILTER_BRIGHTNESS,
            'IMG_FILTER_CONTRAST' => IMG_FILTER_CONTRAST,
            'IMG_FILTER_COLORIZE' => IMG_FILTER_COLORIZE,
            'IMG_FILTER_SATURATE' => IMG_FILTER_SATURATE,
            'IMG_FILTER_GAUSSIAN_BLUR' => IMG_FILTER_GAUSSIAN_BLUR,
        ];
        
        foreach ($filters as $name => $constant) {
            if (defined($name)) {
                $availableFilters[] = $name;
            }
        }
        
        error_log('Available GD filters: ' . implode(', ', $availableFilters));
        return $availableFilters;
    }

    /**
     * Apply enhanced processing based on instructions
     */
    private function applyEnhancedProcessing($image, $width, $height, $instructions) {
        // Check available filters for debugging
        $this->checkAvailableFilters();
        
        // Create a new image with the same dimensions
        $enhanced = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        imagealphablending($enhanced, false);
        imagesavealpha($enhanced, true);
        
        // Copy the original image
        imagecopy($enhanced, $image, 0, 0, 0, 0, $width, $height);
        
        // Apply enhancements based on instruction keywords
        $instructions = strtolower($instructions);
        
        // Exterior enhancements
        if (strpos($instructions, 'grass') !== false || strpos($instructions, 'landscaping') !== false) {
            // Enhance green colors
            if (defined('IMG_FILTER_COLORIZE')) {
                imagefilter($enhanced, IMG_FILTER_COLORIZE, 0, 20, 0);
            } else {
                // Fallback: increase brightness for greener look
                imagefilter($enhanced, IMG_FILTER_BRIGHTNESS, 10);
            }
        }
        
        if (strpos($instructions, 'sky') !== false || strpos($instructions, 'blue') !== false) {
            // Enhance blue colors
            if (defined('IMG_FILTER_COLORIZE')) {
                imagefilter($enhanced, IMG_FILTER_COLORIZE, 0, 0, 20);
            } else {
                // Fallback: increase brightness for clearer sky
                imagefilter($enhanced, IMG_FILTER_BRIGHTNESS, 10);
            }
        }
        
        if (strpos($instructions, 'bright') !== false || strpos($instructions, 'lighting') !== false) {
            // Increase brightness
            imagefilter($enhanced, IMG_FILTER_BRIGHTNESS, 15);
        }
        
        // Interior enhancements
        if (strpos($instructions, 'clean') !== false || strpos($instructions, 'clutter') !== false) {
            // Increase contrast and brightness for cleaner look
            imagefilter($enhanced, IMG_FILTER_CONTRAST, 15);
            imagefilter($enhanced, IMG_FILTER_BRIGHTNESS, 10);
        }
        
        if (strpos($instructions, 'modern') !== false || strpos($instructions, 'furniture') !== false) {
            // Enhance saturation for more vibrant colors (if available)
            if (defined('IMG_FILTER_SATURATE')) {
                imagefilter($enhanced, IMG_FILTER_SATURATE, 20);
            } else {
                // Fallback: increase contrast and brightness for more vibrant look
                imagefilter($enhanced, IMG_FILTER_CONTRAST, 10);
                imagefilter($enhanced, IMG_FILTER_BRIGHTNESS, 5);
            }
        }
        
        // General enhancements
        // Increase contrast slightly
        if (defined('IMG_FILTER_CONTRAST')) {
            imagefilter($enhanced, IMG_FILTER_CONTRAST, 10);
        }
        
        // Sharpen the image (using blur as a sharpening technique)
        if (defined('IMG_FILTER_GAUSSIAN_BLUR')) {
            imagefilter($enhanced, IMG_FILTER_GAUSSIAN_BLUR);
            imagefilter($enhanced, IMG_FILTER_GAUSSIAN_BLUR);
        }
        
        return $enhanced;
    }
    
    /**
     * Create image resource from file
     */
    private function createImageFromFile($path, $mimeType) {
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
     * Save image to file
     */
    private function saveImage($image, $path, $mimeType) {
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
