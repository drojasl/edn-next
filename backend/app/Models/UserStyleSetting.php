<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStyleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'primary_color',
        'secondary_color',
        'accent_color',
        'logo_url',
        'theme_mode',
    ];

    public function user()
    {
        return $this->belongsTo(Entrepreneur::class);
    }
}
