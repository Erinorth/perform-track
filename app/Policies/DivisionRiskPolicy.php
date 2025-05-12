<?php
/**
 * ไฟล์: app\Policies\DivisionRiskPolicy.php
 * Policy กำหนดสิทธิ์การเข้าถึงและจัดการข้อมูลความเสี่ยงระดับฝ่าย
 */

namespace App\Policies;

use App\Models\DivisionRisk;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DivisionRiskPolicy
{
    use HandlesAuthorization;

    /**
     * ตรวจสอบว่าผู้ใช้สามารถดูรายการความเสี่ยงระดับฝ่ายทั้งหมดได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // ตัวอย่างเช่น ผู้ใช้ที่มีบทบาทดังนี้สามารถดูรายการได้
        // return $user->hasRole(['admin', 'risk_manager', 'division_head']);
        
        // เปิดให้ทุกคนที่ล็อกอินเข้าใช้งานสามารถดูรายการได้
        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถดูข้อมูลความเสี่ยงระดับฝ่ายเฉพาะรายการได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DivisionRisk  $divisionRisk
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, DivisionRisk $divisionRisk)
    {
        // ตัวอย่างเช่น ผู้ใช้ที่มีบทบาทดังนี้หรือเป็นเจ้าของฝ่ายสามารถดูข้อมูลได้
        // return $user->hasRole(['admin', 'risk_manager']) || $user->division_id === $divisionRisk->division_id;
        
        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถสร้างข้อมูลความเสี่ยงระดับฝ่ายได้หรือไม่
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
     * ตรวจสอบว่าผู้ใช้สามารถอัปเดตข้อมูลความเสี่ยงระดับฝ่ายได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DivisionRisk  $divisionRisk
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, DivisionRisk $divisionRisk)
    {
        // ตัวอย่างเช่น ผู้ใช้ที่มีบทบาทดังนี้หรือเป็นเจ้าของฝ่ายสามารถแก้ไขข้อมูลได้
        // return $user->hasRole(['admin', 'risk_manager']) || $user->division_id === $divisionRisk->division_id;
        
        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบข้อมูลความเสี่ยงระดับฝ่ายได้หรือไม่
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DivisionRisk  $divisionRisk
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, DivisionRisk $divisionRisk)
    {
        // ตัวอย่างเช่น เฉพาะผู้ใช้ที่มีบทบาทดังนี้สามารถลบข้อมูลได้
        // return $user->hasRole(['admin', 'risk_manager']);
        
        return true;
    }
}
