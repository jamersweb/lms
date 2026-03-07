<?php
/**
 * Database export – single file. Upload to your server root (or public folder).
 * Open in browser: https://yoursite.com/export-database.php?token=YOUR_SECRET
 *
 * 1. Set $export_token below (or use .env from parent folder).
 * 2. Upload this file to the web root / public folder.
 * 3. Visit the URL with ?token=YOUR_SECRET to download the SQL file.
 */
error_reporting(0);
ini_set('display_errors', 0);

// -------- Optional: load .env from parent (e.g. Laravel root) --------
$envPath = __DIR__ . '/../.env';
if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            $key = trim($m[1]);
            $val = trim($m[2], " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) putenv("$key=$val");
        }
    }
}

$export_token = getenv('EXPORT_TOKEN') ?: 'change_me_123';  // set in .env as EXPORT_TOKEN=your_secret or edit here
$db_host     = getenv('DB_HOST') ?: '127.0.0.1';
$db_port     = getenv('DB_PORT') ?: '3306';
$db_name     = getenv('DB_DATABASE') ?: 'laravel';
$db_user     = getenv('DB_USERNAME') ?: 'root';
$db_pass     = getenv('DB_PASSWORD') ?: '';

// -------- Security: require token --------
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';
if ($token === '' || !hash_equals($export_token, $token)) {
    while (ob_get_level()) ob_end_clean();
    header('HTTP/1.0 403 Forbidden');
    echo 'Forbidden. Use ?token=YOUR_SECRET';
    exit;
}

// -------- Connect --------
try {
    $dsn = "mysql:host=" . $db_host . ";port=" . $db_port . ";dbname=" . $db_name . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain');
    echo 'Database connection failed: ' . $e->getMessage();
    exit;
}

// Clear any output so download headers work (no output before this)
while (ob_get_level()) ob_end_clean();
set_time_limit(0);

$filename = 'database-' . date('Y-m-d_His') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

function out($s) {
    echo $s;
    if (ob_get_level()) ob_flush();
    flush();
}

out("-- Export: " . $db_name . " at " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n");

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$dbName = $db_name;

foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `" . str_replace('`', '``', $table) . "`")->fetch(PDO::FETCH_ASSOC);
    if (!empty($create['Create Table'])) {
        out($create['Create Table'] . ";\n\n");
    }
    $count = $pdo->query("SELECT COUNT(*) FROM `" . str_replace('`', '``', $table) . "`")->fetchColumn();
    if ($count == 0) {
        out("-- {$table}: 0 rows\n\n");
        continue;
    }
    out("-- {$table}: {$count} rows\n");
    $colNames = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "`")->fetchAll(PDO::FETCH_COLUMN);
    $colList = implode(',', array_map(function ($c) { return '`' . str_replace(['`', '\\'], ['``', '\\\\'], $c) . '`'; }, $colNames));
    $tableEsc = '`' . str_replace(['`', '\\'], ['``', '\\\\'], $table) . '`';
    $chunk = 300;
    $offset = 0;
    while (true) {
        $stmt = $pdo->prepare("SELECT * FROM `" . str_replace('`', '``', $table) . "` LIMIT " . $chunk . " OFFSET " . $offset);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) break;
        $vals = [];
        foreach ($rows as $row) {
            $v = [];
            foreach ($colNames as $col) {
                $x = $row[$col] ?? null;
                $v[] = $x === null ? 'NULL' : $pdo->quote($x);
            }
            $vals[] = '(' . implode(',', $v) . ')';
        }
        out("INSERT INTO {$tableEsc} ({$colList}) VALUES\n" . implode(",\n", $vals) . ";\n");
        $offset += $chunk;
        if (count($rows) < $chunk) break;
    }
    out("\n");
}

out("SET FOREIGN_KEY_CHECKS=1;\n");
