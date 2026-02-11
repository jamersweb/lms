<?php
/**
 * Create Storage Symlink Script - Fixed Version
 * Run: php create-storage-link-fixed.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$target = __DIR__ . '/storage/app/public';
$link = __DIR__ . '/public/storage';

echo "=== Storage Symlink Creation Script ===\n\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Target: $target\n";
echo "Link: $link\n\n";

// Step 1: Check if target exists
echo "Step 1: Checking target directory...\n";
if (!is_dir($target)) {
    echo "✗ Target directory does NOT exist: $target\n";
    echo "Creating target directory...\n";
    if (!mkdir($target, 0755, true)) {
        $error = error_get_last();
        die("✗ Failed to create target directory: " . ($error['message'] ?? 'Unknown error') . "\n");
    }
    echo "✓ Target directory created.\n";
} else {
    echo "✓ Target directory exists.\n";
    echo "  Permissions: " . substr(sprintf('%o', fileperms($target)), -4) . "\n";
    echo "  Writable: " . (is_writable($target) ? 'Yes' : 'No') . "\n";
}

// Step 2: Check if link already exists
echo "\nStep 2: Checking if link already exists...\n";
if (file_exists($link)) {
    echo "⚠ Link already exists.\n";
    if (is_link($link)) {
        echo "  It is a symlink pointing to: " . readlink($link) . "\n";
        echo "  Removing existing symlink...\n";
        if (unlink($link)) {
            echo "✓ Removed existing symlink.\n";
        } else {
            $error = error_get_last();
            die("✗ Failed to remove existing symlink: " . ($error['message'] ?? 'Unknown error') . "\n");
        }
    } elseif (is_dir($link)) {
        echo "  It is a directory (not a symlink).\n";
        // Check if it's empty
        $files = scandir($link);
        $fileCount = count(array_filter($files, function($f) { return $f !== '.' && $f !== '..'; }));
        if ($fileCount > 0) {
            echo "  ⚠ Directory is NOT empty ($fileCount items). Cannot remove automatically.\n";
            echo "  Please manually remove: $link\n";
            echo "  Command: rm -rf " . escapeshellarg($link) . "\n";
            exit(1);
        } else {
            echo "  Removing empty directory...\n";
            if (rmdir($link)) {
                echo "✓ Removed existing directory.\n";
            } else {
                $error = error_get_last();
                die("✗ Failed to remove directory: " . ($error['message'] ?? 'Unknown error') . "\n");
            }
        }
    } else {
        echo "  Removing existing file...\n";
        if (unlink($link)) {
            echo "✓ Removed existing file.\n";
        } else {
            $error = error_get_last();
            die("✗ Failed to remove file: " . ($error['message'] ?? 'Unknown error') . "\n");
        }
    }
} else {
    echo "✓ No existing link found.\n";
}

// Step 3: Check parent directory permissions
echo "\nStep 3: Checking parent directory permissions...\n";
$parentDir = dirname($link);
if (!is_dir($parentDir)) {
    echo "✗ Parent directory does NOT exist: $parentDir\n";
    echo "Creating parent directory...\n";
    if (!mkdir($parentDir, 0755, true)) {
        $error = error_get_last();
        die("✗ Failed to create parent directory: " . ($error['message'] ?? 'Unknown error') . "\n");
    }
    echo "✓ Parent directory created.\n";
} else {
    echo "✓ Parent directory exists: $parentDir\n";
    echo "  Permissions: " . substr(sprintf('%o', fileperms($parentDir)), -4) . "\n";
    echo "  Writable: " . (is_writable($parentDir) ? 'Yes' : 'No') . "\n";
    
    if (!is_writable($parentDir)) {
        echo "\n⚠ WARNING: Parent directory is NOT writable!\n";
        echo "You may need to change permissions:\n";
        echo "chmod 755 " . escapeshellarg($parentDir) . "\n";
    }
}

// Step 4: Get absolute paths
echo "\nStep 4: Resolving absolute paths...\n";
$targetAbsolute = realpath($target);
if (!$targetAbsolute) {
    die("✗ Error: Could not resolve target path: $target\n");
}
echo "✓ Target absolute path: $targetAbsolute\n";

$linkAbsolute = realpath(dirname($link)) . '/' . basename($link);
echo "✓ Link absolute path: $linkAbsolute\n";

// Step 5: Create the symlink
echo "\nStep 5: Creating symlink...\n";
echo "Command: symlink('$targetAbsolute', '$linkAbsolute')\n";

// Clear any previous errors
error_clear_last();

if (symlink($targetAbsolute, $linkAbsolute)) {
    echo "✓ Symlink created successfully!\n";
} else {
    $error = error_get_last();
    echo "✗ Failed to create symlink!\n";
    if ($error) {
        echo "Error message: " . $error['message'] . "\n";
        echo "Error file: " . $error['file'] . "\n";
        echo "Error line: " . $error['line'] . "\n";
    } else {
        echo "No error message available (check permissions)\n";
    }
    
    echo "\nTroubleshooting steps:\n";
    echo "1. Check if symlink() function is enabled:\n";
    echo "   php -r \"var_dump(function_exists('symlink'));\"\n";
    echo "\n2. Try creating manually via SSH:\n";
    echo "   ln -s " . escapeshellarg($targetAbsolute) . " " . escapeshellarg($linkAbsolute) . "\n";
    echo "\n3. Check file permissions:\n";
    echo "   ls -la " . escapeshellarg(dirname($linkAbsolute)) . "\n";
    echo "   ls -la " . escapeshellarg($targetAbsolute) . "\n";
    exit(1);
}

// Step 6: Verify the symlink
echo "\nStep 6: Verifying symlink...\n";
if (file_exists($linkAbsolute)) {
    if (is_link($linkAbsolute)) {
        $readLink = readlink($linkAbsolute);
        echo "✓ Symlink exists\n";
        echo "  Points to: $readLink\n";
        
        if ($readLink === $targetAbsolute || realpath($linkAbsolute) === $targetAbsolute) {
            echo "✓ Symlink points to correct location\n";
            
            if (is_dir($linkAbsolute)) {
                echo "✓ Target is accessible through symlink\n";
                $files = glob($linkAbsolute . '/*');
                $fileCount = count($files);
                echo "  Files/directories found: $fileCount\n";
                
                if ($fileCount > 0) {
                    echo "\n  Sample contents:\n";
                    $sample = array_slice($files, 0, 5);
                    foreach ($sample as $file) {
                        echo "    - " . basename($file) . "\n";
                    }
                }
            }
            
            echo "\n";
            echo "✓✓✓ SUCCESS! ✓✓✓\n";
            echo "\nYou can now access files at:\n";
            echo "https://courses.tazkiyahtarbiyah.com/storage/\n";
        } else {
            echo "⚠ Warning: Symlink points to different location\n";
            echo "  Expected: $targetAbsolute\n";
            echo "  Actual: $readLink\n";
        }
    } else {
        echo "⚠ Warning: File exists but is not a symlink\n";
    }
} else {
    echo "✗ Symlink was not created or is not accessible\n";
}
