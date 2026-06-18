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
