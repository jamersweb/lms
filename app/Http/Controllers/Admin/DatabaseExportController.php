<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseExportController extends Controller
{
    public function __invoke(DatabaseExportService $exporter): StreamedResponse
    {
        $filename = 'database-' . now()->format('Y-m-d_His') . '.sql';

        return response()->streamDownload(
            function () use ($exporter) {
                $exporter->exportToStream(function (string $chunk) {
                    echo $chunk;
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                });
            },
            $filename,
            [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
