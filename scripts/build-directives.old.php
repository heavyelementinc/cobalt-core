<?php

// Define the variable that prevents the environment from attempting to execute
// routes and controllers
define("UNIT_TESTS", true);
// Load the environment which includes our autoloader
require_once __DIR__ . '/../env.php';
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
    $properties[] = " * @property-read {$shortName} \${$propertyName}";
}

sort($use);
sort($properties);


// 2. Read existing DocBlock to preserve other data (descriptions, @author, etc.)
$preservedDocBlockLines = [];

if (file_exists($targetFile)) {
    $targetContents = file_get_contents($targetFile);
    
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
            // (If you have NON-directive @property-reads you want to keep, you can tighten this regex)
            if (preg_match('/@property-read\s+/', $trimmed)) {
                continue;
            }
            
            $preservedDocBlockLines[] = $line;
        }
    }
} else {
    echo "Warning: Target file not found at {$targetFile}. Generating DocBlock from scratch.\n\n";
}


// 3. Output strictly formatted lists ready for copy/pasting
echo "========================================================\n";
echo " COPY/PASTE READY: use Statements\n";
echo "========================================================\n\n";

foreach ($use as $fqn) {
    echo "use {$fqn};\n";
}

echo "\n\n";

echo "/**\n";

// Print the preserved existing lines first (like descriptions, @author, etc.)
foreach ($preservedDocBlockLines as $line) {
    // Ensure we don't accidentally print empty lines at the very top
    if (trim($line) === '*' && empty($properties)) continue;
    echo $line . "\n";
}

// Add a spacer if there was existing data
if (!empty($preservedDocBlockLines)) {
    echo " *\n";
}

// Print the newly generated properties
foreach ($properties as $propertyTag) {
    echo "{$propertyTag}\n";
}

echo " */\n";