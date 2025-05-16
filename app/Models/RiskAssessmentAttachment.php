<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAssessmentAttachment extends Model
{
    use HasFactory;

    // กำหนดฟิลด์ที่สามารถกำหนดค่าได้
    protected $fillable = [
        'risk_assessment_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    // ความสัมพันธ์กับโมเดล OrganizationalRisk
    public function riskAssessment()
    {
        return $this->belongsTo(RiskAssessment::class);
    }

    // สร้าง accessor สำหรับ URL ของไฟล์
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}
