<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class DashboardPolicy
{
    /**
     * ตรวจสอบสิทธิ์ดู Dashboard
     */
    public function view(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view');
    }

    /**
     * ตรวจสอบสิทธิ์ดู Dashboard ทั้งหมด
     */
    public function viewAll(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAll Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view_all');
    }

    /**
     * ตรวจสอบสิทธิ์ดู Dashboard ระดับกอง
     */
    public function viewDivision(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewDivision Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view_division');
    }

    /**
     * ตรวจสอบสิทธิ์ดู Dashboard ระดับแผนก
     */
    public function viewDepartment(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewDepartment Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view_department');
    }

    /**
     * ตรวจสอบสิทธิ์ส่งออกรายงาน Dashboard
     */
    public function export(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ export Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.export');
    }

    /**
     * ตรวจสอบสิทธิ์กรองข้อมูล Dashboard
     */
    public function filter(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ filter Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.filter');
    }
}
