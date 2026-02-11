<?php
/**
 * Verify Storage Symlink Script
 * Run: php verify-storage-link.php
 */

$link = __DIR__ . '/public/storage';
$target = __DIR__ . '/storage/app/public';

echo "=== Storage Symlink Verification ===\n\n";

echo "Link path: $link\n";
echo "Target path: $target\n\n";

// Check if link exists
if (file_exists($link)) {
    echo "✓ Link exists\n";
    
    if (is_link($link)) {
        echo "✓ It is a symlink\n";
        $actualTarget = readlink($link);
        echo "Symlink points to: $actualTarget\n";
        
        $expectedTarget = realpath($target);
        echo "Expected target: $expectedTarget\n";
        
        if ($actualTarget === $expectedTarget || realpath($link) === $expectedTarget) {
            echo "\n✓✓✓ SYMLINK IS WORKING CORRECTLY! ✓✓✓\n";
        } else {
            echo "\n⚠ Warning: Symlink points to different location\n";
        }
        
        // Check if target is accessible
        if (is_dir($link)) {
            echo "✓ Target directory is accessible through symlink\n";
            
            // List some files
            $files = scandir($link);
            $fileCount = count(array_filter($files, function($f) { return $f !== '.' && $f !== '..'; }));
            echo "Files/directories found: $fileCount\n";
            
            if ($fileCount > 0) {
                echo "\nSample contents:\n";
                $sample = array_slice(array_filter($files, function($f) { return $f !== '.' && $f !== '..'; }), 0, 5);
                foreach ($sample as $file) {
                    echo "  - $file\n";
                }
            }
        } else {
            echo "✗ Target directory is NOT accessible through symlink\n";
        }
    } elseif (is_dir($link)) {
        echo "⚠ It is a directory, not a symlink\n";
        echo "You may need to remove it and recreate the symlink\n";
    } else {
        echo "⚠ It exists but is not a symlink or directory\n";
    }
} else {
    echo "✗ Link does NOT exist\n";
    echo "\nYou need to create the symlink. Run:\n";
    echo "ln -s storage/app/public public/storage\n";
}

echo "\n=== Test URL ===\n";
echo "Test if storage is accessible:\n";
echo "https://courses.tazkiyahtarbiyah.com/storage/.gitignore\n";
echo "(Should show the .gitignore file content or 404 if not working)\n";
