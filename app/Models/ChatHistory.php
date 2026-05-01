<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'suggestions',
        'images',
        'reference_pages'
    ];

    protected $casts = [
        'suggestions' => 'array',
        'images' => 'array',
        'reference_pages' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
