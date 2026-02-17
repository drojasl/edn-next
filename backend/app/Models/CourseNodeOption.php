<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseNodeOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_node_id',
        'label',
        'next_node_id',
    ];

    public function node()
    {
        return $this->belongsTo(CourseNode::class, 'course_node_id');
    }

    public function nextNode()
    {
        return $this->belongsTo(CourseNode::class, 'next_node_id');
    }
}
