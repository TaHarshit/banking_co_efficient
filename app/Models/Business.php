<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Business extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'logo',
        'address',
        'status',
        'business_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'password_setup_token',
        'password_setup_token_expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_setup_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Automatically hash password when setting
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Generate password setup token
     */
    public function generatePasswordSetupToken()
    {
        $this->password_setup_token = Str::random(64);
        $this->password_setup_token_expires_at = now()->addHours(24);
        $this->save();

        return $this->password_setup_token;
    }

    /**
     * Get users belonging to this business
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get employees belonging to this business
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get sections belonging to this business
     */
    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Get questions belonging to this business
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Boot method to auto-generate business code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->business_code)) {
                $business->business_code = 'BUS-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }
}
