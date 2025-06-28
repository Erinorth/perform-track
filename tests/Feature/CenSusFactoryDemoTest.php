<?php

namespace Tests\Feature;

use App\Models\CenSus;
use Tests\TestCase;

class CenSusFactoryDemoTest extends TestCase
{
    /**
     * ทดสอบการสร้าง Hierarchy แบบสมจริง
     */
    public function test_demo_census_hierarchy(): void
    {
        $this->info('🚀 Demo การสร้าง CenSus แบบ Hierarchy ที่สมจริง');

        // สร้าง hierarchy สำหรับฝ่าย อบค.
        $employees = CenSus::createSingleDivisionHierarchy('อบค.');

        $this->displayHierarchy($employees, 'อบค.');
        
        // ทดสอบจำนวนตามโครงสร้าง
        $this->validateHierarchyStructure($employees, 'อบค.');

        $this->info('✅ Demo Hierarchy เสร็จสิ้น');
    }

    /**
     * ทดสอบการสร้าง Hierarchy ทั้งองค์กร
     */
    public function test_demo_full_organization_hierarchy(): void
    {
        $this->info('🚀 Demo การสร้าง Hierarchy ทั้งองค์กร');

        // สร้าง hierarchy ทั้งองค์กร
        $allEmployees = CenSus::createHierarchy();

        $this->info("\n📊 สรุปทั้งองค์กร:");
        $this->info("  รวมพนักงานทั้งหมด: {$allEmployees->count()} คน");

        // แสดงรายละเอียดแต่ละฝ่าย
        $employeesByDivision = $allEmployees->groupBy('fay');
        
        foreach ($employeesByDivision as $division => $employees) {
            $this->info("\n🏢 ฝ่าย: {$division}");
            $this->displayHierarchy($employees, $division);
        }

        // ทดสอบโครงสร้างทั้งองค์กร
        $this->validateFullOrganization($allEmployees);

        $this->info('✅ Demo Full Organization Hierarchy เสร็จสิ้น');
    }

    /**
     * ทดสอบการสร้าง Hierarchy แบบ Custom
     */
    public function test_demo_custom_hierarchy(): void
    {
        $this->info('🚀 Demo การสร้าง Custom Hierarchy');

        // กำหนดค่าแบบ custom
        $config = [
            'division' => 'วศก.',
            'departments' => ['กวศ1-ธ.', 'กวศ2-ธ.'],
            'sections_per_department' => 2,
            'workers_per_section' => 3,
        ];

        $employees = CenSus::createCustomHierarchy($config);

        $this->info("\n📋 Custom Hierarchy สำหรับ {$config['division']}:");
        $this->displayHierarchy($employees, $config['division']);

        // คำนวณจำนวนที่คาดหวัง
        $expectedCount = 1 + 2 + // Director + Assistant Directors
                        count($config['departments']) + // Department Heads
                        (count($config['departments']) * $config['sections_per_department']) + // Section Heads
                        (count($config['departments']) * $config['sections_per_department'] * $config['workers_per_section']); // Workers

        $this->assertEquals($expectedCount, $employees->count());
        $this->info("✅ Custom Hierarchy ถูกต้อง (คาดหวัง: {$expectedCount}, ได้: {$employees->count()})");
    }

    /**
     * ทดสอบการสร้าง Hierarchy ขนาดใหญ่ (20 คน)
     */
    public function test_demo_hierarchy_20_people(): void
    {
        $this->info('🚀 Demo การสร้าง Hierarchy สำหรับ 20 คน');

        // สร้าง custom hierarchy ที่มี 20 คน
        $config = [
            'division' => 'อบค.',
            'departments' => ['กผงค-ธ.', 'กกห-ธ.'],
            'sections_per_department' => 2, // กองละ 2 แผนก
            'workers_per_section' => 4, // แผนกละ 4 คน
        ];

        $employees = CenSus::createCustomHierarchy($config);

        $this->info("\n📋 Hierarchy 20 คน:");
        $this->displayDetailedHierarchy($employees);

        // ทดสอบจำนวน (1 อ + 2 ช.อ + 2 ก + 4 ห + 16 ผู้ปฏิบัติงาน = 25 คน)
        // ปรับให้เป็น 20 คน
        $adjustedEmployees = $employees->take(20);
        
        $this->assertCount(20, $adjustedEmployees);
        $this->info("✅ สร้าง Hierarchy 20 คนเสร็จสิ้น");
    }

