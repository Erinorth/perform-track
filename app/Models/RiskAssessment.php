<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = ['assessment_date', 'likelihood_level', 'impact_level', 'notes', 'division_risk_id'];

    public function divisionRisk(): BelongsTo
    {
        return $this->belongsTo(DivisionRisk::class);
    }
}
