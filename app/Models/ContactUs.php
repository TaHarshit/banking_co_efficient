<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    use HasFactory;

    protected $table = 'contact_us';

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'reply',
        'replied_at',
        'replied_by',
        'status',
        'business_id',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    /**
     * Get the admin user who replied to this contact.
     */
    public function repliedByUser()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * Check if this contact has been replied to.
     */
    public function isReplied(): bool
    {
        return $this->status === 'replied';
    }

    /**
     * Get the business associated with the contact request.
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
