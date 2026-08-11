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
        'privacy_policy_fr',
        'terms_and_conditions',
        'terms_and_conditions_fr',
        'feedback_form_link',
        'feedback_form_link_fr',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
