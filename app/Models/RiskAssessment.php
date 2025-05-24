<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'assessment_year',
        'assessment_period',
        'likelihood_level', 
        'impact_level', 
        'risk_score', 
        'notes', 
        'division_risk_id'
    ];

    /**
     * คอลัมน์ที่ควรแปลงเป็น Data types เฉพาะ
     *
     * @var array
     */
    protected $casts = [
        'assessment_year' => 'integer',
        'assessment_period' => 'integer',
        'likelihood_level' => 'integer',
        'impact_level' => 'integer',
        'risk_score' => 'integer',
    ];

    /**
     * ความสัมพันธ์กับเอกสารแนบ
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(RiskAssessmentAttachment::class);
    }

    /**
     * ความสัมพันธ์กับความเสี่ยงระดับฝ่าย
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function divisionRisk(): BelongsTo
    {
        return $this->belongsTo(DivisionRisk::class);
    }

    /**
     * ความสัมพันธ์กับแผนการจัดการความเสี่ยง
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function riskMitigationPlans(): HasMany
    {
        return $this->hasMany(RiskMitigationPlan::class);
    }

    /**
     * อ่านค่าระดับความเสี่ยงจากคะแนนความเสี่ยง
     * 
     * @return string
     */
    public function getRiskLevelAttribute(): string
    {
        if ($this->risk_score <= 3) {
            return 'ต่ำ';
        } elseif ($this->risk_score <= 6) {
            return 'ปานกลาง';
        } elseif ($this->risk_score <= 9) {
            return 'สูง';
        } else {
            return 'สูงมาก';
        }
    }

    /**
     * อ่านค่าสีระดับความเสี่ยงสำหรับแสดงผล
     * 
     * @return string
     */
    public function getRiskColorAttribute(): string
    {
        if ($this->risk_score <= 3) {
            return 'green';
        } elseif ($this->risk_score <= 6) {
            return 'yellow';
        } elseif ($this->risk_score <= 9) {
            return 'orange';
        } else {
            return 'red';
        }
    }

    /**
     * คำนวณคะแนนความเสี่ยงจากระดับโอกาสและผลกระทบ
     * 
     * @return int
     */
    public function calculateRiskScore(): int
    {
        return $this->likelihood_level * $this->impact_level;
    }
}
