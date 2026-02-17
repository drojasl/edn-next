<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseNode extends Model
{
    use HasFactory;

    public const TYPES = ['video', 'form', 'menu', 'info', 'action'];

    protected $fillable = [
        'course_id',
        'type',
        'title',
        'slug',
        'content',
        'video_url',
        'position',
        'pos_x',
        'pos_y',
        'is_start',
        'is_end',
    ];

    protected $casts = [
        'content' => 'array',
        'is_start' => 'boolean',
        'is_end' => 'boolean',
        'position' => 'integer',
        'pos_x' => 'integer',
        'pos_y' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function options()
    {
        return $this->hasMany(CourseNodeOption::class);
    }
}
