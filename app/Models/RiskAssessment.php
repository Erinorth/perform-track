<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = ['assessment_date', 'likelihood_level', 'impact_level', 'notes', 'department_risk_id'];

    public function departmentRisk(): BelongsTo
    {
        return $this->belongsTo(DepartmentRisk::class);
    }
}
