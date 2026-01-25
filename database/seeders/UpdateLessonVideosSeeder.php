<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;

class UpdateLessonVideosSeeder extends Seeder
{
    /**
     * Update existing lessons with actual video URLs from Tazkiyah Tarbiyah
     */
    public function run(): void
    {
        $updates = [
            // Aqaid
            "Hazraat e Sahaba Ikraam RA ka Ta'aruf" => 'https://tazkiyahtarbiyah.com/video-series/hazraat-e-sahaba-ikraam-ra-ka-taaruf/',
            'Sunnat Tareeqah hi Wahid Rasta hai' => 'https://tazkiyahtarbiyah.com/video-series/sunnat-tareeqah-hi-wahid-rasta-hai/',
            
            // Hamds & Naats
            'Pyare Nabi S.A.W.W ki Ishq Mein (Urdu Lyrics)' => 'https://tazkiyahtarbiyah.com/video-series/pyare-nabi-s-a-w-w-ki-ishq-mein/',
            'Aye Dil Muhammad ki Faryaad hojaa – Naatiya Kalam' => 'https://tazkiyahtarbiyah.com/video-series/aye-dil-muhammad-ki-faryaad-hojaa/',
            
            // Islah aur Tarbiyat
            'Ikhlaas ki Haqeeqat kiya hai?' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-ki-haqeeqat-kiya-hai/',
            'Ikhlaas walay Amal ki Taaqat' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-walay-amal-ki-taaqat/',
            'Ikhlaas wala Sadaqah' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-wala-sadaqah/',
            'Ikhlaas ki Tareef' => 'https://tazkiyahtarbiyah.com/video-series/ikhlaas-ki-tareef/',
            'Kia ap Darakht say Afzal hain?' => 'https://tazkiyahtarbiyah.com/video-series/kia-ap-darakht-say-afzal-hain/',
            'Amal ki Qubooliyat ki Do Sharaait' => 'https://tazkiyahtarbiyah.com/video-series/amal-ki-qubooliyat-ki-do-sharaait/',
            
            // Muharram
            'Muharram al Haram ki Ahmiyat' => 'https://tazkiyahtarbiyah.com/video-series/muharram-al-haram-ki-ahmiyat/',
            'Ashura ka Roza' => 'https://tazkiyahtarbiyah.com/video-series/ashura-ka-roza/',
            
            // Ramadan
            'Ramazan kay Ahkaam, Sunnatein aur Kaifiyaat' => 'https://tazkiyahtarbiyah.com/video-series/ramazan-kay-ahkaam-sunnatein-aur-kaifiyaat/',
            'Ramazan aur Tazkiyah Nafs' => 'https://tazkiyahtarbiyah.com/video-series/ramazan-aur-tazkiyah-nafs/',
            'Ramazan kay baad Taqwa waali Zindagi' => 'https://tazkiyahtarbiyah.com/video-series/ramazan-kay-baad-taqwa-waali-zindagi/',
            'Shab e Qadr – Laylatal ul Qadr' => 'https://tazkiyahtarbiyah.com/video-series/shab-e-qadr-laylatal-ul-qadr/',
            'Shab e Qadr Hasil karna Nihayat Asaan' => 'https://tazkiyahtarbiyah.com/video-series/shab-e-qadr-hasil-karna-nihayat-asaan/',
            'Zindagi ko Ramazan ki Tarah Guzarnay ka Tareeqah' => 'https://tazkiyahtarbiyah.com/video-series/zindagi-ko-ramazan-ki-tarah-guzarnay-ka-tareeqah/',
            
            // Zilhijjah
            'Zilhajjah kay pehlay Ashray ki Ahmiyat' => 'https://tazkiyahtarbiyah.com/video-series/zilhajjah-kay-pehlay-ashray-ki-ahmiyat/',
            'Qurbani ki Fazilat or Hukum' => 'https://tazkiyahtarbiyah.com/video-series/qurbani-ki-fazilat-or-hukum/',
            'Auliya Allah ki Qurbani ki Kafiyat' => 'https://tazkiyahtarbiyah.com/video-series/auliya-allah-ki-qurbani-ki-kafiyat/',
            
            // Sunnah
            'Azaan aur Aqamat ki Sunnatein' => 'https://tazkiyahtarbiyah.com/video-series/azaan-aur-aqamat-ki-sunnatein/',
            'Bait ul Khala janay ki Sunnatein' => 'https://tazkiyahtarbiyah.com/video-series/bait-ul-khala-janay-ki-sunnatein/',
            'Jamaee ka Hukum or Aadaab' => 'https://tazkiyahtarbiyah.com/video-series/jamaee-ka-hukum-or-aadaab/',
            'Hath aur Zabaan ki Hifazat' => 'https://tazkiyahtarbiyah.com/video-series/hath-aur-zabaan-ki-hifazat/',
            'Bachon ki Tarbiyat' => 'https://tazkiyahtarbiyah.com/video-series/bachon-ki-tarbiyat-2/',
            'Bemaari, Ayadat aur unki Sunnatein' => 'https://tazkiyahtarbiyah.com/video-series/bemaari-ayadat-aur-unki-sunnatein/',
            
            // Tarq e Masiyah
            'Libaas ka Hukum' => 'https://tazkiyahtarbiyah.com/video-series/libaas-ka-hukum/',
            
            // Tazkiyah
            'Bait aur us ki Ahmiyat' => 'https://tazkiyahtarbiyah.com/video-series/bait-aur-us-ki-ahmiyat/',
            'Tasbeehat e Thalatha' => 'https://tazkiyahtarbiyah.com/video-series/tasbeehat-e-thalatha/',
            "Zikr e Qalbi – Ta'aruf or Maqsad" => 'https://tazkiyahtarbiyah.com/video-series/zikr-e-qalbi-taaruf-or-maqsad/',
            'Zikr e Qalbi – Quran Kareem ki roshni mein' => 'https://tazkiyahtarbiyah.com/video-series/zikr-e-qalbi-quran-kareem-ki-roshni-mein/',
        ];

        $count = 0;
        foreach ($updates as $title => $url) {
            $updated = Lesson::where('title', $title)->update([
                'video_provider' => 'external',
                'external_video_url' => $url,
            ]);
            
            if ($updated > 0) {
                $count += $updated;
                $this->command->info("Updated: {$title}");
            }
        }

        $this->command->info('');
        $this->command->info("Total lessons updated: {$count}");
    }
}
