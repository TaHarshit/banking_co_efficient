<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSubscriptions extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_id',
        'user_name',
        'user_email',
        'plan_id',
        'receipt_id',
        'purchase_token',
        'purchase_from',
        'subscription_start_date',
        'subscription_end_date',
        'user_quota',
        'amount',
        'payment_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'subscription_start_date' => 'datetime',
        'subscription_end_date' => 'datetime',
        'amount' => 'decimal:2',
        'user_quota' => 'integer',
        'status' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    public function plan(){
        return $this->belongsTo(Plans::class, 'plan_id', 'id');
    }
    
    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function business(){
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }
}
