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
        'user_name',
        'user_email',
        'plan_id',
        'receipt_id',
        'subscription_start_date',
        'subscription_end_date',
        'purchase_from',
        'purchase_token',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    public function plan(){
        return $this->hasOne(Plans::class, 'id', 'plan_id');
    }
    
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
