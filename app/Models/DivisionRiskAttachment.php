<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DivisionRiskAttachment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'division_risk_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    /**
     * ความสัมพันธ์กับโมเดล DivisionRisk
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function divisionRisk()
    {
        return $this->belongsTo(DivisionRisk::class);
    }
}
