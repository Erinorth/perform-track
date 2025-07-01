<?php

namespace App\Policies;

use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RiskAssessmentPolicy
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
     * ตรวจสอบสิทธิ์การดูรายการการประเมินความเสี่ยง
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny RiskAssessment สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_assessment.view') || 
               $user->hasPermissionTo('risk_assessment.view_own');
    }

    /**
     * ตรวจสอบสิทธิ์การดูการประเมินความเสี่ยงเฉพาะรายการ
     */
    public function view(User $user, RiskAssessment $riskAssessment): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view RiskAssessment ID: ' . $riskAssessment->id);
        
        // ดูได้ทั้งหมด
        if ($user->hasPermissionTo('risk_assessment.view')) {
            return true;
        }

        // ดูเฉพาะของตนเอง
        if ($user->hasPermissionTo('risk_assessment.view_own')) {
            // TODO: เพิ่มการตรวจสอบความเป็นเจ้าของ
            return true; // ชั่วคราว
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์การสร้างการประเมินความเสี่ยง
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create RiskAssessment สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_assessment.create') || 
               $user->hasPermissionTo('risk_assessment.manage_own');
    }

    /**
     * ตรวจสอบสิทธิ์การแก้ไขการประเมินความเสี่ยง
     */
    public function update(User $user, RiskAssessment $riskAssessment): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update RiskAssessment ID: ' . $riskAssessment->id);
        
        // แก้ไขได้ทั้งหมด
        if ($user->hasPermissionTo('risk_assessment.update')) {
            return true;
        }

        // แก้ไขเฉพาะของตนเอง
        if ($user->hasPermissionTo('risk_assessment.manage_own')) {
            // TODO: เพิ่มการตรวจสอบความเป็นเจ้าของ
            return true; // ชั่วคราว
        }

        return false;
    }

    /**
     * ตรวจสอบสิทธิ์การลบการประเมินความเสี่ยง
     */
    public function delete(User $user, RiskAssessment $riskAssessment): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete RiskAssessment ID: ' . $riskAssessment->id);
        
        // ตรวจสอบสิทธิ์พื้นฐาน
        $hasDeletePermission = $user->hasPermissionTo('risk_assessment.delete') || 
                              ($user->hasPermissionTo('risk_assessment.manage_own') /* && เป็นของตนเอง */);
        
        if (!$hasDeletePermission) {
            return false;
        }

        // ตรวจสอบว่าอนุมัติแล้วหรือไม่
        if ($riskAssessment->status === 'approved') {
            Log::warning('ไม่สามารถลบการประเมินความเสี่ยงที่อนุมัติแล้วได้');
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบสิทธิ์การอนุมัติการประเมินความเสี่ยง
     */
    public function approve(User $user, RiskAssessment $riskAssessment): bool
    {
        Log::info('ตรวจสอบสิทธิ์ approve RiskAssessment ID: ' . $riskAssessment->id);
        
        // ตรวจสอบระดับความเสี่ยง
        $riskLevel = $riskAssessment->risk_level ?? 'medium';
        
        // ความเสี่ยงระดับสูงต้องใช้สิทธิ์พิเศษ
        if (in_array($riskLevel, ['high', 'very_high'])) {
            return $user->hasPermissionTo('risk_assessment.approve_high');
        }

        // ความเสี่ยงระดับปกติ
        return $user->hasPermissionTo('risk_assessment.approve');
    }

    /**
     * ตรวจสอบสิทธิ์การอัpโหลดเอกสารแนบ
     */
    public function uploadAttachment(User $user, RiskAssessment $riskAssessment): bool
    {
        Log::info('ตรวจสอบสิทธิ์ uploadAttachment RiskAssessment ID: ' . $riskAssessment->id);
        
        return $user->hasPermissionTo('attachment.upload') && 
               ($user->hasPermissionTo('risk_assessment.update') || 
                $user->hasPermissionTo('risk_assessment.manage_own'));
    }

    /**
     * ตรวจสอบสิทธิ์การลบเอกสารแนบ
     */
    public function deleteAttachment(User $user, RiskAssessment $riskAssessment): bool
    {
        Log::info('ตรวจสอบสิทธิ์ deleteAttachment RiskAssessment ID: ' . $riskAssessment->id);
        
        return $user->hasPermissionTo('attachment.delete') && 
               ($user->hasPermissionTo('risk_assessment.update') || 
                $user->hasPermissionTo('risk_assessment.manage_own'));
    }

    /**
     * ตรวจสอบสิทธิ์การส่งออกข้อมูล
     */
    public function export(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ export RiskAssessment สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_assessment.export');
    }

    /**
     * ตรวจสอบสิทธิ์การลบหลายรายการ
     */
    public function bulkDelete(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ bulkDelete RiskAssessment สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('risk_assessment.delete');
    }
}
