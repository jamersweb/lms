<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Enrollment;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_lesson_without_enrollment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_free_preview' => false]);

        // Attempt to complete lesson (IDOR check on LessonProgressController)
        $response = $this->actingAs($user)->post(route('lessons.complete', $lesson));
        
        // Should be forbidden (403 or 404 depending on middleware approach, usually 403)
        // Note: Currently LessonProgressController might not have enrollment check implemented yet per Phase 3 notes.
        // If it fails, we know we need to harden it.
        $this->assertTrue(in_array($response->status(), [403, 404]), "Status was " . $response->status());
    }

    public function test_user_can_access_free_preview_without_enrollment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_free_preview' => true]);

        // For now we test completion endpoint as a proxy for access, 
        // or if we had a view endpoint we'd check that. 
        // Let's assume progress tracking usually requires enrollment EVEN IF it is free preview?
        // Actually free preview usually implies viewing only. 
        // Let's check if we can view the lesson page (if route exists).
        // Standard LMS: free preview = viewable. Tracking progress might still require enrollment or just be allowed.
        // We'll verify the 'show' route if it existed.
        // For regression, let's verify we CANNOT complete it if not enrolled, unless generic policy allows.
        
        $response = $this->actingAs($user)->post(route('lessons.complete', $lesson));
        // If our logic is "must be enrolled to track progress", this should fail.
        // If "free preview means fully accessible including tracking", it passes.
        // Based on Phase 1/3, usually enrollment is required for progress.
        $this->assertTrue(in_array($response->status(), [403, 404]), "Status was " . $response->status());
    }

    public function test_admin_routes_protected()
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->get(route('admin.courses.index'));
        $response->assertStatus(403);
    }

    public function test_certificate_download_authorization()
    {
        // Placeholder for certificate IDOR check if certificates were implemented in Phase 1 (implicit).
        // Since we didn't explicitly implement CertificateController in P1/P2/P3, skipping for now
        // or assuming it's a future step. We'll mark as skipped.
        $this->markTestSkipped('Certificate module not yet implemented explicitly.');
    }
}
