<?php
/**
 * Diagnostic Script: Check Lesson Video Data
 * 
 * Run this on your production server to check video data for lessons:
 * php check-lesson-videos.php [lesson_id]
 * 
 * If no lesson_id is provided, it will check all lessons.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lesson;

$lessonId = $argv[1] ?? null;

if ($lessonId) {
    $lessons = Lesson::where('id', $lessonId)->get();
} else {
    $lessons = Lesson::all();
}

echo "\n=== Lesson Video Data Check ===\n\n";

foreach ($lessons as $lesson) {
    echo "Lesson ID: {$lesson->id}\n";
    echo "Title: {$lesson->title}\n";
    echo "Video Provider: {$lesson->video_provider}\n";
    echo "YouTube Video ID: " . ($lesson->youtube_video_id ?: 'NULL') . "\n";
    echo "External Video URL: " . ($lesson->external_video_url ?: 'NULL') . "\n";
    
    // Build expected video URL
    $expectedUrl = null;
    switch ($lesson->video_provider) {
        case 'youtube':
            if ($lesson->youtube_video_id) {
                $expectedUrl = 'https://www.youtube.com/embed/' . $lesson->youtube_video_id;
            }
            break;
        case 'external':
            $expectedUrl = $lesson->external_video_url;
            break;
        case 'mp4':
            if ($lesson->video_path) {
                $expectedUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($lesson->video_path);
            }
            break;
    }
    
    echo "Expected Video URL: " . ($expectedUrl ?: 'NULL') . "\n";
    
    // Check for inconsistencies
    $issues = [];
    if ($lesson->video_provider === 'youtube' && !$lesson->youtube_video_id) {
        $issues[] = "⚠ Provider is 'youtube' but youtube_video_id is empty";
    }
    if ($lesson->video_provider === 'external' && !$lesson->external_video_url) {
        $issues[] = "⚠ Provider is 'external' but external_video_url is empty";
    }
    if ($lesson->video_provider === 'youtube' && $lesson->external_video_url) {
        $issues[] = "⚠ Provider is 'youtube' but external_video_url is set: {$lesson->external_video_url}";
    }
    if ($lesson->youtube_video_id && $lesson->external_video_url && strpos($lesson->external_video_url, 'youtube') !== false) {
        $issues[] = "⚠ Both youtube_video_id and external_video_url contain YouTube URLs - may cause confusion";
    }
    
    if (!empty($issues)) {
        echo "\nIssues Found:\n";
        foreach ($issues as $issue) {
            echo "  {$issue}\n";
        }
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
}

echo "Total lessons checked: " . $lessons->count() . "\n";
echo "\nTo fix a specific lesson, update it in the admin panel or database.\n";
