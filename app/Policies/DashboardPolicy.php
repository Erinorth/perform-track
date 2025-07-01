<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class DashboardPolicy
{
    /**
     * ตรวจสอบสิทธิ์การดู Dashboard
     */
    public function view(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view');
    }

    /**
     * ตรวจสอบสิทธิ์การดู Dashboard ทั้งหมด
     */
    public function viewAll(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAll Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view_all');
    }

    /**
     * ตรวจสอบสิทธิ์การดู Dashboard ระดับกอง
     */
    public function viewDivision(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewDivision Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view_division');
    }

    /**
     * ตรวจสอบสิทธิ์การดู Dashboard ระดับแผนก
     */
    public function viewDepartment(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewDepartment Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.view_department');
    }

    /**
     * ตรวจสอบสิทธิ์การส่งออกรายงาน Dashboard
     */
    public function export(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ export Dashboard สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('dashboard.export');
    }
}
