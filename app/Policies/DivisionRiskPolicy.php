<?php

namespace App\Policies;

use App\Models\DivisionRisk;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DivisionRiskPolicy
{
    /**
     * ตรวจสอบสิทธิ์ก่อนการดำเนินการอื่นๆ
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super Admin และ Admin มีสิทธิ์ทั้งหมด
        if ($user->hasRole(['super_admin', 'admin'])) {
            Log::info("User ID: {$user->id} เป็น Super Admin/Admin - อนุญาตทุกการกระทำ");
            return true;
        }

        return null; // ให้ตรวจสอบสิทธิ์ต่อ
    }

    /**
     * ตรวจสอบสิทธิ์การดูรายการความเสี่ยงระดับกอง
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny DivisionRisk สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('division_risk.view') || 
               $user->hasPermissionTo('division_risk.view_own');
    }

    /**
     * ตรวจสอบสิทธิ์การดูความเสี่ยงระดับกองเฉพาะรายการ
     */
    public function view(User $user, DivisionRisk $divisionRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view DivisionRisk ID: ' . $divisionRisk->id);
        
        // ดูได้ทั้งหมด
        if ($user->hasPermissionTo('division_risk.view')) {
            return true;
        }

        // ดูเฉพาะของตนเอง (ตรวจสอบตาม division_id ของ user)
        if ($user->hasPermissionTo('division_risk.view_own')) {
            // TODO: เพิ่มการตรวจสอบ division_id ของ user กับ division_risk
            return true; // ชั่วคราว
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์การสร้างความเสี่ยงระดับกอง
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create DivisionRisk สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('division_risk.create') || 
               $user->hasPermissionTo('division_risk.manage_own');
    }

    /**
     * ตรวจสอบสิทธิ์การแก้ไขความเสี่ยงระดับกอง
     */
    public function update(User $user, DivisionRisk $divisionRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update DivisionRisk ID: ' . $divisionRisk->id);
        
        // แก้ไขได้ทั้งหมด
        if ($user->hasPermissionTo('division_risk.update')) {
            return true;
        }

        // แก้ไขเฉพาะของตนเอง
        if ($user->hasPermissionTo('division_risk.manage_own')) {
            // TODO: เพิ่มการตรวจสอบ division_id
            return true; // ชั่วคราว
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์การลบความเสี่ยงระดับกอง
     */
    public function delete(User $user, DivisionRisk $divisionRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete DivisionRisk ID: ' . $divisionRisk->id);
        
        // ตรวจสอบสิทธิ์พื้นฐาน
        $hasDeletePermission = $user->hasPermissionTo('division_risk.delete') || 
                              ($user->hasPermissionTo('division_risk.manage_own') /* && เป็นของตนเอง */);
        
        if (!$hasDeletePermission) {
            return false;
        }

        // ตรวจสอบว่ามี risk assessments ที่เชื่อมโยงอยู่หรือไม่
        if ($divisionRisk->riskAssessments()->exists()) {
            Log::warning('ไม่สามารถลบความเสี่ยงระดับกองได้ เนื่องจากมีการประเมินความเสี่ยงที่เชื่อมโยงอยู่');
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบสิทธิ์การอนุมัติความเสี่ยงระดับกอง
     */
    public function approve(User $user, DivisionRisk $divisionRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ approve DivisionRisk ID: ' . $divisionRisk->id);
        
        return $user->hasPermissionTo('division_risk.approve');
    }

    /**
     * ตรวจสอบสิทธิ์การอัปโหลดเอกสารแนบ
     */
    public function uploadAttachment(User $user, DivisionRisk $divisionRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ uploadAttachment DivisionRisk ID: ' . $divisionRisk->id);
        
        return $user->hasPermissionTo('attachment.upload') && 
               ($user->hasPermissionTo('division_risk.update') || 
                $user->hasPermissionTo('division_risk.manage_own'));
    }

    /**
     * ตรวจสอบสิทธิ์การลบเอกสารแนบ
     */
    public function deleteAttachment(User $user, DivisionRisk $divisionRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ deleteAttachment DivisionRisk ID: ' . $divisionRisk->id);
        
        return $user->hasPermissionTo('attachment.delete') && 
               ($user->hasPermissionTo('division_risk.update') || 
                $user->hasPermissionTo('division_risk.manage_own'));
    }

    /**
     * ตรวจสอบสิทธิ์การส่งออกข้อมูล
     */
    public function export(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ export DivisionRisk สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('division_risk.export');
    }
}
