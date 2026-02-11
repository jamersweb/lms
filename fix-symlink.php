<?php
/**
 * Fix Storage Symlink - Use Correct Relative Path
 * Run: php fix-symlink.php
 */

$link = __DIR__ . '/public/storage';
$targetRelative = '../storage/app/public'; // Relative to public/ directory

echo "=== Fixing Storage Symlink ===\n\n";

// Remove existing symlink
if (file_exists($link)) {
    echo "Removing existing symlink...\n";
    if (is_link($link)) {
        unlink($link);
        echo "✓ Removed existing symlink\n";
    } elseif (is_dir($link)) {
        rmdir($link);
        echo "✓ Removed existing directory\n";
    } else {
        unlink($link);
        echo "✓ Removed existing file\n";
    }
}

// Create symlink with correct relative path
echo "\nCreating symlink with correct path...\n";
echo "Link: $link\n";
echo "Target (relative): $targetRelative\n";

if (symlink($targetRelative, $link)) {
    echo "✓ Symlink created successfully!\n";
    
    // Verify
    if (is_link($link)) {
        $actualTarget = readlink($link);
        echo "✓ Verified: Points to $actualTarget\n";
        
        // Check if it resolves correctly
        $resolved = realpath($link);
        $expected = realpath(__DIR__ . '/storage/app/public');
        
        if ($resolved === $expected) {
            echo "✓✓✓ SUCCESS! Symlink resolves correctly!\n";
            echo "Resolved path: $resolved\n";
            
            // Test if files are accessible
            if (is_dir($link)) {
                $files = scandir($link);
                $fileCount = count(array_filter($files, function($f) { return $f !== '.' && $f !== '..'; }));
                echo "✓ Files accessible: $fileCount items found\n";
            }
        } else {
            echo "⚠ Warning: Resolved path doesn't match expected\n";
            echo "  Resolved: $resolved\n";
            echo "  Expected: $expected\n";
        }
    }
} else {
    $error = error_get_last();
    echo "✗ Failed to create symlink\n";
    echo "Error: " . ($error['message'] ?? 'Unknown') . "\n";
    echo "\nTry manually:\n";
    echo "cd public\n";
    echo "ln -s ../storage/app/public storage\n";
    echo "cd ..\n";
}
