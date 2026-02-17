<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'access_code_id',
        'activated_at',
        'expired_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function accessCode()
    {
        return $this->belongsTo(AccessCode::class);
    }
}
