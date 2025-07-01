<?php

namespace App\Policies;

use App\Models\OrganizationalRisk;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrganizationalRiskPolicy
{
    /**
     * ตรวจสอบสิทธิ์ก่อนการดำเนินการอื่นๆ
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super Admin มีสิทธิ์ทั้งหมด
        if ($user->hasRole('super_admin')) {
            Log::info("User ID: {$user->id} เป็น Super Admin - อนุญาตทุกการกระทำ");
            return true;
        }

        return null; // ให้ตรวจสอบสิทธิ์ต่อ
    }

    /**
     * ตรวจสอบสิทธิ์การดูรายการความเสี่ยงระดับองค์กร
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny OrganizationalRisk สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('organizational_risk.view');
    }

    /**
     * ตรวจสอบสิทธิ์การดูความเสี่ยงระดับองค์กรเฉพาะรายการ
     */
    public function view(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        return $user->hasPermissionTo('organizational_risk.view');
    }

    /**
     * ตรวจสอบสิทธิ์การสร้างความเสี่ยงระดับองค์กร
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create OrganizationalRisk สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('organizational_risk.create');
    }

    /**
     * ตรวจสอบสิทธิ์การแก้ไขความเสี่ยงระดับองค์กร
     */
    public function update(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        return $user->hasPermissionTo('organizational_risk.update');
    }

    /**
     * ตรวจสอบสิทธิ์การลบความเสี่ยงระดับองค์กร
     */
    public function delete(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        if (!$user->hasPermissionTo('organizational_risk.delete')) {
            return false;
        }

        // ตรวจสอบว่ามีความเสี่ยงระดับฝ่ายที่เชื่อมโยงอยู่หรือไม่
        if ($organizationalRisk->divisionRisks()->exists()) {
            Log::warning('ไม่สามารถลบความเสี่ยงระดับองค์กรได้ เนื่องจากมีความเสี่ยงระดับฝ่ายที่เชื่อมโยงอยู่');
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบสิทธิ์การกู้คืนความเสี่ยงระดับองค์กร
     */
    public function restore(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ restore OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        return $user->hasPermissionTo('organizational_risk.create');
    }

    /**
     * ตรวจสอบสิทธิ์การลบถาวร
     */
    public function forceDelete(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ forceDelete OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        // เฉพาะ Super Admin เท่านั้น
        return $user->hasRole('super_admin');
    }

    /**
     * ตรวจสอบสิทธิ์การเปิด/ปิดใช้งาน
     */
    public function toggleActive(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ toggleActive OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        return $user->hasPermissionTo('organizational_risk.update');
    }

    /**
     * ตรวจสอบสิทธิ์การดูเอกสารแนบ
     */
    public function viewAttachment(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAttachment OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        return $user->hasPermissionTo('attachment.view') && 
               $user->hasPermissionTo('organizational_risk.view');
    }

    /**
     * ตรวจสอบสิทธิ์การอัปโหลดเอกสารแนบ
     */
    public function uploadAttachment(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ uploadAttachment OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        return $user->hasPermissionTo('attachment.upload') && 
               $user->hasPermissionTo('organizational_risk.update');
    }

    /**
     * ตรวจสอบสิทธิ์การลบเอกสารแนบ
     */
    public function deleteAttachment(User $user, OrganizationalRisk $organizationalRisk): bool
    {
        Log::info('ตรวจสอบสิทธิ์ deleteAttachment OrganizationalRisk ID: ' . $organizationalRisk->id);
        
        return $user->hasPermissionTo('attachment.delete') && 
               $user->hasPermissionTo('organizational_risk.update');
    }

    /**
     * ตรวจสอบสิทธิ์การส่งออกข้อมูล
     */
    public function export(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ export OrganizationalRisk สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('organizational_risk.export');
    }

    /**
     * ตรวจสอบสิทธิ์การนำเข้าข้อมูล
     */
    public function import(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ import OrganizationalRisk สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('organizational_risk.import');
    }
}
