<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'egat_id',
        'name',
        'email',
        'password',
        'company',    // บริษัท
        'department', // แผนก
        'position',   // ตำแหน่ง
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'egat_id' => 'string', // เพิ่ม casting เป็น string
        ];
    }

    /**
     * ความสัมพันธ์กับ Census
     */
    public function census()
    {
        return $this->hasOne(CenSus::class, 'EMPN', 'egat_id');
    }

    /**
     * Accessor สำหรับชื่อเต็มพร้อมตำแหน่ง
     */
    public function getFullNameWithPositionAttribute(): string
    {
        return $this->position ? "{$this->name} ({$this->position})" : $this->name;
    }

    /**
     * Accessor สำหรับข้อมูลองค์กร
     */
    public function getOrganizationInfoAttribute(): string
    {
        $info = [];
        
        if ($this->company) {
            $info[] = $this->company;
        }
        
        if ($this->department) {
            $info[] = $this->department;
        }
        
        return implode(' - ', $info);
    }
}
