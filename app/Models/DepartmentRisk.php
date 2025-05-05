<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentRisk extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['risk_name', 'description', 'organizational_risk_id'];

    public function organizationalRisk(): BelongsTo
    {
        return $this->belongsTo(OrganizationalRisk::class);
    }

    public function likelihoodCriteria(): HasMany
    {
        return $this->hasMany(LikelihoodCriterion::class);
    }

    public function impactCriteria(): HasMany
    {
        return $this->hasMany(ImpactCriterion::class);
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }
}
