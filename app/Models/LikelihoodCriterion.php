<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LikelihoodCriterion extends Model
{
    use HasFactory;

    protected $fillable = ['level', 'name', 'description', 'division_risk_id'];

    public function divisionRisk(): BelongsTo
    {
        return $this->belongsTo(DivisionRisk::class);
    }
}
