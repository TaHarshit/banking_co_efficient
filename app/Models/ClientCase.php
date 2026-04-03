<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'case_reference',
        'client_alias',
        'context_overview',
        'case_details',
    ];

    protected $casts = [
        'case_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
