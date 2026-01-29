<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

class CertificateService
{
    public function awardCertificate(User $user, string $type, ?Course $course = null, ?string $level = null): Certificate
    {
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course?->id,
            'type' => $type,
            'level' => $level,
            'certificate_number' => Certificate::generateCertificateNumber(),
            'issued_at' => now(),
            'metadata' => [
                'user_name' => $user->name,
                'course_title' => $course?->title,
                'issued_date' => now()->toDateString(),
            ],
        ]);

        // Generate PDF asynchronously or on-demand
        // For now, we'll generate on first download request

        return $certificate;
    }

    public function generatePdf(Certificate $certificate): Certificate
    {
        $user = $certificate->user;
        $course = $certificate->course;

        $data = [
            'certificate_number' => $certificate->certificate_number,
            'user_name' => $user->name,
            'course_title' => $course?->title ?? 'Learning Journey',
            'type' => $certificate->type,
            'level' => $certificate->level,
            'issued_date' => $certificate->issued_at->format('F j, Y'),
        ];

        $pdf = DomPDF::loadView('certificates.pdf', $data);

        $filename = "certificates/{$certificate->id}-{$certificate->certificate_number}.pdf";
        Storage::put($filename, $pdf->output());

        $certificate->update(['pdf_path' => $filename]);

        return $certificate;
    }
}
