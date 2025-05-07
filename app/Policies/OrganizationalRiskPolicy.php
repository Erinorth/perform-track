<?php
/**
 * ไฟล์: app\Policies\OrganizationalRiskPolicy.php
 * กำหนดนโยบายการเข้าถึงและจัดการความเสี่ยงระดับองค์กร
 * ใช้ในระบบ Role-based Access Control สำหรับจัดการสิทธิ์ในแอปพลิเคชัน Risk Assessment
 */

namespace App\Policies;

use App\Models\OrganizationalRisk;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationalRiskPolicy
{
    /**
     * กำหนดว่าผู้ใช้สามารถดูรายการความเสี่ยงระดับองค์กรทั้งหมดได้หรือไม่
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function viewAny(User $user): bool
    {
        // อนุญาตให้ผู้ใช้ทุกคนสามารถดูรายการความเสี่ยงระดับองค์กรได้
        // หากต้องการจำกัดสิทธิ์ ควรตรวจสอบบทบาทผู้ใช้ เช่น
        // return $user->hasRole(['admin', 'risk_manager', 'viewer']);
        return true;
    }

    /**
     * กำหนดว่าผู้ใช้สามารถดูข้อมูลความเสี่ยงระดับองค์กรเฉพาะรายการได้หรือไม่
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @param OrganizationalRisk $organizationalRisk ความเสี่ยงที่ต้องการดู
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function view(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        // อนุญาตให้ผู้ใช้ทุกคนสามารถดูข้อมูลความเสี่ยงระดับองค์กรได้
        return true;
    }

    /**
     * กำหนดว่าผู้ใช้สามารถสร้างความเสี่ยงระดับองค์กรใหม่ได้หรือไม่
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function create(User $user): bool
    {
        // อนุญาตเฉพาะผู้ดูแลระบบหรือผู้จัดการความเสี่ยง
        // ในที่นี้สมมติให้มีเมธอด hasRole() ที่ตรวจสอบบทบาทของผู้ใช้
        return $user->hasRole(['admin', 'risk_manager']);
    }

    /**
     * กำหนดว่าผู้ใช้สามารถแก้ไขข้อมูลความเสี่ยงระดับองค์กรได้หรือไม่
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @param OrganizationalRisk $organizationalRisk ความเสี่ยงที่ต้องการแก้ไข
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function update(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        // อนุญาตเฉพาะผู้ดูแลระบบหรือผู้จัดการความเสี่ยง
        return $user->hasRole(['admin', 'risk_manager']);
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบข้อมูลความเสี่ยงระดับองค์กรได้หรือไม่
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @param OrganizationalRisk $organizationalRisk ความเสี่ยงที่ต้องการลบ
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function delete(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        // อนุญาตเฉพาะผู้ดูแลระบบเท่านั้น
        // การลบเป็นการดำเนินการที่สำคัญและอาจส่งผลกระทบต่อข้อมูลอื่นๆ ที่เกี่ยวข้อง
        return $user->hasRole('admin');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถกู้คืนข้อมูลความเสี่ยงระดับองค์กรที่ถูกลบได้หรือไม่
     * (ใช้กรณีที่มีการใช้ Soft Delete)
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @param OrganizationalRisk $organizationalRisk ความเสี่ยงที่ต้องการกู้คืน
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function restore(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        // อนุญาตเฉพาะผู้ดูแลระบบเท่านั้น
        return $user->hasRole('admin');
    }

    /**
     * กำหนดว่าผู้ใช้สามารถลบข้อมูลความเสี่ยงระดับองค์กรอย่างถาวรได้หรือไม่
     * (ใช้กรณีที่มีการใช้ Soft Delete และต้องการลบข้อมูลออกจากฐานข้อมูลจริงๆ)
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @param OrganizationalRisk $organizationalRisk ความเสี่ยงที่ต้องการลบถาวร
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function forceDelete(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        // อนุญาตเฉพาะผู้ดูแลระบบที่มีสิทธิ์สูงสุดเท่านั้น
        // การลบถาวรเป็นการดำเนินการที่ไม่สามารถย้อนกลับได้และอาจส่งผลกระทบรุนแรง
        return $user->hasRole('super_admin');
    }
    
    /**
     * ตรวจสอบสิทธิ์การเปลี่ยนสถานะของความเสี่ยง (เปิด/ปิดการใช้งาน)
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @param OrganizationalRisk $organizationalRisk ความเสี่ยงที่ต้องการเปลี่ยนสถานะ
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function toggleActive(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        // อนุญาตเฉพาะผู้ดูแลระบบหรือผู้จัดการความเสี่ยง
        return $user->hasRole(['admin', 'risk_manager']);
    }

    /**
     * กำหนดว่าผู้ใช้สามารถดูเอกสารแนบของความเสี่ยงระดับองค์กรได้หรือไม่
     * 
     * @param User $user ผู้ใช้ที่ต้องการตรวจสอบสิทธิ์
     * @param OrganizationalRisk $organizationalRisk ความเสี่ยงที่ต้องการดูเอกสารแนบ
     * @return bool อนุญาตหรือไม่อนุญาต
     */
    public function viewAttachment(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        // บันทึกล็อกการตรวจสอบสิทธิ์การดูเอกสารแนบ
        \Illuminate\Support\Facades\Log::info('ตรวจสอบสิทธิ์การดูเอกสารแนบ', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'risk_id' => $organizationalRisk->id,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กำหนดว่าผู้ใช้ทั้งหมดที่มีสิทธิ์ดูความเสี่ยงสามารถดูเอกสารแนบได้
        // หากต้องการจำกัดสิทธิ์เพิ่มเติม สามารถปรับเงื่อนไขได้ตามความเหมาะสม
        return $this->view($user, $organizationalRisk);
    }
}
