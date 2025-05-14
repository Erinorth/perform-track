<?php

namespace App\Policies;

use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RiskAssessmentPolicy
{
    use HandlesAuthorization;

    /**
     * กำหนดสิทธิ์การดูรายการประเมินความเสี่ยงทั้งหมด
     */
    public function viewAny(User $user): bool
    {
        return true; // ทุกคนที่เข้าสู่ระบบสามารถดูรายการได้
    }

    /**
     * กำหนดสิทธิ์การดูรายละเอียดการประเมินความเสี่ยง
     */
    public function view(User $user, RiskAssessment $riskAssessment): bool
    {
        return true; // ทุกคนที่เข้าสู่ระบบสามารถดูรายละเอียดได้
    }

    /**
     * กำหนดสิทธิ์การสร้างการประเมินความเสี่ยงใหม่
     */
    public function create(User $user): bool
    {
        // ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการสร้างการประเมินความเสี่ยงหรือไม่
        return $user->hasPermission('create_risk_assessment');
    }

    /**
     * กำหนดสิทธิ์การอัพเดทการประเมินความเสี่ยง
     */
    public function update(User $user, RiskAssessment $riskAssessment): bool
    {
        // ตรวจสอบว่าเป็นผู้สร้างหรือมีสิทธิ์แก้ไขหรือไม่
        return $user->id === $riskAssessment->user_id || 
               $user->hasPermission('update_risk_assessment');
    }

    /**
     * กำหนดสิทธิ์การลบการประเมินความเสี่ยง
     */
    public function delete(User $user, RiskAssessment $riskAssessment): bool
    {
        // ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการลบหรือไม่
        return $user->hasPermission('delete_risk_assessment');
    }

    /**
     * กำหนดสิทธิ์การกู้คืนการประเมินความเสี่ยงที่ถูกลบ
     */
    public function restore(User $user, RiskAssessment $riskAssessment): bool
    {
        return $user->hasPermission('restore_risk_assessment');
    }

    /**
     * กำหนดสิทธิ์การลบถาวร
     */
    public function forceDelete(User $user, RiskAssessment $riskAssessment): bool
    {
        return $user->hasPermission('force_delete_risk_assessment');
    }
}
