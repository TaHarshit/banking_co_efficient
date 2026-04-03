<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSubscribe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'company_id',
        'is_subscribed',
    ];

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

}
