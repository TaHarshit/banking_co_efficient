<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'case_reference',
        'client_alias',
        'context_overview',
        'case_details',
        'ai_analysis',
        'action_plan',
        'plan_rating',
    ];

    protected $casts = [
        'case_details' => 'array',
        'ai_analysis' => 'array',
        'action_plan' => 'array',
    ];

    protected $appends = [
        'analyze_status',
        'plan_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aiJobs()
    {
        return $this->hasMany(AiJob::class, 'case_id');
    }

    public function getAnalyzeStatusAttribute(): string
    {
        $latestJob = $this->aiJobs()->where('job_type', 'analyze_case')->latest()->first();
        if ($latestJob) {
            return $latestJob->status;
        }
        return ! empty($this->ai_analysis) ? 'completed' : 'not_started';
    }

    public function getPlanStatusAttribute(): string
    {
        $latestJob = $this->aiJobs()->where('job_type', 'generate_plan')->latest()->first();
        if ($latestJob) {
            return $latestJob->status;
        }
        return ! empty($this->action_plan) ? 'completed' : 'not_started';
    }
}
