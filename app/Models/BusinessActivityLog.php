<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessActivityLog extends Model
{
    use HasFactory;

    protected $table = 'business_activity_logs';

    protected $fillable = [
        'business_id',
        'actor_id',
        'actor_name',
        'module',
        'action',
        'module_id',
        'description',
        'data',
        'ip_address',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the business that owns this activity log.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
