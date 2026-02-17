<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'phone',
        'city',
        'country',
    ];

    public function accessLogs()
    {
        return $this->hasMany(ProspectAccessLog::class);
    }

    public function courseProgress()
    {
        return $this->hasMany(ProspectCourseProgress::class);
    }

    public function nodeProgress()
    {
        return $this->hasMany(ProspectNodeProgress::class);
    }
}