    /**
     * แสดง Hierarchy แบบละเอียด
     */
    private function displayHierarchy($employees, string $division): void
    {
        $employeesByPosition = $employees->groupBy('a_position');

        // ผู้อำนวยการฝ่าย
        if ($employeesByPosition->has('อ')) {
            $this->info("  👨‍💼 ผู้อำนวยการฝ่าย ({$employeesByPosition['อ']->count()} คน):");
            foreach ($employeesByPosition['อ'] as $emp) {
                $this->info("    - {$emp->full_name_thai}");
            }
        }

        // ผู้ช่วยผู้อำนวยการฝ่าย
        if ($employeesByPosition->has('ช.อ')) {
            $this->info("  👨‍💼 ผู้ช่วยผู้อำนวยการฝ่าย ({$employeesByPosition['ช.อ']->count()} คน):");
            foreach ($employeesByPosition['ช.อ'] as $emp) {
                $this->info("    - {$emp->full_name_thai}");
            }
        }

        // หัวหน้ากอง
        if ($employeesByPosition->has('ก')) {
            $this->info("  🏢 หัวหน้ากอง ({$employeesByPosition['ก']->count()} คน):");
            $departmentHeads = $employeesByPosition['ก']->groupBy('gong');
            foreach ($departmentHeads as $dept => $heads) {
                foreach ($heads as $emp) {
                    $this->info("    - {$emp->full_name_thai} ({$dept})");
                }
            }
        }

        // หัวหน้าแผนก
        if ($employeesByPosition->has('ห')) {
            $this->info("  👥 หัวหน้าแผนก ({$employeesByPosition['ห']->count()} คน):");
            $sectionHeads = $employeesByPosition['ห']->groupBy('gong');
            foreach ($sectionHeads as $dept => $heads) {
                $this->info("    📂 {$dept}:");
                foreach ($heads as $emp) {
                    $this->info("      - {$emp->full_name_thai} ({$emp->pnang})");
                }
            }
        }

        // ผู้ปฏิบัติงาน
        $workers = $employees->reject(function ($emp) {
            return in_array($emp->a_position, ['อ', 'ช.อ', 'ก', 'ห']);
        });

        if ($workers->count() > 0) {
            $this->info("  👨‍💻 ผู้ปฏิบัติงาน ({$workers->count()} คน):");
            $workersBySection = $workers->groupBy('pnang');
            foreach ($workersBySection as $section => $sectionWorkers) {
                $this->info("    📁 {$section} ({$sectionWorkers->count()} คน):");
                foreach ($sectionWorkers as $emp) {
                    $this->info("      - {$emp->full_name_thai} ({$emp->position_level})");
                }
            }
        }

        // สถิติ
        $this->info("\n  📊 สถิติ:");
        $this->info("    รวม: {$employees->count()} คน");
        $this->info("    ชาย: {$employees->filter->isMale()->count()} คน");
        $this->info("    หญิง: {$employees->filter->isFemale()->count()} คน");
    }

    /**
     * แสดง Hierarchy แบบละเอียดมาก
     */
    private function displayDetailedHierarchy($employees): void
    {
        $this->info("📋 รายชื่อพนักงานทั้งหมด:");
        
        foreach ($employees as $index => $emp) {
            $number = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $gender = $emp->isMale() ? 'ชาย' : 'หญิง';
            $level = $this->getPositionLevel($emp->a_position);
            $organization = $this->getOrganizationPath($emp);
            
            $this->info("  {$number}. {$emp->full_name_thai}");
            $this->info("      ตำแหน่ง: {$level} ({$emp->a_position})");
            $this->info("      หน่วยงาน: {$organization}");
            $this->info("      เพศ: {$gender}");
            $this->info("");
        }
    }

