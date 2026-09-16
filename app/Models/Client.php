<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'user_id',
        'client_id',
        'client_alias',
        'notes',
        'ai_summary',
        'summary_updated_at',
    ];

    protected $casts = [
        'ai_summary' => 'array',
        'summary_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cases()
    {
        return $this->hasMany(ClientCase::class, 'client_id', 'client_id')
            ->where('user_id', $this->user_id);
    }
}
