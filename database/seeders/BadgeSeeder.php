<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'First Lesson',
                'slug' => 'first-lesson',
                'description' => 'Completed your first lesson.',
                'icon' => 'book-open',
                'criteria' => [
                    'type' => 'event_count',
                    'event_type' => 'lesson_completed',
                    'count' => 1,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Top Learner',
                'slug' => 'top-learner',
                'description' => 'Earned 500 points.',
                'icon' => 'trophy',
                'criteria' => [
                    'type' => 'points',
                    'count' => 500,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Helper',
                'slug' => 'helper',
                'description' => 'Replied to 10 discussions.',
                'icon' => 'message-circle',
                'criteria' => [
                    'type' => 'event_count',
                    'event_type' => 'reply_created',
                    'count' => 10,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($badges as $badge) {
            \App\Models\Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
