<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseExportService
{
    /**
     * Export database to SQL (MySQL/MariaDB). Streams to a callback to avoid memory issues.
     */
    public function exportToStream(callable $write): void
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            $write("-- Unsupported driver: {$connection}. Use MySQL/MariaDB or export manually.\n");
            return;
        }

        $write("-- Database export: " . config('database.connections.' . $driver . '.database') . "\n");
        $write("-- Generated: " . now()->toDateTimeString() . "\n\n");
        $write("SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $this->getTables($driver);
        foreach ($tables as $table) {
            $create = $this->getCreateTable($table, $driver);
            if ($create) {
                $write($create . ";\n\n");
            }
            $this->streamTableData($table, $driver, $write);
        }

        $write("SET FOREIGN_KEY_CHECKS=1;\n");
    }

    /**
     * Export to a file path. Returns the path.
     */
    public function exportToFile(?string $dir = null): string
    {
        $dir = $dir ?? storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir . '/db-' . now()->format('Y-m-d_His') . '.sql';
        $fp = fopen($path, 'w');
        $this->exportToStream(function (string $chunk) use ($fp) {
            fwrite($fp, $chunk);
        });
        fclose($fp);
        return $path;
    }

    protected function getTables(string $connection): array
    {
        $db = config("database.connections.{$connection}.database");
        $rows = DB::connection($connection)->select('SHOW TABLES');
        $key = 'Tables_in_' . $db;
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = $row->$key;
        }
        return $tables;
    }

    protected function getCreateTable(string $table, string $connection): ?string
    {
        $tableEsc = '`' . str_replace(['`', '\\'], ['``', '\\\\'], $table) . '`';
        $rows = DB::connection($connection)->select("SHOW CREATE TABLE {$tableEsc}");
        $first = $rows[0] ?? null;
        if (! $first) {
            return null;
        }
        // MySQL returns column "Create Table" (with space)
        return $first->{'Create Table'} ?? null;
    }

    protected function streamTableData(string $table, string $connection, callable $write): void
    {
        $count = DB::connection($connection)->table($table)->count();
        if ($count === 0) {
            $write("-- Table {$table}: 0 rows\n\n");
            return;
        }

        $write("-- Table {$table}: {$count} rows\n");
        $chunkSize = 500;
        $cols = null;
        $offset = 0;

        while (true) {
            $rows = DB::connection($connection)->table($table)->offset($offset)->limit($chunkSize)->get();
            if ($rows->isEmpty()) {
                break;
            }
            if ($cols === null) {
                $cols = array_keys((array) $rows->first());
            }
            $insert = $this->buildInsert($table, $cols, $rows, $connection);
            $write($insert);
            $offset += $chunkSize;
            if ($rows->count() < $chunkSize) {
                break;
            }
        }
        $write("\n");
    }

    protected function buildInsert(string $table, array $cols, $rows, string $connection): string
    {
        $pdo = DB::connection($connection)->getPdo();
        $tableEsc = '`' . str_replace(['`', '\\'], ['``', '\\\\'], $table) . '`';
        $colList = implode(',', array_map(function ($c) {
            return '`' . str_replace(['`', '\\'], ['``', '\\\\'], $c) . '`';
        }, $cols));

        $lines = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $vals = [];
            foreach ($cols as $col) {
                $v = $row[$col] ?? null;
                if ($v === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = $pdo->quote((string) $v);
                }
            }
            $lines[] = '(' . implode(',', $vals) . ')';
        }
        return "INSERT INTO {$tableEsc} ({$colList}) VALUES\n" . implode(",\n", $lines) . ";\n";
    }
}
