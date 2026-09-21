<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plans extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'validity',
        'validity_type',
        'type',
        'user_quota',
        'ios_product_id',
        'android_product_id',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'validity' => 'integer',
        'type' => 'integer',
        'user_quota' => 'integer',
        'status' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function isBusinessPlan(): bool
    {
        return $this->type === 1;
    }

    public function isIndividualPlan(): bool
    {
        return $this->type === 0;
    }

    public function isFree(): bool
    {
        return (float)$this->price == 0.00 || $this->validity_type === 'lifetime';
    }

    public function isSingle(): bool
    {
        return $this->validity_type === 'one-time';
    }

    public function isPro(): bool
    {
        return $this->isIndividualPlan() && (float)$this->price > 0 && $this->validity_type !== 'one-time';
    }

    public function isUnlimited(): bool
    {
        return $this->isPro() || $this->isBusinessPlan();
    }

    public function canExportPdf(): bool
    {
        // Free tier cannot export; Single analysis, Pro, and Business can export
        return !$this->isFree();
    }

    public function hasFullProfiling(): bool
    {
        // Free tier gets basic profiling; Single, Pro, and Business get full behavioral profiling
        return !$this->isFree();
    }
}
