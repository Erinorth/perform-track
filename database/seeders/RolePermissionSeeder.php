<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class RolePermissionSeeder extends Seeder
{
    /**
     * เรียกใช้งาน seeder
     */
    public function run()
    {
        Log::info('เริ่มต้นการสร้าง Roles และ Permissions');

        // สร้าง Permissions ทั้งหมด
        $this->createPermissions();

        // สร้าง Roles และกำหนด Permissions
        $this->createRoleWithPermissions();

        Log::info('สร้าง Roles และ Permissions เสร็จสิ้น');
    }

    /**
     * สร้าง Permissions ทั้งหมด
     */
    private function createPermissions()
    {
        Log::info('กำลังสร้าง Permissions');

        $permissions = [
            // Dashboard Permissions
            'dashboard.view' => 'ดูหน้า Dashboard',
            'dashboard.export' => 'ส่งออกรายงาน Dashboard',
            'dashboard.view_all' => 'ดู Dashboard ทั้งหมด',
            'dashboard.view_division' => 'ดู Dashboard ระดับกอง',
            'dashboard.view_department' => 'ดู Dashboard ระดับแผนก',
            
            // Organizational Risk Permissions
            'organizational_risk.view' => 'ดูความเสี่ยงระดับองค์กร',
            'organizational_risk.create' => 'สร้างความเสี่ยงระดับองค์กร',
            'organizational_risk.update' => 'แก้ไขความเสี่ยงระดับองค์กร',
            'organizational_risk.delete' => 'ลบความเสี่ยงระดับองค์กร',
            'organizational_risk.export' => 'ส่งออกข้อมูลความเสี่ยงระดับองค์กร',
            'organizational_risk.import' => 'นำเข้าข้อมูลความเสี่ยงระดับองค์กร',
            
            // Division Risk Permissions (ระดับกอง)
            'division_risk.view' => 'ดูความเสี่ยงระดับกอง',
            'division_risk.create' => 'สร้างความเสี่ยงระดับกอง',
            'division_risk.update' => 'แก้ไขความเสี่ยงระดับกอง',
            'division_risk.delete' => 'ลบความเสี่ยงระดับกอง',
            'division_risk.export' => 'ส่งออกข้อมูลความเสี่ยงระดับกอง',
            'division_risk.view_own' => 'ดูความเสี่ยงระดับกองของตนเอง',
            'division_risk.manage_own' => 'จัดการความเสี่ยงระดับกองของตนเอง',
            'division_risk.approve' => 'อนุมัติความเสี่ยงระดับกอง',
            
            // Department Risk Permissions (ระดับแผนก)
            'department_risk.view' => 'ดูความเสี่ยงระดับแผนก',
            'department_risk.create' => 'สร้างความเสี่ยงระดับแผนก',
            'department_risk.update' => 'แก้ไขความเสี่ยงระดับแผนก',
            'department_risk.delete' => 'ลบความเสี่ยงระดับแผนก',
            'department_risk.export' => 'ส่งออกข้อมูลความเสี่ยงระดับแผนก',
            'department_risk.view_own' => 'ดูความเสี่ยงระดับแผนกของตนเอง',
            'department_risk.manage_own' => 'จัดการความเสี่ยงระดับแผนกของตนเอง',
            
            // Risk Assessment Permissions
            'risk_assessment.view' => 'ดูการประเมินความเสี่ยง',
            'risk_assessment.create' => 'สร้างการประเมินความเสี่ยง',
            'risk_assessment.update' => 'แก้ไขการประเมินความเสี่ยง',
            'risk_assessment.delete' => 'ลบการประเมินความเสี่ยง',
            'risk_assessment.approve' => 'อนุมัติการประเมินความเสี่ยง',
            'risk_assessment.approve_high' => 'อนุมัติความเสี่ยงระดับสูง',
            'risk_assessment.export' => 'ส่งออกข้อมูลการประเมินความเสี่ยง',
            'risk_assessment.view_own' => 'ดูการประเมินความเสี่ยงของตนเอง',
            'risk_assessment.manage_own' => 'จัดการการประเมินความเสี่ยงของตนเอง',
            
            // Risk Control Permissions
            'risk_control.view' => 'ดูการควบคุมความเสี่ยง',
            'risk_control.create' => 'สร้างการควบคุมความเสี่ยง',
            'risk_control.update' => 'แก้ไขการควบคุมความเสี่ยง',
            'risk_control.delete' => 'ลบการควบคุมความเสี่ยง',
            'risk_control.activate' => 'เปิดใช้งานการควบคุมความเสี่ยง',
            'risk_control.deactivate' => 'ปิดใช้งานการควบคุมความเสี่ยง',
            'risk_control.export' => 'ส่งออกข้อมูลการควบคุมความเสี่ยง',
            'risk_control.view_own' => 'ดูการควบคุมความเสี่ยงของตนเอง',
            'risk_control.manage_own' => 'จัดการการควบคุมความเสี่ยงของตนเอง',
            
            // Criteria Management Permissions
            'criteria.view' => 'ดูเกณฑ์การประเมิน',
            'criteria.create' => 'สร้างเกณฑ์การประเมิน',
            'criteria.update' => 'แก้ไขเกณฑ์การประเมิน',
            'criteria.delete' => 'ลบเกณฑ์การประเมิน',
            
            // Report Permissions
            'report.view' => 'ดูรายงาน',
            'report.create' => 'สร้างรายงาน',
            'report.export' => 'ส่งออกรายงาน',
            'report.schedule' => 'จัดกำหนดการรายงาน',
            
            // User Management Permissions
            'user.view' => 'ดูผู้ใช้งาน',
            'user.create' => 'สร้างผู้ใช้งาน',
            'user.update' => 'แก้ไขผู้ใช้งาน',
            'user.delete' => 'ลบผู้ใช้งาน',
            'user.manage_permissions' => 'จัดการสิทธิ์ผู้ใช้งาน',
            'user.manage_roles' => 'จัดการบทบาทผู้ใช้งาน',
            
            // Settings Permissions
            'settings.view' => 'ดูการตั้งค่า',
            'settings.update' => 'แก้ไขการตั้งค่า',
            'settings.system' => 'ตั้งค่าระบบ',
            'settings.backup' => 'สำรองข้อมูล',
            'settings.restore' => 'คืนค่าข้อมูล',
            
            // Attachment Permissions
            'attachment.view' => 'ดูเอกสารแนบ',
            'attachment.upload' => 'อัปโหลดเอกสารแนบ',
            'attachment.download' => 'ดาวน์โหลดเอกสารแนบ',
            'attachment.delete' => 'ลบเอกสารแนบ',
        ];
        
        foreach ($permissions as $permission => $description) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
            Log::info("สร้าง Permission: {$permission}");
        }
    }
    
    /**
     * สร้าง Roles และกำหนด Permissions
     */
    private function createRoleWithPermissions()
    {
        Log::info('กำลังสร้าง Roles และกำหนด Permissions');

        // Super Admin - มีสิทธิ์ทั้งหมด
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());
        Log::info('สร้าง Role: Super Admin พร้อมสิทธิ์ทั้งหมด');
        
        // Admin - ผู้ดูแลระบบ
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPermissions = [
            'dashboard.view', 'dashboard.export', 'dashboard.view_all',
            'organizational_risk.view', 'organizational_risk.create', 'organizational_risk.update', 'organizational_risk.delete', 'organizational_risk.export', 'organizational_risk.import',
            'division_risk.view', 'division_risk.create', 'division_risk.update', 'division_risk.delete', 'division_risk.export', 'division_risk.approve',
            'department_risk.view', 'department_risk.create', 'department_risk.update', 'department_risk.delete', 'department_risk.export',
            'risk_assessment.view', 'risk_assessment.create', 'risk_assessment.update', 'risk_assessment.delete', 'risk_assessment.approve', 'risk_assessment.export',
            'risk_control.view', 'risk_control.create', 'risk_control.update', 'risk_control.delete', 'risk_control.export',
            'criteria.view', 'criteria.create', 'criteria.update', 'criteria.delete',
            'report.view', 'report.create', 'report.export', 'report.schedule',
            'user.view', 'user.update',
            'settings.view', 'settings.update',
            'attachment.view', 'attachment.upload', 'attachment.download', 'attachment.delete',
        ];
        $admin->givePermissionTo($adminPermissions);
        Log::info('สร้าง Role: Admin พร้อมสิทธิ์ที่กำหนด');
        
        // Director (ผู้อำนวยการฝ่าย)
        $director = Role::firstOrCreate(['name' => 'director', 'guard_name' => 'web']);
        $directorPermissions = [
            'dashboard.view', 'dashboard.export', 'dashboard.view_all',
            'organizational_risk.view', 'organizational_risk.export',
            'division_risk.view', 'division_risk.export', 'division_risk.approve',
            'department_risk.view', 'department_risk.export',
            'risk_assessment.view', 'risk_assessment.approve_high', 'risk_assessment.export',
            'risk_control.view', 'risk_control.export',
            'criteria.view',
            'report.view', 'report.create', 'report.export', 'report.schedule',
            'user.view',
            'settings.view',
            'attachment.view', 'attachment.download',
        ];
        $director->givePermissionTo($directorPermissions);
        Log::info('สร้าง Role: Director พร้อมสิทธิ์ที่กำหนด');
        
        // Chief (หัวหน้ากอง)
        $chief = Role::firstOrCreate(['name' => 'chief', 'guard_name' => 'web']);
        $chiefPermissions = [
            'dashboard.view', 'dashboard.view_division',
            'organizational_risk.view',
            'division_risk.view', 'division_risk.view_own', 'division_risk.manage_own',
            'department_risk.view', 'department_risk.export',
            'risk_assessment.view', 'risk_assessment.view_own', 'risk_assessment.manage_own', 'risk_assessment.approve',
            'risk_control.view', 'risk_control.view_own', 'risk_control.manage_own',
            'criteria.view',
            'report.view', 'report.export',
            'settings.view',
            'attachment.view', 'attachment.upload', 'attachment.download',
        ];
        $chief->givePermissionTo($chiefPermissions);
        Log::info('สร้าง Role: Chief พร้อมสิทธิ์ที่กำหนด');
        
        // Head (หัวหน้าแผนก)
        $head = Role::firstOrCreate(['name' => 'head', 'guard_name' => 'web']);
        $headPermissions = [
            'dashboard.view', 'dashboard.view_department',
            'organizational_risk.view',
            'division_risk.view', 'division_risk.view_own',
            'department_risk.view', 'department_risk.view_own', 'department_risk.manage_own',
            'risk_assessment.view', 'risk_assessment.view_own', 'risk_assessment.manage_own',
            'risk_control.view', 'risk_control.update', 'risk_control.view_own',
            'criteria.view',
            'report.view',
            'settings.view',
            'attachment.view', 'attachment.upload', 'attachment.download',
        ];
        $head->givePermissionTo($headPermissions);
        Log::info('สร้าง Role: Head พร้อมสิทธิ์ที่กำหนด');
        
        // User (ผู้ใช้งานทั่วไป)
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userPermissions = [
            'dashboard.view',
            'organizational_risk.view',
            'department_risk.view_own',
            'risk_assessment.view_own',
            'risk_control.view_own',
            'criteria.view',
            'report.view',
            'settings.view',
            'attachment.view', 'attachment.download',
        ];
        $user->givePermissionTo($userPermissions);
        Log::info('สร้าง Role: User พร้อมสิทธิ์ที่กำหนด');
        
        // Guest (ผู้เยี่ยมชม)
        $guest = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);
        $guestPermissions = [
            'dashboard.view',
            'organizational_risk.view',
            'attachment.view',
        ];
        $guest->givePermissionTo($guestPermissions);
        Log::info('สร้าง Role: Guest พร้อมสิทธิ์ที่กำหนด');
    }
}
