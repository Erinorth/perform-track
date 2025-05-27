<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// โมเดลสำหรับการควบคุมความเสี่ยง
class RiskControl extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'division_risk_id',
        'control_name',
        'description',
        'owner',
        'status',
        'control_type',
        'implementation_details',
    ];

    protected $casts = [
        'status' => 'string',
        'control_type' => 'string',
    ];

    // Constants สำหรับการใช้งาน
    const CONTROL_TYPES = [
        'preventive' => 'ป้องกัน',
        'detective' => 'ตรวจสอบ',
        'corrective' => 'แก้ไข',
        'compensating' => 'ชดเชย'
    ];

    const STATUS_OPTIONS = [
        'active' => 'ใช้งาน',
        'inactive' => 'ไม่ใช้งาน'
    ];

    // ความสัมพันธ์กับ DivisionRisk
    public function divisionRisk()
    {
        return $this->belongsTo(DivisionRisk::class, 'division_risk_id');
    }

    // ความสัมพันธ์ผ่าน DivisionRisk ไปยัง OrganizationalRisk
    public function organizationalRisk()
    {
        return $this->hasOneThrough(
            OrganizationalRisk::class,
            DivisionRisk::class,
            'id', // Foreign key on DivisionRisk table
            'id', // Foreign key on OrganizationalRisk table
            'division_risk_id', // Local key on RiskControl table
            'organizational_risk_id' // Local key on DivisionRisk table
        );
    }

    // ความสัมพันธ์กับ ControlAssessment (ถ้ามี)
    public function assessments()
    {
        return $this->hasMany(ControlAssessment::class);
    }

    // ความสัมพันธ์สำหรับการประเมินล่าสุด
    public function latestAssessment()
    {
        return $this->hasOne(ControlAssessment::class)->latest('assessment_date');
    }

    // Accessor สำหรับแสดงชื่อสถานะเป็นภาษาไทย
    public function getStatusNameAttribute()
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    // Accessor สำหรับแสดงชื่อประเภทการควบคุม
    public function getControlTypeNameAttribute()
    {
        return self::CONTROL_TYPES[$this->control_type] ?? $this->control_type;
    }

    // Accessor สำหรับแสดงข้อมูลสำหรับ dropdown
    public function getDisplayNameAttribute()
    {
        return $this->control_name . ($this->control_type ? ' (' . $this->control_type_name . ')' : '');
    }

    // Scopes สำหรับการกรอง
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('control_type', $type);
    }

    public function scopeByDivisionRisk($query, $divisionRiskId)
    {
        return $query->where('division_risk_id', $divisionRiskId);
    }

    // Scope สำหรับการค้นหา
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('control_name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%')
              ->orWhere('owner', 'like', '%' . $search . '%')
              ->orWhere('implementation_details', 'like', '%' . $search . '%');
        });
    }

    // Scope สำหรับการกรองตามหลายเงื่อนไข
    public function scopeFilter($query, array $filters)
    {
        return $query->when($filters['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($filters['control_type'] ?? null, function ($query, $type) {
                return $query->where('control_type', $type);
            })
            ->when($filters['division_risk_id'] ?? null, function ($query, $divisionRiskId) {
                return $query->where('division_risk_id', $divisionRiskId);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->search($search);
            });
    }

    // Method สำหรับเปลี่ยนสถานะ
    public function toggleStatus()
    {
        $this->status = $this->status === 'active' ? 'inactive' : 'active';
        return $this->save();
    }

    // Method สำหรับตรวจสอบว่าสามารถลบได้หรือไม่
    public function canBeDeleted()
    {
        // ตรวจสอบว่ามีการประเมินที่เกี่ยวข้องหรือไม่
        return $this->assessments()->count() === 0;
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RiskControlAttachment::class);
    }
}
