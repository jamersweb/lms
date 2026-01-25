<?php

namespace App\Services;

class TranscriptParser
{
    public function parse(string $content): array
    {
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        
        $segments = [];
        $currentSegment = null;
        
        // Simple state machine
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if ($line === 'WEBVTT') continue;
            if (is_numeric($line)) continue; // SRT index

            // Timestamp line: 00:00:01.000 --> 00:00:04.000
            if (preg_match('/^(\d{2}:\d{2}:\d{2}[.,]\d{3})\s-->\s(\d{2}:\d{2}:\d{2}[.,]\d{3})/', $line, $matches)) {
                if ($currentSegment) {
                    $segments[] = $currentSegment;
                }
                $currentSegment = [
                    'start_seconds' => $this->timeToSeconds($matches[1]),
                    'end_seconds' => $this->timeToSeconds($matches[2]),
                    'text' => '',
                ];
            } elseif ($currentSegment) {
                $currentSegment['text'] .= ($currentSegment['text'] ? ' ' : '') . $line;
            }
        }
        
        if ($currentSegment) {
            $segments[] = $currentSegment;
        }

        return $segments;
    }

    protected function timeToSeconds(string $timeString): float
    {
        // Format: HH:MM:SS.mmm or HH:MM:SS,mmm
        $timeString = str_replace(',', '.', $timeString);
        $parts = explode(':', $timeString);
        
        $hours = (float) $parts[0];
        $minutes = (float) $parts[1];
        $seconds = (float) $parts[2];
        
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}
