<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model สำหรับข้อมูล Census จากฐานข้อมูล MMDDATA
 * เชื่อมต่อกับ view_census เพื่อดึงข้อมูลพนักงาน EGAT
 */
class CenSus extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     */
    protected $table = 'view_census';

    /**
     * ชื่อการเชื่อมต่อฐานข้อมูล
     * ใช้ mmddata สำหรับ production และ testing_mmddata สำหรับการทดสอบ
     */
    protected $connection = 'mmddata';

    /**
     * Primary key ของตาราง
     */
    protected $primaryKey = 'EMPN';

    /**
     * ประเภทของ primary key
     */
    protected $keyType = 'string';

    /**
     * ไม่ใช้ auto incrementing primary key
     */
    public $incrementing = false;

    /**
     * ไม่ใช้ timestamps (created_at, updated_at)
     */
    public $timestamps = false;

    /**
     * ฟิลด์ที่สามารถกรอกข้อมูลได้
     */
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

    /**
     * ฟิลด์ที่ถูก cast เป็นประเภทข้อมูลเฉพาะ
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
     * กำหนด connection แบบ dynamic ตาม environment
     */
    public function getConnectionName()
    {
        if (app()->environment('testing')) {
            return 'testing_mmddata';
        }
        
        return $this->connection ?? config('database.default');
    }

    /**
     * Override newQuery เพื่อใช้ connection ที่ถูกต้อง
     */
    public function newQuery()
    {
        $connection = $this->getConnectionName();
        return $this->setConnection($connection)->newModelQuery();
    }

    /**
     * Accessor สำหรับชื่อเต็ม (ไทย)
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->TITLE ?? '') . ' ' . ($this->NAME ?? ''));
    }

    /**
     * Accessor สำหรับชื่อเต็ม (อังกฤษ)
     */
    public function getFullEnglishNameAttribute(): string
    {
        return trim($this->emp_ename ?? '');
    }

    /**
     * Accessor สำหรับแผนก (Department)
     */
    public function getDepartmentAttribute(): ?string
    {
        return $this->pnang;
    }

    /**
     * Accessor สำหรับส่วนงาน (Section)
     */
    public function getSectionAttribute(): ?string
    {
        return $this->gong;
    }

    /**
     * Accessor สำหรับตำแหน่ง (Position)
     */
    public function getPositionAttribute(): ?string
    {
        return $this->a_position;
    }

    /**
     * Accessor สำหรับฝ่าย (Division)
     */
    public function getDivisionAttribute(): ?string
    {
        return $this->fay;
    }

    /**
     * Scope สำหรับค้นหาตาม EMPN
     */
    public function scopeByEmployeeNumber($query, string $empn)
    {
        return $query->where('EMPN', $empn);
    }

    /**
     * Scope สำหรับค้นหาตามแผนก
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('pnang', $department);
    }

    /**
     * Scope สำหรับค้นหาตามส่วนงาน
     */
    public function scopeBySection($query, string $section)
    {
        return $query->where('gong', $section);
    }

    /**
     * Scope สำหรับค้นหาตามตำแหน่ง
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('a_position', $position);
    }

    /**
     * ความสัมพันธ์กับ User model (หากมี)
     */
    public function user()
    {
        return $this->hasOne(User::class, 'egat_id', 'EMPN');
    }

    /**
     * ตรวจสอบว่าพนักงานคนนี้มี User account หรือไม่
     */
    public function hasUserAccount(): bool
    {
        return $this->user()->exists();
    }

    /**
     * แปลงข้อมูลเป็น array สำหรับการสร้าง User
     */
    public function toUserData(): array
    {
        return [
            'egat_id' => $this->EMPN,
            'name' => $this->full_name,
            'email' => $this->EMPN . '@egat.co.th',
            'company' => 'EGAT',
            'department' => $this->department,
            'position' => $this->position,
        ];
    }

    /**
     * Static method สำหรับการค้นหาในการทดสอบ
     */
    public static function testingQuery()
    {
        return (new static)->setConnection('testing_mmddata')->newQuery();
    }
}
