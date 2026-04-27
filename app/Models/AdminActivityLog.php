<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_logs';

    protected $fillable = [
        'admin_id',
        'module',
        'action',
        'module_id',
        'description',
        'data',
        'ip_address'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
