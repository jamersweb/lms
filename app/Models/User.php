<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'has_bayah',
        'level',
        'whatsapp_number',
        'whatsapp_opt_in',
        'email_reminders_opt_in',
        'last_active_at',
        // Note: 'is_admin' intentionally excluded for security - set via admin controllers only
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'has_bayah' => 'boolean',
            'whatsapp_opt_in' => 'boolean',
            'email_reminders_opt_in' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function habits()
    {
        return $this->hasMany(Habit::class);
    }

    public function habitLogs()
    {
        return $this->hasMany(HabitLog::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function settings()
    {
        return $this->hasOne(UserSettings::class);
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }

    public function replies()
    {
        return $this->hasMany(DiscussionReply::class);
    }
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function isEnrolledIn($courseId)
    {
        return $this->enrollments()->where('course_id', $courseId)->exists();
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function pointsEvents()
    {
        return $this->hasMany(PointsEvent::class);
    }

    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function assessmentResponses()
    {
        return $this->hasMany(AssessmentResponse::class);
    }

    public function courseExemptions()
    {
        return $this->hasMany(CourseExemption::class);
    }

    public function voiceNotes()
    {
        return $this->hasMany(VoiceNote::class, 'sender_id');
    }

    public function nudgeDeliveries()
    {
        return $this->hasMany(NudgeDelivery::class);
    }

    public function lessonReflections()
    {
        return $this->hasMany(LessonReflection::class);
    }

    public function taskProgress()
    {
        return $this->hasMany(TaskProgress::class);
    }

    public function dailyMetrics()
    {
        return $this->hasMany(DailyUserMetric::class);
    }

    public function latestMetric()
    {
        return $this->hasOne(DailyUserMetric::class)->latestOfMany('date');
    }

    public function courseProgressSnapshots()
    {
        return $this->hasMany(CourseProgressSnapshot::class);
    }

    public function activityEvents()
    {
        return $this->hasMany(ActivityEvent::class);
    }
}
