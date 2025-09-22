<?php
require_once 'config.php';
require_once 'google_ai_integration.php';
require_once 'enhancement_instructions.php';

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
    // Validate uploaded files
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        throw new Exception('No images uploaded');
    }

    $files = $_FILES['images'];
    $fileCount = count($files['name']);
    
    if ($fileCount > MAX_FILES) {
        throw new Exception('Too many files uploaded. Maximum ' . MAX_FILES . ' allowed.');
    }

    // Validate each file
    $validFiles = [];
    for ($i = 0; $i < $fileCount; $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error for file: ' . $file['name']);
        }

        if (!isValidImageFile($file)) {
            throw new Exception('Invalid file: ' . $file['name']);
        }

        $validFiles[] = $file;
    }

    // Get enhancement options
    $enhancementOptions = [];
    if (isset($_POST['enhancement_options'])) {
        $enhancementOptions = json_decode($_POST['enhancement_options'], true);
    }
    
    // Get custom instructions
    $customInstructions = isset($_POST['custom_instructions']) ? trim($_POST['custom_instructions']) : '';

    // Process each image
    $enhancedImages = [];
    $sessionId = generateUniqueId();

    foreach ($validFiles as $index => $file) {
        $originalFilename = sanitizeFilename($file['name']);
        $uniqueId = generateUniqueId();
        
        // Save original file
        $originalPath = TEMP_DIR . $uniqueId . '_' . $originalFilename;
        if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
            throw new Exception('Failed to save file: ' . $file['name']);
        }

        // Determine photo type and generate instructions
        $photoType = EnhancementInstructions::analyzeImageType($originalPath);
        
        // If mixed type is selected, analyze each image individually
        if (isset($enhancementOptions['photoType']) && $enhancementOptions['photoType'] === 'mixed') {
            $photoType = EnhancementInstructions::analyzeImageType($originalPath);
        } elseif (isset($enhancementOptions['photoType'])) {
            $photoType = $enhancementOptions['photoType'];
        }
        
        // Generate enhancement instructions
        $selectedOptions = isset($enhancementOptions['options']) ? $enhancementOptions['options'] : [];
        $customInstructions = isset($enhancementOptions['customInstructions']) ? $enhancementOptions['customInstructions'] : '';
        
        $finalInstructions = EnhancementInstructions::generateInstructions(
            $selectedOptions, 
            $photoType, 
            $customInstructions
        );

        // Process with AI
        $enhancedImage = processImageWithAI($originalPath, $finalInstructions, $uniqueId);
        
        if ($enhancedImage) {
            $enhancedImages[] = [
                'original_name' => $file['name'],
                'unique_id' => $uniqueId,
                'preview_url' => $enhancedImage['preview_url'],
                'download_url' => $enhancedImage['download_url'],
                'session_id' => $sessionId,
                'photo_type' => $photoType
            ];
        } else {
            throw new Exception('Failed to process image: ' . $file['name']);
        }
    }

    echo json_encode([
        'success' => true,
        'enhanced_images' => $enhancedImages,
        'session_id' => $sessionId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function processImageWithAI($originalPath, $instructions, $uniqueId) {
    try {
        $enhancedPath = ENHANCED_DIR . $uniqueId . '_enhanced.jpg';
        $previewPath = PREVIEW_DIR . $uniqueId . '_preview.jpg';
        
        // Try Google AI enhancement first
        $googleAI = new GoogleAIIntegration(GOOGLE_AI_API_KEY, GOOGLE_AI_MODEL);
        $aiSuccess = false;
        
        // Test connection first
        if ($googleAI->testConnection()) {
            $aiSuccess = $googleAI->enhanceImage($originalPath, $instructions, $enhancedPath);
        }
        
        // Fallback to basic enhancement if AI fails
        if (!$aiSuccess) {
            error_log('Google AI failed, using fallback enhancement for: ' . $uniqueId);
            $aiSuccess = FallbackImageEnhancement::enhance($originalPath, $enhancedPath);
        }
        
        if (!$aiSuccess) {
            // Last resort: just copy the original
            if (!copy($originalPath, $enhancedPath)) {
                return false;
            }
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
