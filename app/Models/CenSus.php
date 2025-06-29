<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class CenSus extends Model
{
    use HasFactory;

    /**
     * ชื่อการเชื่อมต่อฐานข้อมูล
     */
    protected $connection = 'mmddata';

    /**
     * ชื่อตาราง
     */
    protected $table = 'view_census';

    /**
     * Primary Key
     */
    protected $primaryKey = 'EMPN';

    /**
     * ประเภทของ Primary Key
     */
    protected $keyType = 'string';

    /**
     * ไม่ใช่ auto-incrementing
     */
    public $incrementing = false;

    /**
     * ไม่มี timestamps (created_at, updated_at)
     */
    public $timestamps = false;

    /**
     * ฟิลด์ที่สามารถ mass assignment ได้
     */
    protected $fillable = [
        'EMPN',
        'TITLE',
        'NAME',
        'emp_ename',
        'fay',
        'gong',
        'pnang',
        'a_position',
    ];

    /**
     * กำหนดประเภทข้อมูลของ attributes
     */
    protected $casts = [
        'EMPN' => 'string',
        'TITLE' => 'string',
        'NAME' => 'string',
        'emp_ename' => 'string',
        'fay' => 'string',
        'gong' => 'string',
        'pnang' => 'string',
        'a_position' => 'string',
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'egat_id', 'EMPN');
    }

    // === Helper Methods ===

    /**
     * รับชื่อเต็มภาษาไทย
     */
    public function getFullNameThaiAttribute(): string
    {
        return trim(($this->TITLE ?? '') . ' ' . ($this->NAME ?? ''));
    }

    /**
     * รับชื่อเต็มภาษาอังกฤษ
     */
    public function getFullNameEnglishAttribute(): string
    {
        return $this->emp_ename ?? '';
    }

    /**
     * รับชื่อฝ่าย
     */
    public function getDivisionNameAttribute(): string
    {
        return $this->fay ?? '';
    }

    /**
     * รับชื่อกอง
     */
    public function getDepartmentNameAttribute(): string
    {
        return $this->gong ?? '';
    }

    /**
     * รับชื่อแผนก
     */
    public function getSectionNameAttribute(): string
    {
        return $this->pnang ?? '';
    }

    /**
     * รับตำแหน่งงาน
     */
    public function getPositionNameAttribute(): string
    {
        return $this->a_position ?? '';
    }

    /**
     * รับชื่อองค์กรเต็ม
     */
    public function getOrganizationFullAttribute(): string
    {
        $parts = array_filter([
            $this->getDivisionNameAttribute(),
            $this->getDepartmentNameAttribute(),
            $this->getSectionNameAttribute(),
        ]);

        return implode(' - ', $parts);
    }

    /**
     * ตรวจสอบว่าเป็นพนักงานหญิงหรือไม่
     */
    public function isFemale(): bool
    {
        return in_array($this->TITLE, ['นาง', 'น.ส.', 'Ms.', 'Miss', 'Mrs.']);
    }

    /**
     * ตรวจสอบว่าเป็นพนักงานชายหรือไม่
     */
    public function isMale(): bool
    {
        return in_array($this->TITLE, ['นาย', 'Mr.', 'วาที่ ร.ต.', 'Act.Sub.Lt.']);
    }

    /**
     * แปลงคำนำหน้าเป็นภาษาไทย
     */
    public function getTitleThaiAttribute(): string
    {
        $titleMapping = [
            'Mr.' => 'นาย',
            'Mrs.' => 'นาง',
            'Miss' => 'น.ส.',
            'Ms.' => 'น.ส.',
            'Act.Sub.Lt.' => 'วาที่ ร.ต.',
        ];

        return $titleMapping[$this->TITLE] ?? $this->TITLE ?? '';
    }

    /**
     * แปลงคำนำหน้าเป็นภาษาอังกฤษ
     */
    public function getTitleEnglishAttribute(): string
    {
        $titleMapping = [
            'นาย' => 'Mr.',
            'นาง' => 'Mrs.',
            'น.ส.' => 'Miss',
            'วาที่ ร.ต.' => 'Act.Sub.Lt.',
        ];

        return $titleMapping[$this->TITLE] ?? $this->TITLE ?? '';
    }

    /**
     * รับระดับตำแหน่ง (จากรหัสตำแหน่ง)
     */
    public function getPositionLevelAttribute(): string
    {
        $position = $this->a_position ?? '';
        
        if (str_contains($position, 'ก')) {
            return 'หัวหน้ากอง';
        } elseif (str_contains($position, 'วศ.')) {
            return 'วิศวกร';
        } elseif (str_contains($position, 'ช.')) {
            return 'ช่าง';
        } elseif (str_contains($position, 'ชก.')) {
            return 'ช่างชำนาญการ';
        } elseif (str_contains($position, 'พช.')) {
            return 'พนักงานวิชาชีพ';
        } elseif (str_contains($position, 'ห')) {
            return 'หัวหน้าแผนก';
        } elseif (str_contains($position, 'อ')) {
            return 'ผู้อำนวยการฝ่าย';
        } elseif (str_contains($position, 'ช.อ')) {
            return 'ผู้ช่วยผู้อำนวยการฝ่าย';
        }

        return 'ไม่ระบุ';
    }

    /**
     * ตรวจสอบว่าเป็นหัวหน้ากองหรือไม่
     */
    public function isChief(): bool
    {
        return str_contains($this->a_position ?? '', 'ก');
    }

    /**
     * ตรวจสอบว่าเป็นหัวหน้าแผนกหรือไม่
     */
    public function isHead(): bool
    {
        return str_contains($this->a_position ?? '', 'ห');
    }

    /**
     * ตรวจสอบว่าเป็นผู้อำนวยการหรือผู้ช่วยผู้อำนวยการหรือไม่
     */
    public function isDirector(): bool
    {
        return str_contains($this->a_position ?? '', 'อ') || str_contains($this->a_position ?? '', 'ช.อ');
    }

    // === Scopes ===

    /**
     * Scope สำหรับค้นหาตามชื่อ
     */
    public function scopeSearchByName($query, $name)
    {
        return $query->where('NAME', 'LIKE', "%{$name}%")
                    ->orWhere('emp_ename', 'LIKE', "%{$name}%");
    }

    /**
     * Scope สำหรับกรองตามฝ่าย
     */
    public function scopeInDivision($query, $division)
    {
        return $query->where('fay', 'LIKE', "%{$division}%");
    }

    /**
     * Scope สำหรับกรองตามกอง
     */
    public function scopeInDepartment($query, $department)
    {
        return $query->where('gong', 'LIKE', "%{$department}%");
    }

    /**
     * Scope สำหรับกรองตามแผนก
     */
    public function scopeInSection($query, $section)
    {
        return $query->where('pnang', 'LIKE', "%{$section}%");
    }

    /**
     * Scope สำหรับกรองตามตำแหน่ง
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('a_position', 'LIKE', "%{$position}%");
    }

    /**
     * Scope สำหรับกรองเฉพาะหัวหน้ากอง
     */
    public function scopeChiefs($query)
    {
        return $query->where('a_position', 'LIKE', '%ก%');
    }

    /**
     * Scope สำหรับกรองเฉพาะหัวหน้าแผนก
     */
    public function scopeHeads($query)
    {
        return $query->where('a_position', 'LIKE', '%ห%');
    }

    /**
     * Scope สำหรับกรองเฉพาะผู้อำนวยการหรือผู้ช่วยผู้อำนวยการ
     */
    public function scopeEngineers($query)
    {
        return $query->where('a_position', 'LIKE', '%อ%') 
                    ->orWhere('a_position', 'LIKE', '%ช.อ%');
    }

    /**
     * Scope สำหรับกรองตามเพศ
     */
    public function scopeByGender($query, $gender)
    {
        if ($gender === 'male') {
            return $query->whereIn('TITLE', ['นาย', 'Mr.', 'วาที่ ร.ต.', 'Act.Sub.Lt.']);
        } elseif ($gender === 'female') {
            return $query->whereIn('TITLE', ['นาง', 'น.ส.', 'Ms.', 'Miss', 'Mrs.']);
        }
        
        return $query;
    }

    // === Static Methods ===

    /**
     * ค้นหาพนักงานตามรหัส
     */
    public static function findByEmployeeId(string $empId): ?CenSus
    {
        Log::info("ค้นหาพนักงานด้วยรหัส: {$empId}");
        return static::where('EMPN', $empId)->first();
    }

    /**
     * ค้นหาพนักงานตามชื่อ
     */
    public static function searchEmployeeByName(string $name): \Illuminate\Database\Eloquent\Collection
    {
        Log::info("ค้นหาพนักงานด้วยชื่อ: {$name}");
        return static::searchByName($name)->get();
    }

    /**
     * รับรายการฝ่ายทั้งหมด
     */
    public static function getAllDivisions(): \Illuminate\Support\Collection
    {
        return static::distinct('fay')
                    ->whereNotNull('fay')
                    ->where('fay', '!=', '')
                    ->orderBy('fay')
                    ->pluck('fay');
    }

    /**
     * รับรายการกองทั้งหมด
     */
    public static function getAllDepartments(): \Illuminate\Support\Collection
    {
        return static::distinct('gong')
                    ->whereNotNull('gong')
                    ->where('gong', '!=', '')
                    ->orderBy('gong')
                    ->pluck('gong');
    }

    /**
     * รับรายการแผนกทั้งหมด
     */
    public static function getAllSections(): \Illuminate\Support\Collection
    {
        return static::distinct('pnang')
                    ->whereNotNull('pnang')
                    ->where('pnang', '!=', '')
                    ->orderBy('pnang')
                    ->pluck('pnang');
    }

    /**
     * รับสถิติพนักงาน
     */
    public static function getEmployeeStatistics(): array
    {
        Log::info('คำนวณสถิติพนักงาน');
        
        return [
            'total' => static::count(),
            'male' => static::byGender('male')->count(),
            'female' => static::byGender('female')->count(),
            'managers' => static::managers()->count(),
            'heads' => static::heads()->count(),
            'engineers' => static::engineers()->count(),
            'divisions' => static::getAllDivisions()->count(),
            'departments' => static::getAllDepartments()->count(),
            'sections' => static::getAllSections()->count(),
        ];
    }
}
