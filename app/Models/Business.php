<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Business extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'logo',
        'address',
        'status',
        'business_code',
        'business_policies_message',
        'plan_id',
        'subscription_start_date',
        'subscription_end_date',
        'user_quota',
        'payment_mode',
        'payment_notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'password_setup_token',
        'password_setup_token_expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_setup_token_expires_at' => 'datetime',
            'subscription_start_date' => 'datetime',
            'subscription_end_date' => 'datetime',
            'user_quota' => 'integer',
            'status' => 'integer',
        ];
    }

    /**
     * Automatically hash password when setting
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Generate password setup token
     */
    public function generatePasswordSetupToken()
    {
        $this->password_setup_token = Str::random(64);
        $this->password_setup_token_expires_at = now()->addHours(24);
        $this->save();

        return $this->password_setup_token;
    }

    /**
     * Get users belonging to this business
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get employees belonging to this business
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get sections belonging to this business
     */
    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Get questions belonging to this business
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get case study questions / business policies belonging to this business
     */
    public function caseStudyQuestions()
    {
        return $this->hasMany(CaseStudyQuestion::class);
    }

    /**
     * Alias for caseStudyQuestions
     */
    public function businessPolicies()
    {
        return $this->hasMany(CaseStudyQuestion::class);
    }

    /**
     * Get the subscription plan assigned to this business
     */
    public function plan()
    {
        return $this->belongsTo(Plans::class, 'plan_id');
    }

    /**
     * Get all subscription history records for this business
     */
    public function subscriptions()
    {
        return $this->hasMany(UserSubscriptions::class, 'business_id')->orderBy('id', 'desc');
    }

    /**
     * Check if the business subscription is currently active
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->status != 1 || empty($this->subscription_start_date) || empty($this->subscription_end_date)) {
            return false;
        }

        $now = \Carbon\Carbon::now();
        $start = \Carbon\Carbon::parse($this->subscription_start_date);
        $end = \Carbon\Carbon::parse($this->subscription_end_date)->endOfDay();

        return $now->between($start, $end);
    }

    /**
     * Get current status text of the subscription
     */
    public function getSubscriptionStatusText(): string
    {
        if (empty($this->subscription_start_date) || empty($this->subscription_end_date)) {
            return 'No Plan Assigned';
        }

        if ($this->status != 1) {
            return 'Business Inactive';
        }

        $now = \Carbon\Carbon::now();
        $start = \Carbon\Carbon::parse($this->subscription_start_date);
        $end = \Carbon\Carbon::parse($this->subscription_end_date)->endOfDay();

        if ($now->gt($end)) {
            return 'Expired';
        } elseif ($now->lt($start)) {
            return 'Scheduled';
        } else {
            return 'Active';
        }
    }

    /**
     * Calculate how many seats are currently in use (unique emails across employees and active users)
     */
    public function getUsedQuota(): int
    {
        $employeeEmails = $this->employees()->pluck('email')->filter()->map(function ($e) {
            return strtolower(trim($e));
        })->toArray();

        $userEmails = $this->users()->whereIn('status', [1, '1', 'active'])->pluck('email')->filter()->map(function ($e) {
            return strtolower(trim($e));
        })->toArray();

        return count(array_unique(array_merge($employeeEmails, $userEmails)));
    }

    /**
     * Calculate remaining seats based on quota
     */
    public function getRemainingQuota(): int
    {
        $quota = (int)($this->user_quota ?? 0);
        return max(0, $quota - $this->getUsedQuota());
    }

    /**
     * Check if business has active subscription and quota available to add an employee
     */
    public function canAddEmployee(): bool
    {
        return $this->isSubscriptionActive() && $this->getRemainingQuota() > 0;
    }

    /**
     * Boot method to auto-generate business code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->business_code)) {
                $business->business_code = 'BUS-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }
}
