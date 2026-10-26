<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationRecording extends Model
{
    protected $fillable = [
        'consultation_request_id',
        'student_voice_data',
        'student_voice_mime',
        'student_voice_filename',
        'official_audio_data',
        'official_audio_mime',
        'official_audio_filename',
    ];

    protected $hidden = [
        'student_voice_data',
        'official_audio_data',
    ];

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }
}
