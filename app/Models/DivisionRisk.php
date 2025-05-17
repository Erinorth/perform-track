<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionRisk extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<int, string>
     */
    protected $fillable = ['risk_name', 'description', 'organizational_risk_id'];

    /**
     * ความสัมพันธ์กับเอกสารแนบ
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachment(): HasMany
    {
        return $this->hasMany(DivisionRiskAttachment::class);
    }

    /**
     * ความสัมพันธ์กับความเสี่ยงระดับองค์กร
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organizationalRisk(): BelongsTo
    {
        return $this->belongsTo(OrganizationalRisk::class);
    }

    /**
     * ความสัมพันธ์กับการประเมินความเสี่ยง
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    /**
     * ความสัมพันธ์กับเกณฑ์โอกาสเกิด
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function likelihoodCriteria(): HasMany
    {
        return $this->hasMany(LikelihoodCriterion::class);
    }

    /**
     * ความสัมพันธ์กับเกณฑ์ผลกระทบ
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function impactCriteria(): HasMany
    {
        return $this->hasMany(ImpactCriterion::class);
    }
}
