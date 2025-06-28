<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Database\Factories\CenSusFactory;

/**
 * Model สำหรับจัดการข้อมูลพนักงาน CenSus
 */
class CenSus extends Model
{
    use HasFactory;

    protected $table = 'census';
    protected $primaryKey = 'EMPN';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'EMPN',
        'TITLE', 
        'NAME',
        'emp_ename',
        'fay',
        'gong',
        'pnang',
        'a_position'
    ];

    // === Static Factory Methods Wrappers ===

    /**
     * สร้าง hierarchy สำหรับทั้งองค์กร
     */
    public static function createHierarchy(): Collection
    {
        return CenSusFactory::createHierarchy();
    }

    /**
     * สร้าง hierarchy สำหรับฝ่ายเดียว
     */
    public static function createSingleDivisionHierarchy(string $division = 'อบค.'): Collection
    {
        return CenSusFactory::createSingleDivisionHierarchy($division);
    }

    /**
     * สร้าง hierarchy แบบปรับแต่งได้
     */
    public static function createCustomHierarchy(array $config): Collection
    {
        return CenSusFactory::createCustomHierarchy($config);
    }

    /**
     * รับข้อมูลโครงสร้างฝ่าย
     */
    public static function getDivisionStructure(): array
    {
        return CenSusFactory::getDivisionStructure();
    }

    // === Accessor Methods ===

    /**
     * ชื่อเต็มภาษาไทย
     */
    public function getFullNameThaiAttribute(): string
    {
        return trim($this->TITLE . ' ' . $this->NAME);
    }

    /**
     * ชื่อเต็มภาษาอังกฤษ
     */
    public function getFullNameEnglishAttribute(): string
    {
        return $this->emp_ename ?? '';
    }

    /**
     * ระดับตำแหน่ง
     */
    public function getPositionLevelAttribute(): string
    {
        return match (true) {
            str_contains($this->a_position, 'อ') && !str_contains($this->a_position, 'ช.อ') => 'ผู้อำนวยการฝ่าย',
            str_contains($this->a_position, 'ช.อ') => 'ผู้ช่วยผู้อำนวยการฝ่าย',
            str_contains($this->a_position, 'ก') => 'หัวหน้ากอง',
            str_contains($this->a_position, 'ห') => 'หัวหน้าแผนก',
            str_contains($this->a_position, 'วศ.') => 'วิศวกร',
            str_contains($this->a_position, 'ช.') => 'ช่าง',
            str_contains($this->a_position, 'ชก.') => 'ช่างชำนาญการ',
            str_contains($this->a_position, 'พช.') => 'พนักงานวิชาชีพ',
            default => 'ไม่ระบุ',
        };
    }

    // === Helper Methods ===

    /**
     * ตรวจสอบว่าเป็นเพศชายหรือไม่
     */
    public function isMale(): bool
    {
        return in_array($this->TITLE, ['นาย', 'Mr.', 'วาที่ ร.ต.']);
    }

    /**
     * ตรวจสอบว่าเป็นเพศหญิงหรือไม่
     */
    public function isFemale(): bool
    {
        return !$this->isMale();
    }

    /**
     * ตรวจสอบว่าเป็นผู้อำนวยการหรือไม่
     */
    public function isDirector(): bool
    {
        return in_array($this->a_position, ['อ', 'ช.อ']);
    }

    /**
     * ตรวจสอบว่าเป็นหัวหน้ากองหรือไม่
     */
    public function isChief(): bool
    {
        return $this->a_position === 'ก';
    }

    /**
     * ตรวจสอบว่าเป็นหัวหน้าแผนกหรือไม่
     */
    public function isHead(): bool
    {
        return $this->a_position === 'ห';
    }

    /**
     * ตรวจสอบว่าเป็นผู้ปฏิบัติงานหรือไม่
     */
    public function isWorker(): bool
    {
        return !in_array($this->a_position, ['อ', 'ช.อ', 'ก', 'ห']);
    }

    // === Scope Methods ===

    /**
     * Filter ตามฝ่าย
     */
    public function scopeByDivision($query, string $division)
    {
        return $query->where('fay', $division);
    }

    /**
     * Filter ตามกอง
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('gong', $department);
    }

    /**
     * Filter ตามแผนก
     */
    public function scopeBySection($query, string $section)
    {
        return $query->where('pnang', $section);
    }

    /**
     * Filter ตามตำแหน่ง
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('a_position', $position);
    }

    /**
     * Filter ผู้บริหาร
     */
    public function scopeDirectors($query)
    {
        return $query->whereIn('a_position', ['อ', 'ช.อ']);
    }

    /**
     * Filter หัวหน้ากอง
     */
    public function scopeChiefs($query)
    {
        return $query->where('a_position', 'ก');
    }

    /**
     * Filter หัวหน้าแผนก
     */
    public function scopeHeads($query)
    {
        return $query->where('a_position', 'ห');
    }

    /**
     * Filter ผู้ปฏิบัติงาน
     */
    public function scopeWorkers($query)
    {
        return $query->whereNotIn('a_position', ['อ', 'ช.อ', 'ก', 'ห']);
    }

    /**
     * กำหนด Factory ที่ใช้
     */
    protected static function newFactory()
    {
        return CenSusFactory::new();
    }
}
