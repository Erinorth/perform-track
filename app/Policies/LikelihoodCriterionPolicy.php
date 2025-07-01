<?php

namespace App\Policies;

use App\Models\LikelihoodCriterion;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LikelihoodCriterionPolicy
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
     * ตรวจสอบสิทธิ์การดูรายการเกณฑ์โอกาส
     */
    public function viewAny(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ viewAny LikelihoodCriterion สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('criteria.view');
    }

    /**
     * ตรวจสอบสิทธิ์การดูเกณฑ์โอกาสเฉพาะรายการ
     */
    public function view(User $user, LikelihoodCriterion $likelihoodCriterion): bool
    {
        Log::info('ตรวจสอบสิทธิ์ view LikelihoodCriterion ID: ' . $likelihoodCriterion->id);
        
        return $user->hasPermissionTo('criteria.view');
    }

    /**
     * ตรวจสอบสิทธิ์การสร้างเกณฑ์โอกาส
     */
    public function create(User $user): bool
    {
        Log::info('ตรวจสอบสิทธิ์ create LikelihoodCriterion สำหรับ User ID: ' . $user->id);
        
        return $user->hasPermissionTo('criteria.create');
    }

    /**
     * ตรวจสอบสิทธิ์การแก้ไขเกณฑ์โอกาส
     */
    public function update(User $user, LikelihoodCriterion $likelihoodCriterion): bool
    {
        Log::info('ตรวจสอบสิทธิ์ update LikelihoodCriterion ID: ' . $likelihoodCriterion->id);
        
        return $user->hasPermissionTo('criteria.update');
    }

    /**
     * ตรวจสอบสิทธิ์การลบเกณฑ์โอกาส
     */
    public function delete(User $user, LikelihoodCriterion $likelihoodCriterion): bool
    {
        Log::info('ตรวจสอบสิทธิ์ delete LikelihoodCriterion ID: ' . $likelihoodCriterion->id);
        
        if (!$user->hasPermissionTo('criteria.delete')) {
            return false;
        }

        // ตรวจสอบว่ามีการใช้งานอยู่หรือไม่
        if ($likelihoodCriterion->riskAssessments()->exists()) {
            Log::warning('ไม่สามารถลบเกณฑ์โอกาสได้ เนื่องจากมีการใช้งานในการประเมินความเสี่ยง');
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบสิทธิ์การกู้คืน
     */
    public function restore(User $user, LikelihoodCriterion $likelihoodCriterion): bool
    {
        return $user->hasPermissionTo('criteria.create');
    }

    /**
     * ตรวจสอบสิทธิ์การลบถาวร
     */
    public function forceDelete(User $user, LikelihoodCriterion $likelihoodCriterion): bool
    {
        return $user->hasRole('super_admin');
    }
}
