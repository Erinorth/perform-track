<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskControlAttachment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'risk_control_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    /**
     * ความสัมพันธ์กับโมเดล RiskControl
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function riskControl()
    {
        return $this->belongsTo(RiskControl::class);
    }
}
