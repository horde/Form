<?php
declare(strict_types=1);

/**
 * Verify all variable type renderers exist.
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "Verifying ALL Variable Type Renderers...\n\n";

// Load the renderer file
$rendererFile = file_get_contents(__DIR__ . '/src/V3/Renderer/HtmlControlRenderer.php');

// Get all variable types
$variableFiles = glob(__DIR__ . '/src/V3/*Variable.php');
$variables = [];

foreach ($variableFiles as $file) {
    $basename = basename($file, '.php');
    if ($basename !== 'BaseVariable') {
        $typeName = strtolower(str_replace('Variable', '', $basename));
        $variables[] = $typeName;
    }
}

sort($variables);

echo "Found " . count($variables) . " variable types\n\n";

// Check each variable has a renderer
$hasRenderer = [];
$missingRenderer = [];

foreach ($variables as $typeName) {
    $functionName = 'function render' . ucfirst($typeName);

    if (stripos($rendererFile, $functionName) !== false) {
        $hasRenderer[] = $typeName;
    } else {
        $missingRenderer[] = $typeName;
    }
}

echo "✓ Variables with renderers: " . count($hasRenderer) . "\n";

if (!empty($missingRenderer)) {
    echo "✗ Variables WITHOUT renderers: " . count($missingRenderer) . "\n";
    foreach ($missingRenderer as $type) {
        echo "  - $type\n";
    }
    exit(1);
} else {
    echo "✓ All variables have renderers!\n\n";
}

echo str_repeat('=', 60) . "\n";
echo "RENDERER COVERAGE: 100%\n";
echo str_repeat('=', 60) . "\n\n";

echo "All " . count($variables) . " variable types have renderers:\n\n";

$chunks = array_chunk($hasRenderer, 6);
foreach ($chunks as $chunk) {
    echo "  " . implode(', ', $chunk) . "\n";
}

echo "\nTotal renderers implemented: " . count($hasRenderer) . "/57\n";
echo "Coverage: 100%\n\n";

// Count render methods
preg_match_all('/protected function render[A-Z][a-z]+/', $rendererFile, $matches);
$renderMethods = count($matches[0]);

echo "Render methods in HtmlControlRenderer: $renderMethods\n";
echo "(Includes modern/legacy/fallback variants for date/time types)\n";
