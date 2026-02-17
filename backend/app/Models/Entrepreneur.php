<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entrepreneur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'codigo_amway',
        'is_account_holder',
        'is_active',
        'slug',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_account_holder' => 'boolean',
        ];
    }

    /**
     * Get style settings.
     */
    public function styleSettings()
    {
        return $this->hasOne(UserStyleSetting::class, 'user_id');
    }

    /**
     * Get social links.
     */
    public function socialLinks()
    {
        return $this->hasMany(UserSocialLink::class, 'user_id');
    }

    /**
     * Get courses owned by user.
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'user_id');
    }

    /**
     * Scope for finding user by Amway credentials.
     */
    public function scopeByCredentials($query, $amwayCode, $isAccountHolder)
    {
        return $query->where('codigo_amway', $amwayCode)
            ->where('is_account_holder', $isAccountHolder);
    }
}
