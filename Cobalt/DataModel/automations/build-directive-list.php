<?php

// Define the variable that prevents the environment from attempting to execute
// routes and controllers
define("UNIT_TESTS", true);
// Load the environment which includes our autoloader
require_once __DIR__ . '/../../../env.php';
// Define the DirectiveCommon FQN we want to filter against
$mustExtend = "Cobalt\DataModel\Directives\Base\DirectiveCommon";

$baseDir = __ENV_ROOT__ . '/Cobalt/DataModel/Directives';
$targetFile = __ENV_ROOT__ . '/Cobalt/DataModel/Classes/DirectiveList.php';
if (!is_dir($baseDir)) {
    echo "Directory not found: {$baseDir}\n";
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$use = [$mustExtend];
$properties = [];

// 1. Build the new lists from the directory
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $relativePath = ltrim(str_replace($baseDir, '', $file->getPathname()), DIRECTORY_SEPARATOR);
    $className = str_replace('.php', '', $relativePath);
    $fqn = 'Cobalt\\DataModel\\Directives\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $className);

    try {
        if (!class_exists($fqn)) continue;
        $reflection = new ReflectionClass($fqn);
        // Classes in this directory might be abstract or interfaces so let's skip those
        if($reflection->isAbstract()) continue;
        if($reflection->isInterface()) continue;
        // Find the root parent class statically since instantiating the various
        // classes require knowing typed parameters for the constructor
        $parent = $reflection->getParentClass();
        while(true) {
            $p = $parent->getParentClass();
            if(!$p) break;
            $parent = $p;
        }
        // Check that the class's FQNs match
        if($parent->getName() !== $mustExtend) continue;
        
    } catch (\Throwable $e) {
        continue;
    }

    $use[] = $fqn;
    // Get the explicitly specified name of the directive OR get dynamically generated directive name
    $propertyName = $reflection->getProperty('name')->getDefaultValue() ?? $fqn::directiveName();
    $shortName = basename(str_replace('\\', '/', $fqn));
    $properties[] = " * @property-read ?{$shortName} \${$propertyName}";
}

sort($use);
sort($properties);


// 2. Read existing file contents and parse out necessary blocks
if (!file_exists($targetFile)) {
    echo "Error: Target file not found at {$targetFile}.\n";
    exit(1);
}

$targetContents = file_get_contents($targetFile);
$preservedDocBlockLines = [];
$existingDocBlock = null;

// Find the DocBlock immediately preceding the DirectiveList class declaration
if (preg_match('/(\/\*\*.*?\*\/)\s*(?:final\s+|abstract\s+)?class\s+DirectiveList/is', $targetContents, $matches)) {
    $existingDocBlock = $matches[1];
    
    // Break into lines
    $lines = explode("\n", $existingDocBlock);
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Skip the opening and closing tags (we will rebuild them)
        if ($trimmed === '/**' || $trimmed === '*/') {
            continue;
        }
        
        // Skip any existing @property-read lines so we don't duplicate them
        if (preg_match('/@property-read\s+/', $trimmed)) {
            continue;
        }
        
        $preservedDocBlockLines[] = $line;
    }
}


// 3. Process and filter Use statements
$finalUses = [];

// Extract all existing use statements
if (preg_match_all('/^use\s+([^;]+);/m', $targetContents, $useMatches)) {
    foreach ($useMatches[1] as $existingUse) {
        $existingUse = trim($existingUse);
        
        // If it's in the Directives namespace, only keep it if it's in our newly generated list
        if (strpos($existingUse, 'Cobalt\\DataModel\\Directives') === 0) {
            if (in_array($existingUse, $use)) {
                $finalUses[] = $existingUse;
            }
        } else {
            // Preserve everything else
            $finalUses[] = $existingUse;
        }
    }
}

// Add any new ones that weren't in the file already
foreach ($use as $newUse) {
    if (!in_array($newUse, $finalUses)) {
        $finalUses[] = $newUse;
    }
}

sort($finalUses);


// 4. Modify File Contents

// Remove all existing `use` statements to prepare a clean slate
$targetContents = preg_replace('/^use\s+[^;]+;[ \t]*\r?\n?/m', '', $targetContents);

// Build the new use string block
$useBlock = implode("\n", array_map(function($u) { return "use {$u};"; }, $finalUses)) . "\n";

// Insert the new use block right after the namespace declaration
$targetContents = preg_replace(
    '/(namespace\s+[a-zA-Z0-9_\\\\]+;[ \t]*\r?\n?)/', 
    "$1\n" . $useBlock, 
    $targetContents, 
    1
);


// 5. Build and Replace the DocBlock

$newDocBlock = "/**\n";

// Print the preserved existing lines first (like descriptions, @author, etc.)
foreach ($preservedDocBlockLines as $line) {
    if (trim($line) === '*' && empty($properties)) continue;
    $newDocBlock .= $line . "\n";
}

// Add a spacer if there was existing data
if (!empty($preservedDocBlockLines)) {
    $newDocBlock .= " *\n";
}

// Print the newly generated properties
foreach ($properties as $propertyTag) {
    $newDocBlock .= $propertyTag . "\n";
}

$newDocBlock .= " */";

// Swap out the old DocBlock with the new one
if ($existingDocBlock) {
    $targetContents = str_replace($existingDocBlock, $newDocBlock, $targetContents);
} else {
    // Fallback: If no docblock existed, insert it directly before the class
    $targetContents = preg_replace(
        '/((?:final\s+|abstract\s+)?class\s+DirectiveList)/i',
        $newDocBlock . "\n$1",
        $targetContents,
        1
    );
}

// Ensure clean double linebreaks aren't destroyed around the use block
$targetContents = preg_replace("/\n{3,}/", "\n\n", $targetContents);


// 6. Write to File
file_put_contents($targetFile, $targetContents);

echo "✅ Successfully updated {$targetFile} with " . count($use) . " Directives.\n";