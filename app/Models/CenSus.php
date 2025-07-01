<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Database\Factories\CenSusFactory;

/**
 * Model สำหรับข้อมูลพนักงาน EGAT จากระบบ MMD Data
 * ใช้ dynamic connection ตาม environment (testing/production)
 */
class CenSus extends Model
{
    use HasFactory;

    /**
     * กำหนด connection แบบ dynamic ตาม environment
     */
    protected $connection;

    /**
     * ชื่อตาราง
     */
    protected $table = 'view_census';

    /**
     * Primary key
     */
    protected $primaryKey = 'EMPN';

    /**
     * ประเภท Primary key
     */
    protected $keyType = 'string';

    /**
     * ไม่ใช้ auto increment
     */
    public $incrementing = false;

    /**
     * ไม่ใช้ timestamps
     */
    public $timestamps = false;

    /**
     * Constructor - กำหนด connection ตาม environment
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // ใช้ connection ตาม environment
        $this->connection = app()->environment('testing') 
            ? config('database.testing_mmddata_connection', 'testing_mmddata')
            : 'mmddata';
            
        Log::debug('CenSus Model initialized', [
            'environment' => app()->environment(),
            'connection' => $this->connection
        ]);
    }

    /**
     * Fields ที่สามารถ mass assign ได้
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
     * Type casting
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
     * Relationship กับ User
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'egat_id', 'EMPN');
    }

    /**
     * ค้นหาพนักงานตาม EGAT ID
     */
    public static function findByEgatId(string $egatId): ?self
    {
        Log::info('Searching employee by EGAT ID', ['egat_id' => $egatId]);
        
        try {
            $employee = static::where('EMPN', $egatId)->first();
            
            if ($employee) {
                Log::info('Employee found', [
                    'egat_id' => $egatId,
                    'name' => $employee->NAME,
                    'position' => $employee->a_position
                ]);
            } else {
                Log::warning('Employee not found', ['egat_id' => $egatId]);
            }
            
            return $employee;
        } catch (\Exception $e) {
            Log::error('Error searching employee', [
                'egat_id' => $egatId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * ได้รับ hierarchy ของพนักงานทั้งหมด
     */
    public static function getHierarchy(): Collection
    {
        Log::info('Getting employee hierarchy');
        
        try {
            return static::all()->groupBy(['fay', 'gong', 'pnang']);
        } catch (\Exception $e) {
            Log::error('Error getting hierarchy', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * ได้รับพนักงานในฝ่าย
     */
    public static function getByDivision(string $division): Collection
    {
        Log::info('Getting employees by division', ['division' => $division]);
        
        try {
            return static::where('fay', $division)->get();
        } catch (\Exception $e) {
            Log::error('Error getting employees by division', [
                'division' => $division,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * ได้รับหัวหน้าของฝ่าย/กอง/แผนก
     */
    public static function getSupervisor(string $fay, ?string $gong = null, ?string $pnang = null): ?self
    {
        Log::info('Finding supervisor', [
            'fay' => $fay,
            'gong' => $gong,
            'pnang' => $pnang
        ]);
        
        try {
            $query = static::where('fay', $fay);
            
            if ($pnang) {
                // หาหัวหน้าแผนก
                $supervisor = $query->where('gong', $gong)
                                  ->where('pnang', $pnang)
                                  ->where('a_position', 'ห')
                                  ->first();
                                  
                if (!$supervisor && $gong) {
                    // หาหัวหน้ากอง
                    $supervisor = static::where('fay', $fay)
                                       ->where('gong', $gong)
                                       ->where('a_position', 'ก')
                                       ->first();
                }
            } elseif ($gong) {
                // หาหัวหน้ากอง
                $supervisor = $query->where('gong', $gong)
                                  ->where('a_position', 'ก')
                                  ->first();
            }
            
            // หาผู้อำนวยการฝ่าย
            if (!isset($supervisor)) {
                $supervisor = static::where('fay', $fay)
                                   ->whereIn('a_position', ['อ', 'ช.อ'])
                                   ->first();
            }
            
            if ($supervisor) {
                Log::info('Supervisor found', [
                    'supervisor_id' => $supervisor->EMPN,
                    'name' => $supervisor->NAME,
                    'position' => $supervisor->a_position
                ]);
            } else {
                Log::warning('No supervisor found');
            }
            
            return $supervisor;
        } catch (\Exception $e) {
            Log::error('Error finding supervisor', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Factory methods สำหรับการทดสอบ
     */
    public static function createHierarchy(): Collection
    {
        return CenSusFactory::createHierarchy();
    }

    public static function createSingleDivisionHierarchy(string $division = 'อบค.'): Collection
    {
        return CenSusFactory::createSingleDivisionHierarchy($division);
    }

    public static function createCustomHierarchy(array $config): Collection
    {
        return CenSusFactory::createCustomHierarchy($config);
    }

    public static function getDivisionStructure(): array
    {
        return CenSusFactory::getDivisionStructure();
    }

    public static function getWorkerPositions(): array
    {
        return CenSusFactory::getWorkerPositions();
    }

    /**
     * Accessors
     */
    public function getFullNameThaiAttribute(): string
    {
        return trim($this->TITLE . ' ' . $this->NAME);
    }

    public function getFullNameEnglishAttribute(): string
    {
        return $this->emp_ename ?? '';
    }

    /**
     * ระดับตำแหน่ง
     */
    public function getPositionLevelAttribute(): string
    {
        return match ($this->a_position) {
            'อ' => 'ผู้อำนวยการฝ่าย',
            'ช.อ' => 'ผู้ช่วยผู้อำนวยการฝ่าย',
            'ก' => 'หัวหน้ากอง',
            'ห' => 'หัวหน้าแผนก',
            'ชก.1', 'ชก.2', 'ชก.3' => 'ช่างชำนาญการ',
            'วศ.7', 'วศ.8', 'วศ.9' => 'วิศวกร',
            'ช.6', 'ช.7', 'ช.8' => 'ช่าง',
            'พช.4', 'พช.5', 'พช.6' => 'พนักงานวิชาชีพ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Helper methods
     */
    public function isMale(): bool
    {
        return in_array($this->TITLE, ['นาย', 'Mr.', 'วาที่ ร.ต.']);
    }

    public function isFemale(): bool
    {
        return !$this->isMale();
    }

    public function isDirector(): bool
    {
        return in_array($this->a_position, ['อ', 'ช.อ']);
    }

    public function isChief(): bool
    {
        return $this->a_position === 'ก';
    }

    public function isHead(): bool
    {
        return $this->a_position === 'ห';
    }

    public function isWorker(): bool
    {
        return !in_array($this->a_position, ['อ', 'ช.อ', 'ก', 'ห']);
    }

    /**
     * Scopes
     */
    public function scopeByDivision($query, string $division)
    {
        return $query->where('fay', $division);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('gong', $department);
    }

    public function scopeBySection($query, string $section)
    {
        return $query->where('pnang', $section);
    }

    public function scopeByPosition($query, string $position)
    {
        return $query->where('a_position', $position);
    }

    public function scopeDirectors($query)
    {
        return $query->whereIn('a_position', ['อ', 'ช.อ']);
    }

    public function scopeChiefs($query)
    {
        return $query->where('a_position', 'ก');
    }

    public function scopeHeads($query)
    {
        return $query->where('a_position', 'ห');
    }

    public function scopeWorkers($query)
    {
        return $query->whereNotIn('a_position', ['อ', 'ช.อ', 'ก', 'ห']);
    }

    /**
     * Factory
     */
    protected static function newFactory()
    {
        return CenSusFactory::new();
    }
}
