<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseNode extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['video', 'form', 'menu', 'info', 'action'];

    protected $fillable = [
        'course_id',
        'type',
        'title',
        'slug',
        'position',
        'content',
        'video_url',
        'meeting_link',
        'show_description',
        'pos_x',
        'pos_y',
        'is_start',
        'is_end',
    ];

    protected $casts = [
        'position' => 'integer',
        'content' => 'array',
        'show_description' => 'boolean',
        'is_start' => 'boolean',
        'is_end' => 'boolean',
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
