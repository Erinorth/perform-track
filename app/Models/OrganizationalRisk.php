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

    // เพิ่มความสัมพันธ์กับเอกสารแนบ
    public function organizationalRiskAttachments()
    {
        return $this->hasMany(OrganizationalRiskAttachment::class);
    }

    public function divisionRisks(): HasMany
    {
        return $this->hasMany(DivisionRisk::class);
    }
}
