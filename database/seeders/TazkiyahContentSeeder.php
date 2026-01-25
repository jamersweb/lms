<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TazkiyahContentSeeder extends Seeder
{
    /**
     * Seed Tazkiyah Tarbiyah content as courses.
     * Content sourced from: https://tazkiyahtarbiyah.com/videos/
     */
    public function run(): void
    {
        $courses = $this->getCourseData();
        
        foreach ($courses as $courseData) {
            $course = Course::create([
                'title' => $courseData['title'],
                'slug' => Str::slug($courseData['title']),
                'description' => $courseData['description'],
                'instructor' => $courseData['instructor'],
                'level' => $courseData['level'],
                'sort_order' => $courseData['sort_order'],
                'thumbnail' => $courseData['thumbnail'] ?? null,
            ]);

            foreach ($courseData['modules'] as $moduleIndex => $moduleData) {
                $module = Module::create([
                    'course_id' => $course->id,
                    'title' => $moduleData['title'],
                    'slug' => Str::slug($moduleData['title']),
                    'sort_order' => $moduleIndex + 1,
                ]);

                foreach ($moduleData['lessons'] as $lessonIndex => $lessonData) {
                    Lesson::create([
                        'module_id' => $module->id,
                        'title' => $lessonData['title'],
                        'slug' => Str::slug($lessonData['title']),
                        'video_provider' => 'external',
                        'external_video_url' => $lessonData['video_url'],
                        'duration_seconds' => $lessonData['duration'] ?? 900,
                        'sort_order' => $lessonIndex + 1,
                        'is_free_preview' => $lessonIndex === 0,
                    ]);
                }
            }

            $this->command->info("Created course: {$course->title}");
        }

        $this->command->info('');
        $this->command->info('Tazkiyah Tarbiyah content seeded successfully!');
        $this->command->info('Total courses created: ' . count($courses));
    }

    private function getCourseData(): array
    {
        return [
            // Course 1: Aqaid (Beliefs)
            [
                'title' => 'Aqaid - Islamic Beliefs',
                'description' => 'Fundamental Islamic beliefs and creed. Learn about the core tenets of faith including belief in Allah, His angels, books, messengers, and the Day of Judgment.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Beginner',
                'sort_order' => 1,
                'modules' => [
                    [
                        'title' => 'Foundations of Belief',
                        'lessons' => [
                            [
                                'title' => 'Hazraat e Sahaba Ikraam RA ka Ta\'aruf',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/hazraat-e-sahaba-ikraam-ra-ka-taaruf/',
                                'duration' => 1200
                            ],
                            [
                                'title' => 'Sunnat Tareeqah hi Wahid Rasta hai',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/sunnat-tareeqah-hi-wahid-rasta-hai/',
                                'duration' => 1500
                            ],
                        ],
                    ],
                ],
            ],

            // Course 2: Hamds & Naats
            [
                'title' => 'Hamds & Naats - Spiritual Poetry',
                'description' => 'Beautiful Islamic poetry praising Allah (Hamd) and the Prophet Muhammad (SAW) (Naat). Learn the spiritual dimensions of Islamic poetry.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Beginner',
                'sort_order' => 2,
                'modules' => [
                    [
                        'title' => 'Naatiya Kalam Collection',
                        'lessons' => [
                            [
                                'title' => 'Pyare Nabi S.A.W.W ki Ishq Mein (Urdu Lyrics)',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/pyare-nabi-s-a-w-w-ki-ishq-mein/',
                                'duration' => 600
                            ],
                            [
                                'title' => 'Aye Dil Muhammad ki Faryaad hojaa – Naatiya Kalam',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/aye-dil-muhammad-ki-faryaad-hojaa/',
                                'duration' => 720
                            ],
                        ],
                    ],
                ],
            ],

            // Course 3: Islah aur Tarbiyat
            [
                'title' => 'Islah aur Tarbiyat - Self-Reformation',
                'description' => 'Comprehensive guidance on self-reformation and spiritual development. Learn the path to purifying the soul and building character according to Islamic teachings.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Intermediate',
                'sort_order' => 3,
                'modules' => [
                    [
                        'title' => 'Understanding Ikhlas (Sincerity)',
                        'lessons' => [
                            [
                                'title' => 'Ikhlaas ki Haqeeqat kiya hai?',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-ki-haqeeqat-kiya-hai/',
                                'duration' => 1200
                            ],
                            [
                                'title' => 'Ikhlaas walay Amal ki Taaqat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-walay-amal-ki-taaqat/',
                                'duration' => 1100
                            ],
                            [
                                'title' => 'Ikhlaas wala Sadaqah',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-wala-sadaqah/',
                                'duration' => 900
                            ],
                            [
                                'title' => 'Ikhlaas ki Tareef',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-ki-tareef/',
                                'duration' => 800
                            ],
                        ],
                    ],
                    [
                        'title' => 'Spiritual Growth',
                        'lessons' => [
                            [
                                'title' => 'Kia ap Darakht say Afzal hain?',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/kia-ap-darakht-say-afzal-hain/',
                                'duration' => 1000
                            ],
                            [
                                'title' => 'Amal ki Qubooliyat ki Do Sharaait',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/amal-ki-qubooliyat-ki-do-sharaait/',
                                'duration' => 1300
                            ],
                        ],
                    ],
                ],
            ],

            // Course 4: Maqbool Ayaam - Muharram
            [
                'title' => 'Muharram - Ashura & Beyond',
                'description' => 'Understanding the sacred month of Muharram and the significance of Ashura. Learn about the historical events and spiritual practices of this blessed time.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Beginner',
                'sort_order' => 4,
                'modules' => [
                    [
                        'title' => 'Significance of Muharram',
                        'lessons' => [
                            [
                                'title' => 'Muharram al Haram ki Ahmiyat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/muharram-al-haram-ki-ahmiyat/',
                                'duration' => 1500
                            ],
                            [
                                'title' => 'Ashura ka Roza',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ashura-ka-roza/',
                                'duration' => 1200
                            ],
                        ],
                    ],
                ],
            ],

            // Course 5: Maqbool Ayaam - Ramadan
            [
                'title' => 'Ramadan - The Most Blessed Month',
                'description' => 'Complete guidance on maximizing the blessings of Ramadan. Learn about fasting, Tarawih, Laylatul Qadr, and maintaining taqwa throughout the year.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Beginner',
                'sort_order' => 5,
                'modules' => [
                    [
                        'title' => 'Ramadan Essentials',
                        'lessons' => [
                            [
                                'title' => 'Ramazan kay Ahkaam, Sunnatein aur Kaifiyaat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ramazan-kay-ahkaam-sunnatein-aur-kaifiyaat/',
                                'duration' => 1800
                            ],
                            [
                                'title' => 'Ramazan aur Tazkiyah Nafs',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ramazan-aur-tazkiyah-nafs/',
                                'duration' => 1600
                            ],
                            [
                                'title' => 'Ramazan kay baad Taqwa waali Zindagi',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/ramazan-kay-baad-taqwa-waali-zindagi/',
                                'duration' => 1400
                            ],
                        ],
                    ],
                    [
                        'title' => 'Laylatul Qadr',
                        'lessons' => [
                            [
                                'title' => 'Shab e Qadr – Laylatal ul Qadr',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/shab-e-qadr-laylatal-ul-qadr/',
                                'duration' => 1500
                            ],
                            [
                                'title' => 'Shab e Qadr Hasil karna Nihayat Asaan',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/shab-e-qadr-hasil-karna-nihayat-asaan/',
                                'duration' => 1200
                            ],
                            [
                                'title' => 'Zindagi ko Ramazan ki Tarah Guzarnay ka Tareeqah',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/zindagi-ko-ramazan-ki-tarah-guzarnay-ka-tareeqah/',
                                'duration' => 1300
                            ],
                        ],
                    ],
                ],
            ],

            // Course 6: Maqbool Ayaam - Zilhijjah
            [
                'title' => 'Zilhijjah - The Most Blessed Days',
                'description' => 'Learn about the sacred days of Zilhijjah, the rituals of Hajj, and the significance of Qurbani. Understand how to maximize worship during these blessed days.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Beginner',
                'sort_order' => 6,
                'modules' => [
                    [
                        'title' => 'Zilhijjah and Qurbani',
                        'lessons' => [
                            [
                                'title' => 'Zilhajjah kay pehlay Ashray ki Ahmiyat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/zilhajjah-kay-pehlay-ashray-ki-ahmiyat/',
                                'duration' => 1400
                            ],
                            [
                                'title' => 'Qurbani ki Fazilat or Hukum',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/qurbani-ki-fazilat-or-hukum/',
                                'duration' => 1600
                            ],
                            [
                                'title' => 'Auliya Allah ki Qurbani ki Kafiyat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/auliya-allah-ki-qurbani-ki-kafiyat/',
                                'duration' => 1200
                            ],
                        ],
                    ],
                ],
            ],

            // Course 7: Sunnah - Legacy of the Beloved
            [
                'title' => 'Sunnah - Legacy of the Beloved (SAW)',
                'description' => 'Comprehensive course on following the Sunnah of Prophet Muhammad (SAW) in daily life. Learn about etiquettes, manners, and practices from the Prophetic tradition.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Beginner',
                'sort_order' => 7,
                'modules' => [
                    [
                        'title' => 'Daily Sunnahs',
                        'lessons' => [
                            [
                                'title' => 'Azaan aur Aqamat ki Sunnatein',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/azaan-aur-aqamat-ki-sunnatein/',
                                'duration' => 1100
                            ],
                            [
                                'title' => 'Bait ul Khala janay ki Sunnatein',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/bait-ul-khala-janay-ki-sunnatein/',
                                'duration' => 900
                            ],
                            [
                                'title' => 'Jamaee ka Hukum or Aadaab',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/jamaee-ka-hukum-or-aadaab/',
                                'duration' => 1000
                            ],
                        ],
                    ],
                    [
                        'title' => 'Character & Conduct',
                        'lessons' => [
                            [
                                'title' => 'Hath aur Zabaan ki Hifazat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/hath-aur-zabaan-ki-hifazat/',
                                'duration' => 1200
                            ],
                            [
                                'title' => 'Bachon ki Tarbiyat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/bachon-ki-tarbiyat-2/',
                                'duration' => 1500
                            ],
                            [
                                'title' => 'Bemaari, Ayadat aur unki Sunnatein',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/bemaari-ayadat-aur-unki-sunnatein/',
                                'duration' => 1300
                            ],
                        ],
                    ],
                    [
                        'title' => 'More Sunnahs',
                        'lessons' => [
                            [
                                'title' => 'Safar ki Sunnatein',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/safar-ki-sunnatein/',
                                'duration' => 1100
                            ],
                            [
                                'title' => 'Sunnat mein Barkat hai',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/sunnat-mein-barkat-hai-2/',
                                'duration' => 1000
                            ],
                        ],
                    ],
                ],
            ],

            // Course 8: Tarq e Masiyah
            [
                'title' => 'Tarq e Masiyah - Turning Back to Allah',
                'description' => 'Guidance on leaving sins and returning to Allah. Learn practical steps for repentance, avoiding temptations, and building a life of obedience.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Intermediate',
                'sort_order' => 8,
                'modules' => [
                    [
                        'title' => 'Path of Repentance',
                        'lessons' => [
                            [
                                'title' => 'Bachon ki Tarbiyat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/bachon-ki-tarbiyat/',
                                'duration' => 1400
                            ],
                            [
                                'title' => 'Libaas ka Hukum',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/libaas-ka-hukum/',
                                'duration' => 1100
                            ],
                        ],
                    ],
                ],
            ],

            // Course 9: Tazkiyah - Journey to Purity
            [
                'title' => 'Tazkiyah - Journey to Purity',
                'description' => 'Deep dive into spiritual purification (Tazkiyah). Learn about cleansing the heart, developing virtues, and removing spiritual diseases.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Advanced',
                'sort_order' => 9,
                'modules' => [
                    [
                        'title' => 'Tareeqat - Way for the Seekers',
                        'lessons' => [
                            [
                                'title' => 'Bait aur us ki Ahmiyat',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/bait-aur-us-ki-ahmiyat/',
                                'duration' => 1600
                            ],
                            [
                                'title' => 'Tasbeehat e Thalatha',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/tasbeehat-e-thalatha/',
                                'duration' => 1400
                            ],
                        ],
                    ],
                    [
                        'title' => 'Zikr e Qalbi - Remembrance of the Heart',
                        'lessons' => [
                            [
                                'title' => 'Zikr e Qalbi – Ta\'aruf or Maqsad',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/zikr-e-qalbi-taaruf-or-maqsad/',
                                'duration' => 1800
                            ],
                            [
                                'title' => 'Zikr e Qalbi – Quran Kareem ki roshni mein',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/zikr-e-qalbi-quran-kareem-ki-roshni-mein/',
                                'duration' => 2000
                            ],
                        ],
                    ],
                ],
            ],

            // Course 10: Huqooq - Rights and Responsibilities
            [
                'title' => 'Huqooq - Rights and Responsibilities',
                'description' => 'Understanding the rights we owe to Allah, ourselves, family, and society. Learn about fulfilling responsibilities according to Islamic teachings.',
                'instructor' => 'Hazrat Shaykh DB',
                'level' => 'Intermediate',
                'sort_order' => 10,
                'modules' => [
                    [
                        'title' => 'Understanding Rights',
                        'lessons' => [
                            [
                                'title' => 'Huqooq Allah - Rights of Allah',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/huqooq-allah/',
                                'duration' => 1500
                            ],
                            [
                                'title' => 'Huqooq ul Ibad - Rights of People',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/huqooq-ul-ibad/',
                                'duration' => 1600
                            ],
                            [
                                'title' => 'Huqooq ul Walidain - Rights of Parents',
                                'video_url' => 'https://tazkiyahtarbiyah.com/video-series/huqooq-ul-walidain/',
                                'duration' => 1400
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
