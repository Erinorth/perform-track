<?php

namespace App\Policies;

use App\Models\ImpactCriterion;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ImpactCriterionPolicy
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
     * ตรวจสอบสิทธิ์การดูรายการเกณฑ์ผลกระทบ
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny ImpactCriterion สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('criteria.view');
    }

    /**
     * ตรวจสอบสิทธิ์การดูเกณฑ์ผลกระทบเฉพาะรายการ
     */
    public function view(User $user, ImpactCriterion $impactCriterion): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view ImpactCriterion ID: ' . $impactCriterion->id);
        
        return $user->hasPermissionTo('criteria.view');
    }

    /**
     * ตรวจสอบสิทธิ์การสร้างเกณฑ์ผลกระทบ
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create ImpactCriterion สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('criteria.create');
    }

    /**
     * ตรวจสอบสิทธิ์การแก้ไขเกณฑ์ผลกระทบ
     */
    public function update(User $user, ImpactCriterion $impactCriterion): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update ImpactCriterion ID: ' . $impactCriterion->id);
        
        return $user->hasPermissionTo('criteria.update');
    }

    /**
     * ตรวจสอบสิทธิ์การลบเกณฑ์ผลกระทบ
     */
    public function delete(User $user, ImpactCriterion $impactCriterion): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete ImpactCriterion ID: ' . $impactCriterion->id);
        
        if (!$user->hasPermissionTo('criteria.delete')) {
            return false;
        }

        // ตรวจสอบว่ามีการใช้งานอยู่หรือไม่
        if ($impactCriterion->riskAssessments()->exists()) {
            Log::warning('ไม่สามารถลบเกณฑ์ผลกระทบได้ เนื่องจากมีการใช้งานในการประเมินความเสี่ยง');
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบสิทธิ์การกู้คืน
     */
    public function restore(User $user, ImpactCriterion $impactCriterion): bool
    {
        return $user->hasPermissionTo('criteria.create');
    }

    /**
     * ตรวจสอบสิทธิ์การลบถาวร
     */
    public function forceDelete(User $user, ImpactCriterion $impactCriterion): bool
    {
        return $user->hasRole('super_admin');
    }
}
