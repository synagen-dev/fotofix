<?php
/**
 * Script to update enhancement instructions from markdown file
 * This helps you easily modify instructions in the markdown file and apply them to the PHP code
 */

require_once 'api/enhancement_instructions.php';

echo "=== FotoFix Enhancement Instructions Updater ===\n\n";

// Read the markdown file
$markdownFile = 'ENHANCEMENT_INSTRUCTIONS.md';
if (!file_exists($markdownFile)) {
    echo "❌ Markdown file not found: $markdownFile\n";
    exit(1);
}

$markdownContent = file_get_contents($markdownFile);
echo "✅ Read markdown file: $markdownFile\n";

// Parse the markdown to extract instructions
$instructions = parseMarkdownInstructions($markdownContent);

if (empty($instructions)) {
    echo "❌ No instructions found in markdown file\n";
    exit(1);
}

echo "✅ Found " . count($instructions) . " instruction categories\n";

// Update the PHP file
$phpFile = 'api/enhancement_instructions.php';
if (!file_exists($phpFile)) {
    echo "❌ PHP file not found: $phpFile\n";
    exit(1);
}

$phpContent = file_get_contents($phpFile);
echo "✅ Read PHP file: $phpFile\n";

// Update the instructions in the PHP file
$updatedContent = updatePhpInstructions($phpContent, $instructions);

if ($updatedContent !== $phpContent) {
    file_put_contents($phpFile, $updatedContent);
    echo "✅ Updated PHP file with new instructions\n";
} else {
    echo "ℹ️ No changes needed - instructions are already up to date\n";
}

echo "\n=== Update Complete ===\n";
echo "You can now test the updated instructions using test_setup.php\n";

/**
 * Parse markdown content to extract instructions
 */
function parseMarkdownInstructions($content) {
    $instructions = [];
    
    // Split by sections
    $sections = preg_split('/## (.+)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    $currentType = null;
    $currentCategory = null;
    
    for ($i = 1; $i < count($sections); $i += 2) {
        $sectionTitle = trim($sections[$i]);
        $sectionContent = $sections[$i + 1] ?? '';
        
        if (strpos($sectionTitle, 'Exterior Enhancement Options') !== false) {
            $currentType = 'exterior';
        } elseif (strpos($sectionTitle, 'Interior Enhancement Options') !== false) {
            $currentType = 'interior';
        } elseif (strpos($sectionTitle, '###') === 0) {
            // This is a category
            $categoryName = trim(str_replace('###', '', $sectionTitle));
            $categoryKey = strtolower(str_replace([' ', '&', '-'], ['_', 'and', '_'], $categoryName));
            
            // Extract instruction from code block
            if (preg_match('/```\s*\n(.*?)\n```/s', $sectionContent, $matches)) {
                $instruction = trim($matches[1]);
                
                if ($currentType && $categoryKey) {
                    $instructions[$currentType][$categoryKey] = [
                        'name' => $categoryName,
                        'description' => extractDescription($sectionContent),
                        'instructions' => $instruction
                    ];
                }
            }
        }
    }
    
    return $instructions;
}

/**
 * Extract description from section content
 */
function extractDescription($content) {
    if (preg_match('/\*\*Description\*\*: (.+)/', $content, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

/**
 * Update PHP file with new instructions
 */
function updatePhpInstructions($phpContent, $instructions) {
    // This is a simplified approach - in practice, you might want to use a more robust method
    
    foreach ($instructions as $type => $categories) {
        foreach ($categories as $key => $data) {
            // Find and replace the instruction in the PHP file
            $pattern = "/'$key' => \[\s*'name' => '[^']*',\s*'description' => '[^']*',\s*'instructions' => '[^']*'\s*\]/";
            $replacement = "'$key' => [\n                'name' => '{$data['name']}',\n                'description' => '{$data['description']}',\n                'instructions' => '{$data['instructions']}'\n            ]";
            
            $phpContent = preg_replace($pattern, $replacement, $phpContent);
        }
    }
    
    return $phpContent;
}
?>
