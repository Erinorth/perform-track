<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LikelihoodCriterion extends Model
{
    use HasFactory;

    protected $fillable = ['level', 'name', 'description', 'department_risk_id'];

    public function departmentRisk(): BelongsTo
    {
        return $this->belongsTo(DepartmentRisk::class);
    }
}
