<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_android_version',
        'android_build_number',
        'user_ios_version',
        'ios_build_number',
        'privacy_policy',
        'terms_and_conditions'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
