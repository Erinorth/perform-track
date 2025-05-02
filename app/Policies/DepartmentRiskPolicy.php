<?php
/**
 * ไฟล์: app\Policies\DepartmentRiskPolicy.php
 * Policy กำหนดสิทธิ์การเข้าถึงและจัดการข้อมูลความเสี่ยงระดับสายงาน
 */

namespace App\Policies;

use App\Models\DepartmentRisk;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentRiskPolicy
{
    use HandlesAuthorization;

    /**
     * ตรวจสอบว่าผู้ใช้สามารถดูรายการความเสี่ยงระดับสายงานทั้งหมดได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // ตัวอย่างเช่น ผู้ใช้ที่มีบทบาทดังนี้สามารถดูรายการได้
        // return $user->hasRole(['admin', 'risk_manager', 'department_head']);
        
        // เปิดให้ทุกคนที่ล็อกอินเข้าใช้งานสามารถดูรายการได้
        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถดูข้อมูลความเสี่ยงระดับสายงานเฉพาะรายการได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DepartmentRisk  $departmentRisk
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, DepartmentRisk $departmentRisk)
    {
        // ตัวอย่างเช่น ผู้ใช้ที่มีบทบาทดังนี้หรือเป็นเจ้าของสายงานสามารถดูข้อมูลได้
        // return $user->hasRole(['admin', 'risk_manager']) || $user->department_id === $departmentRisk->department_id;
        
        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถสร้างข้อมูลความเสี่ยงระดับสายงานได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // ตัวอย่างเช่น ผู้ใช้ที่มีบทบาทดังนี้สามารถสร้างข้อมูลได้
        // return $user->hasRole(['admin', 'risk_manager']);
        
        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถอัปเดตข้อมูลความเสี่ยงระดับสายงานได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DepartmentRisk  $departmentRisk
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, DepartmentRisk $departmentRisk)
    {
        // ตัวอย่างเช่น ผู้ใช้ที่มีบทบาทดังนี้หรือเป็นเจ้าของสายงานสามารถแก้ไขข้อมูลได้
        // return $user->hasRole(['admin', 'risk_manager']) || $user->department_id === $departmentRisk->department_id;
        
        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบข้อมูลความเสี่ยงระดับสายงานได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DepartmentRisk  $departmentRisk
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, DepartmentRisk $departmentRisk)
    {
        // ตัวอย่างเช่น เฉพาะผู้ใช้ที่มีบทบาทดังนี้สามารถลบข้อมูลได้
        // return $user->hasRole(['admin', 'risk_manager']);
        
        return true;
    }
}
