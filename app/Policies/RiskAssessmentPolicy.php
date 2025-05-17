<?php

namespace App\Policies;

use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RiskAssessmentPolicy
{
    use HandlesAuthorization;

    /**
     * กำหนดว่าผู้ใช้สามารถดูรายการการประเมินความเสี่ยงทั้งหมดได้หรือไม่
     */
    public function viewAny(User $user): bool
    {
        return true; // ผู้ใช้ที่ล็อกอินทุกคนสามารถดูรายการได้
    }

    /**
     * กำหนดว่าผู้ใช้สามารถดูรายละเอียดการประเมินความเสี่ยงได้หรือไม่
     */
    public function view(User $user, RiskAssessment $riskAssessment): bool
    {
        return true; // ผู้ใช้ที่ล็อกอินทุกคนสามารถดูรายละเอียดได้
    }

    /**
     * กำหนดว่าผู้ใช้สามารถสร้างการประเมินความเสี่ยงใหม่ได้หรือไม่
     */
    public function create(User $user): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('create_risk_assessment');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถแก้ไขการประเมินความเสี่ยงได้หรือไม่
     */
    public function update(User $user, RiskAssessment $riskAssessment): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('update_risk_assessment');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบการประเมินความเสี่ยงได้หรือไม่
     */
    public function delete(User $user, RiskAssessment $riskAssessment): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        if (!$user->hasPermission('delete_risk_assessment')) {
            return false;
        }

        // สามารถลบได้เสมอ เนื่องจากการประเมินความเสี่ยงไม่มีความสัมพันธ์กับข้อมูลอื่น
        // ที่จะถูกกระทบเมื่อลบ (นอกจากเอกสารแนบที่จะลบอัตโนมัติผ่าน cascadeOnDelete)
        return true;
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบการประเมินความเสี่ยงหลายรายการพร้อมกันได้หรือไม่
     */
    public function bulkDelete(User $user): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('delete_risk_assessment');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถอัปโหลดไฟล์แนบได้หรือไม่
     */
    public function uploadAttachment(User $user, RiskAssessment $riskAssessment): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('update_risk_assessment');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบไฟล์แนบได้หรือไม่
     */
    public function deleteAttachment(User $user, RiskAssessment $riskAssessment): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('update_risk_assessment');
    }
}
