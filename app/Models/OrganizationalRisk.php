<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalRisk extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['risk_name', 'description'];

    public function departmentRisks(): HasMany
    {
        return $this->hasMany(DepartmentRisk::class);
    }
}
