<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search lessons by transcript text and (secondarily) lesson title.
     *
     * @return array<int, array{
     *   lesson_id:int,
     *   course_id:int,
     *   lesson_title:string,
     *   course_title:string,
     *   matches:array<int, array{start_seconds:int,snippet_text:string}>
     * }>
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        // Base transcript query
        $base = DB::table('lesson_transcript_segments as seg')
            ->join('lessons as l', 'l.id', '=', 'seg.lesson_id')
            ->join('modules as m', 'm.id', '=', 'l.module_id')
            ->join('courses as c', 'c.id', '=', 'm.course_id');

        if ($driver === 'mysql') {
            // Prefer FULLTEXT if available
            $base->whereRaw('MATCH(seg.text) AGAINST (? IN BOOLEAN MODE)', [$query]);
            $base->orderByDesc(DB::raw('MATCH(seg.text) AGAINST ("'.$query.'" IN BOOLEAN MODE)'));
        } else {
            $like = '%' . $this->escapeLike($query) . '%';
            $base->where('seg.text', 'like', $like);
        }

        $rows = $base
            ->orderBy('l.id')
            ->orderBy('seg.start_seconds')
            ->limit(200)
            ->get([
                'l.id as lesson_id',
                'c.id as course_id',
                'l.title as lesson_title',
                'c.title as course_title',
                'seg.text as segment_text',
                'seg.start_seconds as start_seconds',
            ]);

        // Group by lesson with max 3 matches per lesson and 20 lessons total
        $grouped = [];

        foreach ($rows as $row) {
            $lessonId = (int) $row->lesson_id;

            if (! isset($grouped[$lessonId])) {
                if (count($grouped) >= 20) {
                    // Reached lesson cap, skip any new lessons
                    continue;
                }

                $grouped[$lessonId] = [
                    'lesson_id' => $lessonId,
                    'course_id' => (int) $row->course_id,
                    'lesson_title' => (string) $row->lesson_title,
                    'course_title' => (string) $row->course_title,
                    'matches' => [],
                ];
            }

            if (count($grouped[$lessonId]['matches']) >= 3) {
                continue;
            }

            $startSeconds = (int) floor((float) $row->start_seconds);
            $snippet = $this->makeSnippet((string) $row->segment_text, $query, 140);

            $grouped[$lessonId]['matches'][] = [
                'start_seconds' => $startSeconds,
                'snippet_text' => $snippet,
            ];
        }

        // Also allow lesson title-only matches for discoverability
        $like = '%' . $this->escapeLike($query) . '%';
        $titleRows = DB::table('lessons as l')
            ->join('modules as m', 'm.id', '=', 'l.module_id')
            ->join('courses as c', 'c.id', '=', 'm.course_id')
            ->where('l.title', 'like', $like)
            ->orderBy('c.id')
            ->orderBy('m.sort_order')
            ->orderBy('l.sort_order')
            ->limit(50)
            ->get([
                'l.id as lesson_id',
                'c.id as course_id',
                'l.title as lesson_title',
                'c.title as course_title',
            ]);

        foreach ($titleRows as $row) {
            $lessonId = (int) $row->lesson_id;
            if (isset($grouped[$lessonId])) {
                continue; // already have transcript matches
            }
            if (count($grouped) >= 20) {
                break;
            }

            $title = (string) $row->lesson_title;
            $grouped[$lessonId] = [
                'lesson_id' => $lessonId,
                'course_id' => (int) $row->course_id,
                'lesson_title' => $title,
                'course_title' => (string) $row->course_title,
                'matches' => [[
                    'start_seconds' => 0,
                    'snippet_text' => $this->makeSnippet($title, $query, 140),
                ]],
            ];
        }

        return array_values($grouped);
    }

    private function escapeLike(string $value): string
    {
        // Escape LIKE wildcard characters for consistent behavior.
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function makeSnippet(string $text, string $needle, int $maxLen = 140): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') {
            return '';
        }

        $pos = mb_stripos($text, $needle);
        if ($pos === false) {
            return mb_strlen($text) <= $maxLen ? $text : (mb_substr($text, 0, $maxLen - 1) . '…');
        }

        $half = (int) floor($maxLen / 2);
        $start = max(0, (int) $pos - $half);
        $snippet = mb_substr($text, $start, $maxLen);

        $prefix = $start > 0 ? '…' : '';
        $suffix = ($start + $maxLen) < mb_strlen($text) ? '…' : '';

        return $prefix . trim($snippet) . $suffix;
    }
}

