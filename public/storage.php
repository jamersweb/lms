<?php
/**
 * Fallback storage file server for shared hosting where symlink() is disabled.
 * .htaccess rewrites /storage/* to this script so files in storage/app/public are still accessible.
 *
 * Usage: Ensure .htaccess contains the rewrite rule for ^storage/(.*) -> storage.php?path=$1
 */

$path = isset($_GET['path']) ? (string) $_GET['path'] : '';

// Block directory traversal and null bytes
if ($path === '' || strpos($path, '..') !== false || strpos($path, "\0") !== false) {
    http_response_code(400);
    exit('Invalid path');
}

$basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public';
$requestedFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
$fullPath = realpath($basePath . DIRECTORY_SEPARATOR . $requestedFile) ?: '';
$baseReal = realpath($basePath) ?: '';

// Ensure resolved path is inside storage/app/public (no directory traversal)
$baseReal = rtrim($baseReal, DIRECTORY_SEPARATOR);
$allowed = $fullPath !== '' && $baseReal !== '' && ($fullPath === $baseReal || strpos($fullPath, $baseReal . DIRECTORY_SEPARATOR) === 0);
if (!$allowed) {
    http_response_code(404);
    exit('Not found');
}

if (!is_file($fullPath)) {
    http_response_code(404);
    exit('Not found');
}

$mimes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'pdf' => 'application/pdf',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mp3' => 'audio/mpeg',
    'json' => 'application/json',
];
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=31536000');
readfile($fullPath);
