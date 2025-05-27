<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RiskControl;
use App\Models\DivisionRisk;

// Policy สำหรับควบคุมสิทธิ์การเข้าถึง RiskControl
class RiskControlPolicy
{
    /**
     * ดูรายการการควบคุมความเสี่ยงทั้งหมด
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_risk_controls') || 
               $user->hasRole(['admin', 'risk_manager', 'viewer']);
    }

    /**
     * ดูรายละเอียดการควบคุมความเสี่ยง
     */
    public function view(User $user, RiskControl $riskControl): bool
    {
        // Admin และ Risk Manager ดูได้ทั้งหมด
        if ($user->hasRole(['admin', 'risk_manager'])) {
            return true;
        }

        // ผู้รับผิดชอบสามารถดูของตัวเองได้
        if ($riskControl->owner === $user->name || $riskControl->owner === $user->email) {
            return true;
        }

        // ผู้ที่มีสิทธิ์ดูทั่วไป
        return $user->hasPermissionTo('view_risk_controls');
    }

    /**
     * สร้างการควบคุมความเสี่ยงใหม่
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_risk_controls') || 
               $user->hasRole(['admin', 'risk_manager', 'division_manager']);
    }

    /**
     * แก้ไขการควบคุมความเสี่ยง
     */
    public function update(User $user, RiskControl $riskControl): bool
    {
        // Admin สามารถแก้ไขได้ทั้งหมด
        if ($user->hasRole('admin')) {
            return true;
        }

        // Risk Manager สามารถแก้ไขได้ทั้งหมด
        if ($user->hasRole('risk_manager')) {
            return true;
        }

        // ผู้รับผิดชอบสามารถแก้ไขของตัวเองได้
        if ($riskControl->owner === $user->name || $riskControl->owner === $user->email) {
            return true;
        }

        // ตรวจสอบสิทธิ์ทั่วไป
        return $user->hasPermissionTo('edit_risk_controls');
    }

    /**
     * ลบการควบคุมความเสี่ยง
     */
    public function delete(User $user, RiskControl $riskControl): bool
    {
        // ตรวจสอบว่าสามารถลบได้หรือไม่
        if (!$riskControl->canBeDeleted()) {
            return false;
        }

        // Admin สามารถลบได้ทั้งหมด
        if ($user->hasRole('admin')) {
            return true;
        }

        // Risk Manager สามารถลบได้
        if ($user->hasRole('risk_manager')) {
            return true;
        }

        // ตรวจสอบสิทธิ์ทั่วไป
        return $user->hasPermissionTo('delete_risk_controls');
    }

    /**
     * กู้คืนการควบคุมความเสี่ยง
     */
    public function restore(User $user, RiskControl $riskControl): bool
    {
        return $user->hasRole(['admin', 'risk_manager']) || 
               $user->hasPermissionTo('restore_risk_controls');
    }

    /**
     * ลบถาวร
     */
    public function forceDelete(User $user, RiskControl $riskControl): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * เปลี่ยนสถานะการควบคุมความเสี่ยง
     */
    public function toggleStatus(User $user, RiskControl $riskControl): bool
    {
        // Admin และ Risk Manager สามารถเปลี่ยนสถานะได้
        if ($user->hasRole(['admin', 'risk_manager'])) {
            return true;
        }

        // ผู้รับผิดชอบสามารถเปลี่ยนสถานะของตัวเองได้
        if ($riskControl->owner === $user->name || $riskControl->owner === $user->email) {
            return true;
        }

        return $user->hasPermissionTo('edit_risk_controls');
    }

    /**
     * ส่งออกข้อมูล
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_risk_controls') || 
               $user->hasRole(['admin', 'risk_manager']);
    }

    /**
     * นำเข้าข้อมูล
     */
    public function import(User $user): bool
    {
        return $user->hasPermissionTo('import_risk_controls') || 
               $user->hasRole(['admin', 'risk_manager']);
    }

    /**
     * ดูรายงาน
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermissionTo('view_risk_reports') || 
               $user->hasRole(['admin', 'risk_manager', 'division_manager', 'viewer']);
    }
}
