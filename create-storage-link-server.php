<?php
/**
 * Create Storage Symlink Script for Linux Server
 * Run this file directly: php create-storage-link-server.php
 * 
 * This script creates the storage symlink without requiring exec() function
 */

$target = __DIR__ . '/storage/app/public';
$link = __DIR__ . '/public/storage';

// Check if target exists
if (!is_dir($target)) {
    echo "Error: Target directory does not exist: $target\n";
    echo "Creating target directory...\n";
    if (!mkdir($target, 0755, true)) {
        die("Failed to create target directory.\n");
    }
    echo "✓ Target directory created.\n";
}

// Remove existing link/directory if it exists
if (file_exists($link) || is_dir($link)) {
    echo "Removing existing link/directory...\n";
    if (is_link($link)) {
        if (unlink($link)) {
            echo "✓ Removed existing symlink.\n";
        } else {
            echo "✗ Failed to remove existing symlink.\n";
            exit(1);
        }
    } elseif (is_dir($link)) {
        // Try to remove directory (should be empty if it's a broken symlink)
        if (rmdir($link)) {
            echo "✓ Removed existing directory.\n";
        } else {
            echo "⚠ Warning: Could not remove existing directory. It may not be empty.\n";
            echo "Please manually remove: $link\n";
            exit(1);
        }
    } else {
        if (unlink($link)) {
            echo "✓ Removed existing file.\n";
        }
    }
}

// Create the symlink using PHP's symlink() function
echo "Creating symlink from $link to $target...\n";

$targetAbsolute = realpath($target);
if (!$targetAbsolute) {
    echo "✗ Error: Could not resolve target path: $target\n";
    exit(1);
}

// Suppress errors and check manually
$oldErrorReporting = error_reporting(E_ALL);
$oldDisplayErrors = ini_set('display_errors', '1');

if (@symlink($targetAbsolute, $link)) {
    echo "✓ Symlink created successfully!\n";
} else {
    $error = error_get_last();
    echo "✗ Failed to create symlink.\n";
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
    } else {
        echo "Error: Unknown error (check permissions)\n";
    }
    echo "\nTroubleshooting:\n";
    echo "1. Check if public/storage directory exists and is writable\n";
    echo "2. Check if storage/app/public directory exists\n";
    echo "3. Try creating manually via SSH:\n";
    echo "   ln -s " . escapeshellarg($targetAbsolute) . " " . escapeshellarg($link) . "\n";
    error_reporting($oldErrorReporting);
    ini_set('display_errors', $oldDisplayErrors);
    exit(1);
}

error_reporting($oldErrorReporting);
ini_set('display_errors', $oldDisplayErrors);

// Verify the link works
echo "\nVerifying symlink...\n";
if (file_exists($link)) {
    if (is_link($link)) {
        $readLink = readlink($link);
        echo "✓ Symlink exists and points to: $readLink\n";
        if ($readLink === $targetAbsolute || realpath($link) === $targetAbsolute) {
            echo "✓ Symlink verified and working!\n";
            if (is_dir($link)) {
                echo "✓ Target directory is accessible\n";
                $fileCount = count(glob($link . '/*'));
                echo "Files found in storage: $fileCount\n";
            }
            echo "\n✓✓✓ SUCCESS! You can now access files at:\n";
            echo "https://courses.tazkiyahtarbiyah.com/storage/\n";
        } else {
            echo "⚠ Warning: Symlink points to different location\n";
            echo "Expected: $targetAbsolute\n";
            echo "Actual: $readLink\n";
        }
    } else {
        echo "⚠ Warning: $link exists but is not a symlink\n";
    }
} else {
    echo "✗ Symlink was not created or is not accessible\n";
}
