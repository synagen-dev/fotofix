<?php
require_once 'config.php';
require_once 'google_ai_integration.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['image_index']) || !isset($input['enhanced_images'])) {
        throw new Exception('Invalid request data');
    }

    $imageIndex = intval($input['image_index']);
    $enhancedImages = $input['enhanced_images'];
    
    if (!isset($enhancedImages[$imageIndex])) {
        throw new Exception('Invalid image index');
    }

    $imageData = $enhancedImages[$imageIndex];
    $uniqueId = $imageData['unique_id'];
    
    // Find the original file
    $originalFiles = glob(TEMP_DIR . $uniqueId . '_*');
    if (empty($originalFiles)) {
        throw new Exception('Original image not found');
    }
    
    $originalPath = $originalFiles[0];
    
    // Get custom instructions (you might want to store these with the session)
    $customInstructions = isset($input['custom_instructions']) ? trim($input['custom_instructions']) : '';
    $finalInstructions = !empty($customInstructions) ? 
        DEFAULT_INSTRUCTIONS . ' ' . $customInstructions : 
        DEFAULT_INSTRUCTIONS;

    // Process with AI again
    $enhancedImage = processImageWithAI($originalPath, $finalInstructions, $uniqueId . '_redo');
    
    if ($enhancedImage) {
        echo json_encode([
            'success' => true,
            'enhanced_image' => [
                'original_name' => $imageData['original_name'],
                'unique_id' => $uniqueId . '_redo',
                'preview_url' => $enhancedImage['preview_url'],
                'download_url' => $enhancedImage['download_url'],
                'session_id' => $imageData['session_id']
            ]
        ]);
    } else {
        throw new Exception('Failed to redo image processing');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function processImageWithAI($originalPath, $instructions, $uniqueId) {
    try {
        $enhancedPath = ENHANCED_DIR . $uniqueId . '_enhanced.png';
        $previewPath = PREVIEW_DIR . $uniqueId . '_preview.png';
        
        // Try Google AI enhancement first
        $googleAI = new GoogleAIIntegration(GOOGLE_AI_API_KEY, GOOGLE_AI_MODEL);
        $aiSuccess = false;
        
        // Test connection first, then send image to AI for enhancing
        if ($googleAI->testConnection()) {
            $aiSuccess = $googleAI->enhanceImage($originalPath, $instructions, $enhancedPath);
        }
        
        // Fallback to basic enhancement if AI fails
        //if (!$aiSuccess) {
            //error_log('Google AI failed, using fallback enhancement for: ' . $uniqueId);
            // $aiSuccess = FallbackImageEnhancement::enhance($originalPath, $enhancedPath);
        //}
        
        if (!$aiSuccess) {
            // Last resort: just copy the original
            //if (!copy($originalPath, $enhancedPath)) {
                return false;
            //}
        }
        
        // Create preview version
        if (!createPreviewImage($enhancedPath, $previewPath)) {
            return false;
        }
        
        return [
            'preview_url' => 'api/get_image.php?type=preview&id=' . $uniqueId,
            'download_url' => 'api/get_image.php?type=enhanced&id=' . $uniqueId
        ];
        
    } catch (Exception $e) {
        error_log('AI processing error: ' . $e->getMessage());
        return false;
    }
}

function createPreviewImage($sourcePath, $previewPath) {
    try {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Calculate new dimensions
        $newWidth = PREVIEW_WIDTH;
        $newHeight = intval(($sourceHeight * $newWidth) / $sourceWidth);

        // Create source image
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Create preview image
        $previewImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($previewImage, false);
            imagesavealpha($previewImage, true);
            $transparent = imagecolorallocatealpha($previewImage, 255, 255, 255, 127);
            imagefilledrectangle($previewImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $previewImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Save preview image
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($previewImage, $previewPath, PREVIEW_QUALITY);
                break;
            case 'image/png':
                $result = imagepng($previewImage, $previewPath, 8);
                break;
            case 'image/webp':
                $result = imagewebp($previewImage, $previewPath, PREVIEW_QUALITY);
                break;
        }

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($previewImage);

        return $result;
        
    } catch (Exception $e) {
        error_log('Preview creation error: ' . $e->getMessage());
        return false;
    }
}
?>
