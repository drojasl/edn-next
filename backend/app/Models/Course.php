<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'is_active',
        'pos_x',
        'pos_y',
        'next_course_id',
        'next_course_label',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    public function nodes()
    {
        return $this->hasMany(CourseNode::class);
    }

    public function nextCourse()
    {
        return $this->belongsTo(Course::class, 'next_course_id');
    }

    public function accessCodes()
    {
        return $this->hasMany(AccessCode::class);
    }
}
