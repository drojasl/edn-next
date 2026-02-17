<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectCourseProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'course_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
