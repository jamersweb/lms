<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonResource;
use App\Services\AuthorizationService;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LessonResourceController extends Controller
{
    /**
     * Export lesson resources (Sunnah & Dua) as PDF
     */
    public function exportPdf(Lesson $lesson)
    {
        $user = auth()->user();
        $authorizationService = app(AuthorizationService::class);
        
        // Standardized authorization check (requires completion)
        $authorizationService->ensureLessonResourceAccess($user, $lesson, true);

        // Eager load resource to avoid N+1
        $lesson->load('resource');
        $resource = $lesson->resource;

        if (!$resource) {
            abort(404, 'No resources available for this lesson.');
        }

        // Serve uploaded PDF if present (takes priority over generated PDF)
        if ($resource->pdf_path && Storage::disk('public')->exists($resource->pdf_path)) {
            $filePath = Storage::disk('public')->path($resource->pdf_path);
            $filename = "lesson-resources-{$lesson->slug}.pdf";
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        if (!$resource->sunnah_pointers && !$resource->duas_text) {
            abort(404, 'No resources available for this lesson.');
        }

        // Cache key based on resource updated_at timestamp
        $cacheKey = "lesson-resource-pdf-{$lesson->id}-" . ($resource->updated_at ? $resource->updated_at->timestamp : '0');
        
        // Check if PDF is cached
        $cachedPdfPath = Cache::get($cacheKey);
        if ($cachedPdfPath && Storage::disk('local')->exists($cachedPdfPath)) {
            $filePath = Storage::disk('local')->path($cachedPdfPath);
            $filename = "lesson-resources-{$lesson->slug}.pdf";
            
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Generate PDF from Sunnah/Dua content
        $pdf = DomPDF::loadView('lesson-resources.pdf', [
            'lesson' => $lesson,
            'resource' => $resource,
        ]);

        // Store PDF in cache
        $storagePath = "lesson-resources/pdfs/{$lesson->id}-{$lesson->slug}.pdf";
        $pdfContent = $pdf->output();
        
        // Ensure directory exists
        $directory = storage_path('app/lesson-resources/pdfs');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        Storage::disk('local')->put($storagePath, $pdfContent);
        
        // Cache the path for 24 hours
        Cache::put($cacheKey, $storagePath, now()->addHours(24));

        $filename = "lesson-resources-{$lesson->slug}-" . now()->format('Y-m-d') . '.pdf';

        return response()->streamDownload(function() use ($pdfContent) {
            echo $pdfContent;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Get lesson resources for API/JSON response
     */
    public function show(Lesson $lesson)
    {
        $user = auth()->user();
        $authorizationService = app(AuthorizationService::class);
        $contentLocale = $user?->content_locale ?? app()->getLocale();
        
        // Standardized authorization check (doesn't require completion for viewing)
        try {
            $authorizationService->ensureLessonResourceAccess($user, $lesson, false);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return response()->json([
                'error' => 'You do not have access to this lesson.'
            ], 403);
        }

        // Check completion status
        $progress = $user->lessonProgress()
            ->where('lesson_id', $lesson->id)
            ->first();

        // If not completed, still allow viewing but show a message
        $canView = $progress && $progress->is_completed;

        // Eager load resource
        $lesson->load('resource');
        $resource = $lesson->resource;

        if (!$resource) {
            return response()->json([
                'sunnah_pointers' => null,
                'duas_text' => null,
                'audio_path' => null,
                'can_view' => $canView,
            ]);
        }

        return response()->json([
            'sunnah_pointers' => $resource->getLocalizedSunnahPointers($contentLocale),
            'duas_text' => $resource->getLocalizedDuasText($contentLocale),
            'audio_path' => $resource->audio_path ? asset('storage/' . $resource->audio_path) : null,
            'can_view' => $canView,
        ]);
    }

    /**
     * View PDF in browser (for iframe embedding)
     */
    public function viewPdf(Lesson $lesson)
    {
        $user = auth()->user();
        $authorizationService = app(AuthorizationService::class);
        
        // Standardized authorization check (requires completion)
        $authorizationService->ensureLessonResourceAccess($user, $lesson, true);

        // Eager load resource to avoid N+1
        $lesson->load('resource');
        $resource = $lesson->resource;

        if (!$resource) {
            abort(404, 'No resources available for this lesson.');
        }

        // Serve uploaded PDF if present (takes priority over generated PDF)
        if ($resource->pdf_path && Storage::disk('public')->exists($resource->pdf_path)) {
            $filePath = Storage::disk('public')->path($resource->pdf_path);
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="lesson-resources-' . $lesson->slug . '.pdf"',
            ]);
        }

        if (!$resource->sunnah_pointers && !$resource->duas_text) {
            abort(404, 'No resources available for this lesson.');
        }

        // Cache key based on resource updated_at timestamp
        $cacheKey = "lesson-resource-pdf-{$lesson->id}-" . ($resource->updated_at ? $resource->updated_at->timestamp : '0');
        
        // Check if PDF is cached
        $cachedPdfPath = Cache::get($cacheKey);
        if ($cachedPdfPath && Storage::disk('local')->exists($cachedPdfPath)) {
            $filePath = Storage::disk('local')->path($cachedPdfPath);
            
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="lesson-resources-' . $lesson->slug . '.pdf"',
            ]);
        }

        // Generate PDF from Sunnah/Dua content
        $pdf = DomPDF::loadView('lesson-resources.pdf', [
            'lesson' => $lesson,
            'resource' => $resource,
        ]);

        // Store PDF in cache
        $storagePath = "lesson-resources/pdfs/{$lesson->id}-{$lesson->slug}.pdf";
        $pdfContent = $pdf->output();
        
        // Ensure directory exists
        $directory = storage_path('app/lesson-resources/pdfs');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        Storage::disk('local')->put($storagePath, $pdfContent);
        
        // Cache the path for 24 hours
        Cache::put($cacheKey, $storagePath, now()->addHours(24));

        // Return PDF for inline viewing (not download)
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lesson-resources-' . $lesson->slug . '.pdf"',
        ]);
    }
}
