<?php
/**
 * Quick Symlink Check
 * Run: php check-symlink.php
 */

$currentDir = __DIR__;
echo "Current directory: $currentDir\n\n";

// Check relative path
$linkRelative = 'public/storage';
$targetRelative = 'storage/app/public';

echo "Checking relative paths:\n";
echo "Link: $linkRelative\n";
echo "Target: $targetRelative\n\n";

if (file_exists($linkRelative)) {
    echo "✓ Link EXISTS (relative path)\n";
    if (is_link($linkRelative)) {
        echo "✓ It IS a symlink\n";
        echo "Points to: " . readlink($linkRelative) . "\n";
    } else {
        echo "⚠ It exists but is NOT a symlink\n";
    }
} else {
    echo "✗ Link does NOT exist (relative path)\n";
}

// Check absolute paths
$linkAbsolute = __DIR__ . '/public/storage';
$targetAbsolute = __DIR__ . '/storage/app/public';

echo "\nChecking absolute paths:\n";
echo "Link: $linkAbsolute\n";
echo "Target: $targetAbsolute\n\n";

if (file_exists($linkAbsolute)) {
    echo "✓ Link EXISTS (absolute path)\n";
    if (is_link($linkAbsolute)) {
        echo "✓ It IS a symlink\n";
        echo "Points to: " . readlink($linkAbsolute) . "\n";
        echo "Real path: " . realpath($linkAbsolute) . "\n";
    } else {
        echo "⚠ It exists but is NOT a symlink\n";
    }
} else {
    echo "✗ Link does NOT exist (absolute path)\n";
}

// Check if public directory exists
echo "\nChecking public directory:\n";
if (is_dir('public')) {
    echo "✓ public/ directory exists\n";
    echo "Contents of public/:\n";
    $publicContents = scandir('public');
    foreach ($publicContents as $item) {
        if ($item !== '.' && $item !== '..') {
            $path = 'public/' . $item;
            $type = is_link($path) ? 'SYMLINK' : (is_dir($path) ? 'DIR' : 'FILE');
            echo "  [$type] $item\n";
            if (is_link($path)) {
                echo "      -> " . readlink($path) . "\n";
            }
        }
    }
} else {
    echo "✗ public/ directory does NOT exist\n";
}

// Check if storage/app/public exists
echo "\nChecking storage/app/public:\n";
if (is_dir('storage/app/public')) {
    echo "✓ storage/app/public exists\n";
    echo "Contents:\n";
    $storageContents = scandir('storage/app/public');
    $fileCount = count(array_filter($storageContents, function($f) { return $f !== '.' && $f !== '..'; }));
    echo "  Files/directories: $fileCount\n";
    if ($fileCount > 0) {
        $sample = array_slice(array_filter($storageContents, function($f) { return $f !== '.' && $f !== '..'; }), 0, 5);
        foreach ($sample as $item) {
            echo "    - $item\n";
        }
    }
} else {
    echo "✗ storage/app/public does NOT exist\n";
}

echo "\n=== Manual Check Commands ===\n";
echo "Run these commands to check:\n";
echo "ls -la public/ | grep storage\n";
echo "ls -la public/storage\n";
echo "readlink public/storage\n";
