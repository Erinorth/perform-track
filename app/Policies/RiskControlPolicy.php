<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RiskControl;
use Illuminate\Support\Facades\Log;

class RiskControlPolicy
{
    /**
     * ตรวจสอบสิทธิ์ก่อนการดำเนินการ
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super Admin และ Admin มีสิทธิ์ทั้งหมด
        if ($user->hasRole(['super_admin', 'admin'])) {
            Log::info("User ID: {$user->id} เป็น Super Admin/Admin - อนุญาตทุกการกระทำ");
            return true;
        }

        return null; // ให้ตรวจสอบต่อไปตาม method อื่นๆ
    }

    /**
     * ตรวจสอบสิทธิ์ดูรายการ Risk Control
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_control.view') || 
               $user->hasPermissionTo('risk_control.view_own');
    }

    /**
     * ตรวจสอบสิทธิ์ดู Risk Control รายการเดียว
     */
    public function view(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view RiskControl ID: ' . $riskControl->id);
        
        // ถ้ามีสิทธิ์ดูทั้งหมด
        if ($user->hasPermissionTo('risk_control.view')) {
            return true;
        }

        // ถ้ามีสิทธิ์ดูของตนเองเท่านั้น
        if ($user->hasPermissionTo('risk_control.view_own')) {
            // ตรวจสอบ ownership ผ่าน division/department
            return $this->checkOwnership($user, $riskControl);
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์สร้าง Risk Control
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_control.create') || 
               $user->hasPermissionTo('risk_control.manage_own');
    }

    /**
     * ตรวจสอบสิทธิ์แก้ไข Risk Control
     */
    public function update(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update RiskControl ID: ' . $riskControl->id);
        
        // ถ้ามีสิทธิ์แก้ไขทั้งหมด
        if ($user->hasPermissionTo('risk_control.update')) {
            return true;
        }

        // ถ้ามีสิทธิ์จัดการของตนเองเท่านั้น
        if ($user->hasPermissionTo('risk_control.manage_own')) {
            return $this->checkOwnership($user, $riskControl);
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์ลบ Risk Control
     */
    public function delete(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete RiskControl ID: ' . $riskControl->id);
        
        // ตรวจสอบสิทธิ์พื้นฐาน
        $hasDeletePermission = $user->hasPermissionTo('risk_control.delete') || 
                              ($user->hasPermissionTo('risk_control.manage_own') && 
                               $this->checkOwnership($user, $riskControl));
        
        if (!$hasDeletePermission) {
            return false;
        }

        // ตรวจสอบเงื่อนไขเพิ่มเติม
        if (method_exists($riskControl, 'canBeDeleted') && !$riskControl->canBeDeleted()) {
            Log::warning('Risk Control ไม่สามารถลบได้ตามเงื่อนไขของ Model');
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบสิทธิ์เปลี่ยนสถานะ
     */
    public function toggleStatus(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ toggleStatus RiskControl ID: ' . $riskControl->id);
        
        return $user->hasPermissionTo('risk_control.toggle_status') || 
               $user->hasPermissionTo('risk_control.activate') || 
               $user->hasPermissionTo('risk_control.deactivate') ||
               ($user->hasPermissionTo('risk_control.manage_own') && 
                $this->checkOwnership($user, $riskControl));
    }

    /**
     * ตรวจสอบสิทธิ์อัปโหลดเอกสารแนบ
     */
    public function uploadAttachment(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ uploadAttachment RiskControl ID: ' . $riskControl->id);
        
        return $user->hasPermissionTo('attachment.upload') && 
               ($user->hasPermissionTo('risk_control.update') || 
                ($user->hasPermissionTo('risk_control.manage_own') && 
                 $this->checkOwnership($user, $riskControl)));
    }

    /**
     * ตรวจสอบสิทธิ์ลบเอกสารแนบ
     */
    public function deleteAttachment(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ deleteAttachment RiskControl ID: ' . $riskControl->id);
        
        return $user->hasPermissionTo('attachment.delete') && 
               ($user->hasPermissionTo('risk_control.update') || 
                ($user->hasPermissionTo('risk_control.manage_own') && 
                 $this->checkOwnership($user, $riskControl)));
    }

    /**
     * ตรวจสอบสิทธิ์ส่งออกข้อมูล
     */
    public function export(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ export RiskControl สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_control.export');
    }

    /**
     * ตรวจสอบ ownership ของ Risk Control
     */
    private function checkOwnership(User $user, RiskControl $riskControl): bool
    {
        // สามารถปรับแต่งตามโครงสร้างองค์กรของระบบ
        // ตัวอย่าง: ตรวจสอบผ่าน division หรือ department
        
        // ถ้า Risk Control เชื่อมกับ Division Risk
        if ($riskControl->divisionRisk) {
            // ตรวจสอบว่า user อยู่ใน division เดียวกันหรือไม่
            return $user->division_id === $riskControl->divisionRisk->division_id;
        }
        
        return false;
    }
}