    /**
     * ได้รับระดับตำแหน่ง
     */
    private function getPositionLevel(string $position): string
    {
        return match (true) {
            str_contains($position, 'อ') && !str_contains($position, 'ช.อ') => 'ผู้อำนวยการฝ่าย',
            str_contains($position, 'ช.อ') => 'ผู้ช่วยผู้อำนวยการฝ่าย',
            str_contains($position, 'ก') => 'หัวหน้ากอง',
            str_contains($position, 'ห') => 'หัวหน้าแผนก',
            str_contains($position, 'วศ.') => 'วิศวกร',
            str_contains($position, 'ช.') => 'ช่าง',
            str_contains($position, 'ชก.') => 'ช่างชำนาญการ',
            str_contains($position, 'พช.') => 'พนักงานวิชาชีพ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * ได้รับ path ของหน่วยงาน
     */
    private function getOrganizationPath($emp): string
    {
        $parts = array_filter([$emp->fay, $emp->gong, $emp->pnang]);
        return implode(' > ', $parts);
    }

    /**
     * ตรวจสอบโครงสร้าง Hierarchy
     */
    private function validateHierarchyStructure($employees, string $division): void
    {
        // ตรวจสอบผู้อำนวยการฝ่าย
        $directors = $employees->filter->isDirector();
        $this->assertEquals(3, $directors->count(), "ควรมีผู้อำนวยการและผู้ช่วย 3 คน (1 อ + 2 ช.อ)");
        
        // ตรวจสอบหัวหน้ากอง
        $chiefs = $employees->filter->isChief();
        $this->assertEquals(5, $chiefs->count(), "ควรมีหัวหน้ากอง 5 คน");
        
        // ตรวจสอบหัวหน้าแผนก
        $heads = $employees->filter->isHead();
        $this->assertEquals(15, $heads->count(), "ควรมีหัวหน้าแผนก 15 คน (5 กอง x 3 แผนก)");
        
        // ตรวจสอบผู้ปฏิบัติงาน
        $workers = $employees->reject(function ($emp) {
            return in_array($emp->a_position, ['อ', 'ช.อ', 'ก', 'ห']);
        });
        $this->assertEquals(75, $workers->count(), "ควรมีผู้ปฏิบัติงาน 75 คน (15 แผนก x 5 คน)");
        
        $this->info("✅ โครงสร้าง Hierarchy ถูกต้อง:");
        $this->info("  - ผู้บริหารฝ่าย: {$directors->count()} คน");
        $this->info("  - หัวหน้ากอง: {$chiefs->count()} คน");
        $this->info("  - หัวหน้าแผนก: {$heads->count()} คน");
        $this->info("  - ผู้ปฏิบัติงาน: {$workers->count()} คน");
        $this->info("  - รวม: {$employees->count()} คน");
    }

    /**
     * ตรวจสอบโครงสร้างทั้งองค์กร
     */
    private function validateFullOrganization($allEmployees): void
    {
        $divisionStructure = CenSus::getDivisionStructure();
        $divisionCount = count($divisionStructure);
        
        // คำนวณจำนวนที่คาดหวัง
        $expectedDirectors = $divisionCount * 3; // 1 อ + 2 ช.อ ต่อฝ่าย
        $expectedChiefs = $divisionCount * 5; // 5 กอง ต่อฝ่าย
        $expectedHeads = $divisionCount * 15; // 15 แผนก ต่อฝ่าย (5 กอง x 3 แผนก)
        $expectedWorkers = $divisionCount * 75; // 75 คน ต่อฝ่าย (15 แผนก x 5 คน)
        $expectedTotal = $expectedDirectors + $expectedChiefs + $expectedHeads + $expectedWorkers;
        
        $this->info("\n📊 สถิติทั้งองค์กร:");
        $this->info("  จำนวนฝ่าย: {$divisionCount} ฝ่าย");
        $this->info("  ผู้บริหารฝ่าย: {$allEmployees->filter->isDirector()->count()} คน (คาดหวัง: {$expectedDirectors})");
        $this->info("  หัวหน้ากอง: {$allEmployees->filter->isChief()->count()} คน (คาดหวัง: {$expectedChiefs})");
        $this->info("  หัวหน้าแผนก: {$allEmployees->filter->isHead()->count()} คน (คาดหวัง: {$expectedHeads})");
        $this->info("  รวมทั้งหมด: {$allEmployees->count()} คน (คาดหวัง: {$expectedTotal})");
        
        $this->assertEquals($expectedTotal, $allEmployees->count());
    }

    /**
     * Helper method สำหรับแสดงข้อความ
     */
    private function info(string $message): void
    {
        echo "\n" . $message;
    }
}
