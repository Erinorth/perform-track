<?php

namespace App\Policies;

use App\Models\DivisionRisk;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DivisionRiskPolicy
{
    use HandlesAuthorization;

    /**
     * กำหนดว่าผู้ใช้สามารถดูรายการความเสี่ยงทั้งหมดได้หรือไม่
     */
    public function viewAny(User $user): bool
    {
        return true; // ผู้ใช้ที่ล็อกอินทุกคนสามารถดูรายการได้
    }

    /**
     * กำหนดว่าผู้ใช้สามารถดูรายละเอียดความเสี่ยงได้หรือไม่
     */
    public function view(User $user, DivisionRisk $divisionRisk): bool
    {
        return true; // ผู้ใช้ที่ล็อกอินทุกคนสามารถดูรายละเอียดได้
    }

    /**
     * กำหนดว่าผู้ใช้สามารถสร้างความเสี่ยงใหม่ได้หรือไม่
     */
    public function create(User $user): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('create_division_risk');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถแก้ไขความเสี่ยงได้หรือไม่
     */
    public function update(User $user, DivisionRisk $divisionRisk): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('update_division_risk');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบความเสี่ยงได้หรือไม่
     */
    public function delete(User $user, DivisionRisk $divisionRisk): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        if (!$user->hasPermission('delete_division_risk')) {
            return false;
        }

        // ตรวจสอบว่าความเสี่ยงนี้มีการประเมินความเสี่ยงที่เชื่อมโยงอยู่หรือไม่
        return !$divisionRisk->riskAssessments()->exists();
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบความเสี่ยงหลายรายการพร้อมกันได้หรือไม่
     */
    public function bulkDelete(User $user): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('delete_division_risk');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถอัปโหลดไฟล์แนบได้หรือไม่
     */
    public function uploadAttachment(User $user, DivisionRisk $divisionRisk): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('update_division_risk');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบไฟล์แนบได้หรือไม่
     */
    public function deleteAttachment(User $user, DivisionRisk $divisionRisk): bool
    {
        // ตรวจสอบบทบาทหรือสิทธิ์ของผู้ใช้ (สามารถปรับแต่งตามความเหมาะสม)
        return $user->hasPermission('update_division_risk');
    }
}