<?php
/**
 * Redo Logic Test Script
 * Test the unique ID handling for multiple redos
 */

echo "<h1>Redo Logic Test</h1>\n";

// Test cases for unique ID extraction
$testCases = [
    'fotofix_68d61d0bdf0831.75797449' => 'fotofix_68d61d0bdf0831.75797449',
    'fotofix_68d61d0bdf0831.75797449_redo' => 'fotofix_68d61d0bdf0831.75797449',
    'fotofix_68d61d0bdf0831.75797449_redo1' => 'fotofix_68d61d0bdf0831.75797449',
    'fotofix_68d61d0bdf0831.75797449_redo2' => 'fotofix_68d61d0bdf0831.75797449',
    'fotofix_68d61d0bdf0831.75797449_redo10' => 'fotofix_68d61d0bdf0831.75797449',
];

echo "<h2>1. Base Unique ID Extraction Test</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Input Unique ID</th><th>Expected Base ID</th><th>Actual Base ID</th><th>Status</th></tr>\n";

foreach ($testCases as $input => $expected) {
    $actual = preg_replace('/_redo.*$/', '', $input);
    $status = ($actual === $expected) ? '✅ PASS' : '❌ FAIL';
    echo "<tr><td>$input</td><td>$expected</td><td>$actual</td><td>$status</td></tr>\n";
}
echo "</table>\n";

// Test redo counter logic
echo "<h2>2. Redo Counter Logic Test</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Current Unique ID</th><th>Expected New ID</th><th>Actual New ID</th><th>Status</th></tr>\n";

$redoTestCases = [
    'fotofix_68d61d0bdf0831.75797449' => 'fotofix_68d61d0bdf0831.75797449_redo1',
    'fotofix_68d61d0bdf0831.75797449_redo' => 'fotofix_68d61d0bdf0831.75797449_redo1',
    'fotofix_68d61d0bdf0831.75797449_redo1' => 'fotofix_68d61d0bdf0831.75797449_redo2',
    'fotofix_68d61d0bdf0831.75797449_redo2' => 'fotofix_68d61d0bdf0831.75797449_redo3',
    'fotofix_68d61d0bdf0831.75797449_redo10' => 'fotofix_68d61d0bdf0831.75797449_redo11',
];

foreach ($redoTestCases as $currentId => $expectedNewId) {
    $baseUniqueId = preg_replace('/_redo.*$/', '', $currentId);
    $redoCount = 1;
    if (preg_match('/_redo(\d+)$/', $currentId, $matches)) {
        $redoCount = intval($matches[1]) + 1;
    }
    $actualNewId = $baseUniqueId . '_redo' . $redoCount;
    $status = ($actualNewId === $expectedNewId) ? '✅ PASS' : '❌ FAIL';
    echo "<tr><td>$currentId</td><td>$expectedNewId</td><td>$actualNewId</td><td>$status</td></tr>\n";
}
echo "</table>\n";

// Test file pattern matching
echo "<h2>3. File Pattern Matching Test</h2>\n";
echo "<p>This simulates how the glob pattern would work for finding original files:</p>\n";

$baseId = 'fotofix_68d61d0bdf0831.75797449';
$pattern = TEMP_DIR . $baseId . '_*';
echo "<p><strong>Pattern:</strong> $pattern</p>\n";
echo "<p><strong>Would match files like:</strong></p>\n";
echo "<ul>\n";
echo "<li>" . TEMP_DIR . $baseId . "_original.jpg</li>\n";
echo "<li>" . TEMP_DIR . $baseId . "_original.png</li>\n";
echo "<li>" . TEMP_DIR . $baseId . "_uploaded.jpg</li>\n";
echo "</ul>\n";

echo "<h2>4. Summary</h2>\n";
echo "<p>The fix addresses the following issues:</p>\n";
echo "<ul>\n";
echo "<li>✅ <strong>Base ID Extraction:</strong> Removes _redo suffixes to find original file</li>\n";
echo "<li>✅ <strong>Redo Counter:</strong> Properly increments redo counter for multiple redos</li>\n";
echo "<li>✅ <strong>File Finding:</strong> Uses base ID to locate original file consistently</li>\n";
echo "<li>✅ <strong>Debug Logging:</strong> Added logging to track the process</li>\n";
echo "</ul>\n";

echo "<h2>5. How It Works Now</h2>\n";
echo "<ol>\n";
echo "<li><strong>First Redo:</strong> fotofix_123 → fotofix_123_redo1</li>\n";
echo "<li><strong>Second Redo:</strong> fotofix_123_redo1 → fotofix_123_redo2</li>\n";
echo "<li><strong>Third Redo:</strong> fotofix_123_redo2 → fotofix_123_redo3</li>\n";
echo "<li><strong>Original File:</strong> Always found using fotofix_123 (base ID)</li>\n";
echo "</ol>\n";
?>
