<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_is_awarded_on_course_completion(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 1]);
        $lesson2 = Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 2]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        // Complete first lesson
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'is_completed' => true,
            'completed_at' => now(),
            'time_watched_seconds' => 100,
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => false,
        ]);

        // Complete second lesson (course completion)
        $this->actingAs($user)->post(route('lessons.complete', $lesson2));

        $this->assertDatabaseHas('certificates', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_completion',
        ]);
    }

    public function test_user_can_view_their_certificates(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_completion',
            'certificate_number' => 'CERT-TEST123',
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('certificates.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Certificates/Index')
            ->has('certificates', 1)
        );
    }

    public function test_user_can_download_certificate_pdf(): void
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_completion',
            'certificate_number' => 'CERT-TEST123',
            'issued_at' => now(),
        ]);

        // Generate PDF
        $service = new CertificateService();
        $certificate = $service->generatePdf($certificate);

        $response = $this->actingAs($user)->get(route('certificates.download', $certificate));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        
        Storage::disk('local')->assertExists($certificate->pdf_path);
    }

    public function test_user_cannot_download_other_users_certificate(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create();

        $certificate = Certificate::create([
            'user_id' => $user1->id,
            'course_id' => $course->id,
            'type' => 'course_completion',
            'certificate_number' => 'CERT-TEST123',
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($user2)->get(route('certificates.download', $certificate));

        $response->assertStatus(403);
    }

    public function test_certificate_number_is_unique(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $cert1 = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_completion',
            'certificate_number' => Certificate::generateCertificateNumber(),
            'issued_at' => now(),
        ]);

        $cert2 = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'level_up',
            'certificate_number' => Certificate::generateCertificateNumber(),
            'issued_at' => now(),
        ]);

        $this->assertNotEquals($cert1->certificate_number, $cert2->certificate_number);
    }
}
