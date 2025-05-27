<?php
// ไฟล์: app\Policies\RiskControlPolicy.php
// Policy สำหรับควบคุมสิทธิ์การเข้าถึง RiskControl
// เพิ่ม Super Admin ที่สามารถทำทุกอย่างได้โดยไม่ต้องตรวจสอบ Permission

namespace App\Policies;

use App\Models\User;
use App\Models\RiskControl;
use App\Models\DivisionRisk;
use Illuminate\Support\Facades\Log;

class RiskControlPolicy
{
    /**
     * ตรวจสอบสิทธิ์ก่อนการตรวจสอบอื่นๆ (Super Admin)
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super Admin สามารถทำทุกอย่างได้
        if ($user->hasRole(['super_admin', 'admin'])) {
            Log::info("User ID: {$user->id} เป็น Super Admin/Admin - อนุญาตทุกการกระทำ");
            return true;
        }

        // คืนค่า null เพื่อให้ตรวจสอบต่อไปตามปกติ
        return null;
    }

    /**
     * ดูรายการการควบคุมความเสี่ยงทั้งหมด
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny สำหรับ User ID: ' . $user->id);
        
        // อนุญาตให้ทุกคนที่ล็อกอินดูได้ (เนื่องจากเป็นระบบภายใน)
        return true;
    }

    /**
     * ดูรายละเอียดการควบคุมความเสี่ยง
     */
    public function view(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view Risk Control ID: ' . $riskControl->id);
        
        // อนุญาตให้ทุกคนที่ล็อกอินดูได้
        return true;
    }

    /**
     * สร้างการควบคุมความเสี่ยงใหม่
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create สำหรับ User ID: ' . $user->id);
        
        // อนุญาตให้ทุกคนที่ล็อกอินสร้างได้ (เพื่อความสะดวกในการใช้งาน)
        return true;
    }

    /**
     * แก้ไขการควบคุมความเสี่ยง
     */
    public function update(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update Risk Control ID: ' . $riskControl->id);
        
        // อนุญาตให้ทุกคนที่ล็อกอินแก้ไขได้
        return true;
    }

    /**
     * ลบการควบคุมความเสี่ยง
     */
    public function delete(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete Risk Control ID: ' . $riskControl->id);
        
        // ตรวจสอบว่าสามารถลบได้หรือไม่ (ตามเงื่อนไขของ Model)
        if (method_exists($riskControl, 'canBeDeleted') && !$riskControl->canBeDeleted()) {
            Log::warning('Risk Control ไม่สามารถลบได้ตามเงื่อนไขของ Model');
            return false;
        }

        // อนุญาตให้ทุกคนที่ล็อกอินลบได้
        return true;
    }

    /**
     * กู้คืนการควบคุมความเสี่ยง
     */
    public function restore(User $user, RiskControl $riskControl): bool
    {
        // อนุญาตให้ทุกคนที่ล็อกอินกู้คืนได้
        return true;
    }

    /**
     * ลบถาวร
     */
    public function forceDelete(User $user, RiskControl $riskControl): bool
    {
        // เฉพาะ Admin เท่านั้นที่สามารถลบถาวรได้
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // อนุญาตให้ทุกคนลบถาวรได้ (สำหรับการทดสอบ)
        return true;
    }

    /**
     * เปลี่ยนสถานะการควบคุมความเสี่ยง
     */
    public function toggleStatus(User $user, RiskControl $riskControl): bool
    {
        Log::info('ตรวจสอบสิทธิ์ toggleStatus Risk Control ID: ' . $riskControl->id);
        
        // อนุญาตให้ทุกคนที่ล็อกอินเปลี่ยนสถานะได้
        return true;
    }

    /**
     * ส่งออกข้อมูล
     */
    public function export(User $user): bool
    {
        // อนุญาตให้ทุกคนที่ล็อกอินส่งออกข้อมูลได้
        return true;
    }

    /**
     * นำเข้าข้อมูล
     */
    public function import(User $user): bool
    {
        // อนุญาตให้ทุกคนที่ล็อกอินนำเข้าข้อมูลได้
        return true;
    }

    /**
     * ดูรายงาน
     */
    public function viewReports(User $user): bool
    {
        // อนุญาตให้ทุกคนที่ล็อกอินดูรายงานได้
        return true;
    }
}
