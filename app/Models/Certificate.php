<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'type',
        'level',
        'certificate_number',
        'issued_at',
        'metadata',
        'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public static function generateCertificateNumber(): string
    {
        return 'CERT-' . strtoupper(uniqid());
    }
}
