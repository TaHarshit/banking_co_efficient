<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserForgotPasswordMail;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_type',
        'name',
        'surname',
        'username',
        'email',
        'phone_no',
        'profile_image',
        'password',
        'business_id',
        'status',
        'job_title',
        'institution',
        'department',
        'year_of_experience',
        'subscribe_newsletter',
        'free_analyses_used',
        'paid_analyses_credits',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'free_analyses_used' => 'integer',
            'paid_analyses_credits' => 'integer',
            // 'password' => 'hashed',
        ];
    }

    /**
     * Get the business that the user belongs to.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user's responses from the onboarding/signup questionnaire.
     */
    public function responses()
    {
        return $this->hasMany(UserResponse::class);
    }

    /**
     * Get the chat sessions for the user.
     */
    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Generates a text summary of the user's behavioral profile based on their signup responses.
     */
    public function getAiBehaviorProfile()
    {
        $responses = $this->responses()->with('question', 'option')->get();
        
        if ($responses->isEmpty()) {
            return "No behavioral profile data available for this user.";
        }

        $profile = "USER BEHAVIORAL PROFILE:\n";
        foreach ($responses as $response) {
            $questionText = $response->question->question_text_en;
            $value = $response->getValue();
            if ($value) {
                $profile .= "- {$questionText}: {$value}\n";
            }
        }

        return $profile;
    }

    /**
     * Get subscriptions for this user
     */
    public function subscriptions()
    {
        return $this->hasMany(UserSubscriptions::class, 'user_id')->orderBy('id', 'desc');
    }

    /**
     * Check if user is associated with a business
     */
    public function isUnderBusiness(): bool
    {
        return !empty($this->business_id);
    }

    /**
     * Check if user has an active subscription (either directly or through their business)
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->isUnderBusiness()) {
            return $this->business ? $this->business->isSubscriptionActive() : false;
        }

        return $this->subscriptions()
            ->where('status', 1)
            ->where('subscription_end_date', '>=', now())
            ->exists();
    }

    /**
     * Get client cases for this user
     */
    public function cases()
    {
        return $this->hasMany(ClientCase::class, 'user_id');
    }

    /**
     * Get the active individual paid subscription (Pro monthly / annual)
     */
    public function getActiveIndividualSubscription(): ?UserSubscriptions
    {
        return $this->subscriptions()
            ->where('status', 1)
            ->where('subscription_end_date', '>=', now())
            ->latest('id')
            ->first();
    }

    /**
     * Get the active plan model for this user
     */
    public function getActivePlan(): ?Plans
    {
        // 1. Business plan inheritance
        if ($this->isUnderBusiness() && $this->business && $this->business->isSubscriptionActive()) {
            return $this->business->plan;
        }

        // 2. Active individual paid recurring subscription (Pro)
        $activeSub = $this->getActiveIndividualSubscription();
        if ($activeSub && $activeSub->plan) {
            return $activeSub->plan;
        }

        // 3. User with paid single analysis credits
        if ($this->paid_analyses_credits > 0) {
            return Plans::where('validity_type', 'one-time')->where('status', 1)->first()
                ?? Plans::where('name', 'like', '%Single%')->first();
        }

        // 4. Default Explorer (Free) plan
        return Plans::where('validity_type', 'lifetime')->where('status', 1)->first()
            ?? Plans::where('price', 0)->first();
    }

    /**
     * Number of remaining free analyses (out of 3 lifetime)
     */
    public function getRemainingFreeAnalyses(): int
    {
        return max(0, 3 - (int)$this->free_analyses_used);
    }

    /**
     * Number of remaining paid consumable analysis credits
     */
    public function getPaidCredits(): int
    {
        return max(0, (int)$this->paid_analyses_credits);
    }

    /**
     * Check if user has unlimited analysis generation (Pro or Business)
     */
    public function isUnlimited(): bool
    {
        if ($this->isUnderBusiness() && $this->business && $this->business->isSubscriptionActive()) {
            return true;
        }

        return $this->getActiveIndividualSubscription() !== null;
    }

    /**
     * Total available analyses (null if unlimited)
     */
    public function getTotalAvailableAnalyses(): ?int
    {
        if ($this->isUnlimited()) {
            return null; // Unlimited
        }

        return $this->getRemainingFreeAnalyses() + $this->getPaidCredits();
    }

    /**
     * Check if user is entitled to run a new analysis
     */
    public function canRunAnalysis(): bool
    {
        if ($this->isUnlimited()) {
            return true;
        }

        return ($this->getRemainingFreeAnalyses() > 0) || ($this->getPaidCredits() > 0);
    }

    /**
     * Check if user can export PDF for a specific case
     */
    public function canExportCasePdf(?ClientCase $case = null): bool
    {
        // Pro & Business subscribers have unlimited export on all cases
        if ($this->isUnlimited()) {
            return true;
        }

        // Cases analyzed using Single Analysis credit have permanent export rights
        if ($case && $case->can_export_pdf) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can export multi-case summary PDF
     */
    public function canExportSummaryPdf(): bool
    {
        return $this->isUnlimited();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::to($this->email)->send(new UserForgotPasswordMail($this, $token));
    }
}
