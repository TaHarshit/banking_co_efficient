<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'department',
        'phone',
    ];

    /**
     * Get the business that owns this employee.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Check if an email is registered as employee for a business
     */
    public static function isEmployeeEmail($businessId, $email)
    {
        return self::where('business_id', $businessId)
            ->where('email', strtolower($email))
            ->exists();
    }
}
