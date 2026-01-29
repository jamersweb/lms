<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $certificates = Certificate::where('user_id', $user->id)
            ->with('course')
            ->latest('issued_at')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'type' => $cert->type,
                    'level' => $cert->level,
                    'certificate_number' => $cert->certificate_number,
                    'issued_at' => $cert->issued_at->format('F j, Y'),
                    'course_title' => $cert->course?->title ?? 'N/A',
                    'can_download' => !empty($cert->pdf_path),
                ];
            });

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
        ]);
    }

    public function download(Certificate $certificate)
    {
        $user = auth()->user();

        abort_unless($certificate->user_id === $user->id || $user->is_admin, 403);

        if (!$certificate->pdf_path) {
            // Generate PDF if not exists
            $service = new CertificateService();
            $certificate = $service->generatePdf($certificate);
        }

        $filePath = Storage::disk('local')->path($certificate->pdf_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'Certificate PDF not found');
        }

        return response()->download(
            $filePath,
            "certificate-{$certificate->certificate_number}.pdf"
        );
    }
}
