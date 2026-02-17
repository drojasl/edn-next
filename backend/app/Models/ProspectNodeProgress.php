<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectNodeProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'course_node_id',
        'viewed_at',
        'data',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'data' => 'array',
    ];

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function node()
    {
        return $this->belongsTo(CourseNode::class, 'course_node_id');
    }
}
