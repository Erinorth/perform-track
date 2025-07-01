<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RiskControl;
use Illuminate\Support\Facades\Log;

class RiskControlPolicy
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
     * ตรวจสอบสิทธิ์การดูรายการการควบคุมความเสี่ยง
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_control.view') || 
               $user->hasPermissionTo('risk_control.view_own');
    }

    /**
     * ตรวจสอบสิทธิ์การดูการควบคุมความเสี่ยงเฉพาะรายการ
     */
    public function view(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view RiskControl ID: ' . $riskControl->id);
        
        // ดูได้ทั้งหมด
        if ($user->hasPermissionTo('risk_control.view')) {
            return true;
        }

        // ดูเฉพาะของตนเอง
        if ($user->hasPermissionTo('risk_control.view_own')) {
            // TODO: เพิ่มการตรวจสอบความเป็นเจ้าของ
            return true; // ชั่วคราว
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์การสร้างการควบคุมความเสี่ยง
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_control.create') || 
               $user->hasPermissionTo('risk_control.manage_own');
    }

    /**
     * ตรวจสอบสิทธิ์การแก้ไขการควบคุมความเสี่ยง
     */
    public function update(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update RiskControl ID: ' . $riskControl->id);
        
        // แก้ไขได้ทั้งหมด
        if ($user->hasPermissionTo('risk_control.update')) {
            return true;
        }

        // แก้ไขเฉพาะของตนเอง
        if ($user->hasPermissionTo('risk_control.manage_own')) {
            // TODO: เพิ่มการตรวจสอบความเป็นเจ้าของ
            return true; // ชั่วคราว
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์การลบการควบคุมความเสี่ยง
     */
    public function delete(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete RiskControl ID: ' . $riskControl->id);
        
        // ตรวจสอบสิทธิ์พื้นฐาน
        $hasDeletePermission = $user->hasPermissionTo('risk_control.delete') || 
                              ($user->hasPermissionTo('risk_control.manage_own') /* && เป็นของตนเอง */);
        
        if (!$hasDeletePermission) {
            return false;
        }

        // ตรวจสอบว่าสามารถลบได้หรือไม่ตามเงื่อนไขของ Model
        if (method_exists($riskControl, 'canBeDeleted') && !$riskControl->canBeDeleted()) {
            Log::warning('Risk Control ไม่สามารถลบได้ตามเงื่อนไขของ Model');
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบสิทธิ์การกู้คืน
     */
    public function restore(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ restore RiskControl ID: ' . $riskControl->id);
        
        return $user->hasPermissionTo('risk_control.create');
    }

    /**
     * ตรวจสอบสิทธิ์การลบถาวร
     */
    public function forceDelete(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ forceDelete RiskControl ID: ' . $riskControl->id);
        
        // เฉพาะ Super Admin และ Admin เท่านั้น
        return $user->hasRole(['super_admin', 'admin']);
    }

    /**
     * ตรวจสอบสิทธิ์การเปิด/ปิดใช้งาน
     */
    public function toggleStatus(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ toggleStatus RiskControl ID: ' . $riskControl->id);
        
        return $user->hasPermissionTo('risk_control.activate') || 
               $user->hasPermissionTo('risk_control.deactivate') ||
               ($user->hasPermissionTo('risk_control.manage_own') /* && เป็นของตนเอง */);
    }

    /**
     * ตรวจสอบสิทธิ์การอัปโหลดเอกสารแนบ
     */
    public function uploadAttachment(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ uploadAttachment RiskControl ID: ' . $riskControl->id);
        
        return $user->hasPermissionTo('attachment.upload') && 
               ($user->hasPermissionTo('risk_control.update') || 
                $user->hasPermissionTo('risk_control.manage_own'));
    }

    /**
     * ตรวจสอบสิทธิ์การลบเอกสารแนบ
     */
    public function deleteAttachment(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ deleteAttachment RiskControl ID: ' . $riskControl->id);
        
        return $user->hasPermissionTo('attachment.delete') && 
               ($user->hasPermissionTo('risk_control.update') || 
                $user->hasPermissionTo('risk_control.manage_own'));
    }

    /**
     * ตรวจสอบสิทธิ์การส่งออกข้อมูล
     */
    public function export(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ export RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_control.export');
    }

    /**
     * ตรวจสอบสิทธิ์การนำเข้าข้อมูล
     */
    public function import(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ import RiskControl สำหรับ User ID: ' . $user->id);
        
        // ใช้สิทธิ์เดียวกับการสร้าง
        return $user->hasPermissionTo('risk_control.create');
    }

    /**
     * ตรวจสอบสิทธิ์การดูรายงาน
     */
    public function viewReports(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewReports RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('report.view');
    }

    /**
     * ตรวจสอบสิทธิ์การลบหลายรายการ
     */
    public function bulkDelete(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ bulkDelete RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_control.delete');
    }
}
