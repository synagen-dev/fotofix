<?php
/**
 * Enhancement Instructions System for FotoFix
 * Provides specific instructions for real estate photo enhancement
 */

class EnhancementInstructions {
    
    // Base instructions for different photo types
    const EXTERIOR_BASE = "This is a real estate exterior photo. Enhance it to make it more attractive to potential buyers while maintaining the exact structure, dimensions, and architectural features. ";
    
    const INTERIOR_BASE = "This is a real estate interior photo. Enhance it to make it more attractive to potential buyers while maintaining the exact structure, dimensions, walls, ceilings, and windows. ";
    
    // Enhancement options
    const ENHANCEMENT_OPTIONS = [
        'exterior' => [
            'landscaping' => [
                'name' => 'Landscaping Improvements',
                'description' => 'Enhance grass, plants, and outdoor features',
                'instructions' => 'Make the grass greener and more lush, add colorful flowers to flower beds and planters, trim and enhance existing bushes and trees, add seasonal flowers where appropriate, ensure all plants look healthy and well-maintained.'
            ],
            'sky_weather' => [
                'name' => 'Sky & Weather Enhancement',
                'description' => 'Improve sky appearance and weather conditions',
                'instructions' => 'Make the sky a beautiful blue with some white clouds, ensure good lighting conditions, remove any dark or stormy weather, add a pleasant sunny day atmosphere.'
            ],
            'exterior_cleaning' => [
                'name' => 'Exterior Cleaning',
                'description' => 'Clean and brighten exterior surfaces',
                'instructions' => 'Clean the exterior walls, windows, and doors, remove any dirt or stains, brighten the paint colors, clean the roof and gutters, ensure all exterior surfaces look fresh and well-maintained.'
            ],
            'outdoor_furniture' => [
                'name' => 'Outdoor Furniture',
                'description' => 'Add or improve outdoor furniture',
                'instructions' => 'Add attractive outdoor furniture like patio sets, chairs, or decorative elements where appropriate, ensure any existing outdoor furniture looks clean and modern.'
            ],
            'lighting' => [
                'name' => 'Exterior Lighting',
                'description' => 'Enhance outdoor lighting',
                'instructions' => 'Improve exterior lighting to show the property at its best, add warm lighting to highlight architectural features, ensure good visibility of the property entrance.'
            ]
        ],
        'interior' => [
            'furniture_modernization' => [
                'name' => 'Furniture Modernization',
                'description' => 'Replace old furniture with modern pieces',
                'instructions' => 'Replace any old, worn, or outdated furniture with modern, stylish pieces that appeal to contemporary buyers. Use neutral, elegant furniture that complements the space. Remove any furniture that makes the space look cluttered or dated.'
            ],
            'cleaning_decluttering' => [
                'name' => 'Cleaning & Decluttering',
                'description' => 'Remove clutter and clean surfaces',
                'instructions' => 'Remove all personal items, clutter, and unnecessary objects. Clean all surfaces including walls, floors, and fixtures. Remove any dirt, stains, or marks. Make the space look spotless and move-in ready.'
            ],
            'lighting_enhancement' => [
                'name' => 'Lighting Enhancement',
                'description' => 'Improve interior lighting',
                'instructions' => 'Enhance the lighting to make the space bright and welcoming. Add warm, inviting light that highlights the best features of the room. Ensure all areas are well-lit and the space feels open and airy.'
            ],
            'color_scheme' => [
                'name' => 'Color Scheme Update',
                'description' => 'Modernize color schemes',
                'instructions' => 'Update wall colors to modern, neutral tones that appeal to a wide range of buyers. Use colors like soft grays, whites, or light beiges. Ensure the color scheme is cohesive throughout the space.'
            ],
            'decorative_touches' => [
                'name' => 'Decorative Touches',
                'description' => 'Add tasteful decorative elements',
                'instructions' => 'Add tasteful, modern decorative elements like plants, artwork, or accessories that enhance the space without cluttering it. Use neutral, elegant pieces that appeal to contemporary buyers.'
            ]
        ]
    ];
    
    /**
     * Generate enhancement instructions based on user selections
     * 
     * @param array $selectedOptions User-selected enhancement options
     * @param string $photoType 'interior' or 'exterior'
     * @param string $customInstructions Additional custom instructions
     * @return string Complete enhancement instructions
     */
    public static function generateInstructions($selectedOptions, $photoType, $customInstructions = '') {
        $baseInstructions = $photoType === 'interior' ? self::INTERIOR_BASE : self::EXTERIOR_BASE;
        
        $enhancementInstructions = [];
        
        // Add selected enhancement options
        if (isset(self::ENHANCEMENT_OPTIONS[$photoType])) {
            foreach ($selectedOptions as $option) {
                if (isset(self::ENHANCEMENT_OPTIONS[$photoType][$option])) {
                    $enhancementInstructions[] = self::ENHANCEMENT_OPTIONS[$photoType][$option]['instructions'];
                }
            }
        }
        
        // Add custom instructions if provided
        if (!empty($customInstructions)) {
            $enhancementInstructions[] = $customInstructions;
        }
        
        // Combine all instructions
        $fullInstructions = $baseInstructions;
        if (!empty($enhancementInstructions)) {
            $fullInstructions .= implode(' ', $enhancementInstructions);
        }
        
        // Add final safety instructions
        $fullInstructions .= " Do not change the structure, dimensions, or architectural features of the building. Maintain the original perspective and composition of the photo.";
        
        return $fullInstructions;
    }
    
    /**
     * Get available enhancement options for a photo type
     * 
     * @param string $photoType 'interior' or 'exterior'
     * @return array Available options
     */
    public static function getOptions($photoType) {
        return isset(self::ENHANCEMENT_OPTIONS[$photoType]) ? self::ENHANCEMENT_OPTIONS[$photoType] : [];
    }
    
    /**
     * Analyze image to determine if it's interior or exterior
     * This is a simple heuristic - in production you might use AI for this
     * 
     * @param string $imagePath Path to the image
     * @return string 'interior' or 'exterior'
     */
    public static function analyzeImageType($imagePath) {
        // Simple heuristic based on filename or basic analysis
        $filename = basename($imagePath);
        $filename = strtolower($filename);
        
        // Check for common interior/exterior keywords in filename
        $interiorKeywords = ['interior', 'inside', 'room', 'kitchen', 'bathroom', 'bedroom', 'living', 'dining'];
        $exteriorKeywords = ['exterior', 'outside', 'front', 'back', 'yard', 'garden', 'patio', 'deck', 'facade'];
        
        foreach ($interiorKeywords as $keyword) {
            if (strpos($filename, $keyword) !== false) {
                return 'interior';
            }
        }
        
        foreach ($exteriorKeywords as $keyword) {
            if (strpos($filename, $keyword) !== false) {
                return 'exterior';
            }
        }
        
        // Default to exterior if we can't determine
        return 'exterior';
    }
    
    /**
     * Get default enhancement options for a photo type
     * 
     * @param string $photoType 'interior' or 'exterior'
     * @return array Default selected options
     */
    public static function getDefaultOptions($photoType) {
        $defaults = [
            'exterior' => ['landscaping', 'sky_weather', 'exterior_cleaning'],
            'interior' => ['furniture_modernization', 'cleaning_decluttering', 'lighting_enhancement']
        ];
        
        return isset($defaults[$photoType]) ? $defaults[$photoType] : [];
    }
}
?>
