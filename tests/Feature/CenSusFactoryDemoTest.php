<?php

namespace Tests\Feature;

use App\Models\CenSus;
use Tests\TestCase;

/**
 * Test สำหรับทดสอบ CenSus Factory และแสดงผลเป็นตาราง
 */
class CenSusFactoryDemoTest extends TestCase
{
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

        // ตรวจสอบความถูกต้องของตำแหน่งก่อนแสดงผล
        $this->validatePositionIntegrity($allEmployees);

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
     * ตรวจสอบความถูกต้องของตำแหน่งในโครงสร้าง
     */
    private function validatePositionIntegrity($allEmployees): void
    {
        $this->info("\n🔍 ตรวจสอบความถูกต้องของตำแหน่ง:");
        
        // ตรวจสอบผู้ปฏิบัติงานที่มีตำแหน่งหัวหน้า
        $workersWithLeadershipPositions = $allEmployees->filter(function ($emp) {
            // คนที่มีกอง และ แผนก (ผู้ปฏิบัติงาน) แต่มีตำแหน่งหัวหน้า
            return !empty($emp->gong) && !empty($emp->pnang) && 
                   in_array($emp->a_position, ['อ', 'ช.อ', 'ก', 'ห']);
        });
        
        if ($workersWithLeadershipPositions->count() > 0) {
            $this->info("  ❌ พบผู้ปฏิบัติงานที่มีตำแหน่งหัวหน้า ({$workersWithLeadershipPositions->count()} คน):");
            foreach ($workersWithLeadershipPositions as $emp) {
                $this->info("     - [{$emp->EMPN}] {$emp->full_name_thai} ตำแหน่ง: {$emp->a_position}");
                $this->info("       หน่วยงาน: {$emp->fay} > {$emp->gong} > {$emp->pnang}");
            }
        } else {
            $this->info("  ✅ ไม่พบผู้ปฏิบัติงานที่มีตำแหน่งหัวหน้า");
        }
        
        // ตรวจสอบหัวหน้าที่อยู่ในแผนก (ควรจะเป็นหัวหน้าแผนกเท่านั้น)
        $leadershipInSections = $allEmployees->filter(function ($emp) {
            return !empty($emp->pnang) && in_array($emp->a_position, ['อ', 'ช.อ', 'ก']);
        });
        
        if ($leadershipInSections->count() > 0) {
            $this->info("  ❌ พบตำแหน่งผู้บริหาร/หัวหน้ากองในแผนก ({$leadershipInSections->count()} คน):");
            foreach ($leadershipInSections as $emp) {
                $this->info("     - [{$emp->EMPN}] {$emp->full_name_thai} ตำแหน่ง: {$emp->a_position}");
                $this->info("       หน่วยงาน: {$emp->fay} > {$emp->gong} > {$emp->pnang}");
            }
        } else {
            $this->info("  ✅ ไม่พบตำแหน่งผู้บริหาร/หัวหน้ากองในแผนก");
        }
        
        // ตรวจสอบการกระจายตำแหน่งของผู้ปฏิบัติงาน
        $actualWorkers = $allEmployees->filter(function ($emp) {
            return !empty($emp->gong) && !empty($emp->pnang) && 
                   !in_array($emp->a_position, ['อ', 'ช.อ', 'ก', 'ห']);
        });
        
        $workerPositions = $actualWorkers->pluck('a_position')->unique()->sort()->values();
        $allowedPositions = CenSus::getWorkerPositions();
        
        $this->info("\n  📋 ตำแหน่งผู้ปฏิบัติงานที่พบ:");
        $this->info("     อนุญาต: " . implode(', ', $allowedPositions));
        $this->info("     พบจริง: " . $workerPositions->implode(', '));
        
        $invalidPositions = $workerPositions->diff($allowedPositions);
        if ($invalidPositions->count() > 0) {
            $this->info("  ❌ พบตำแหน่งที่ไม่อนุญาต: " . $invalidPositions->implode(', '));
        } else {
            $this->info("  ✅ ตำแหน่งผู้ปฏิบัติงานถูกต้องทั้งหมด");
        }
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
                $orgStructure = $this->formatOrganizationStructure($emp->fay, $emp->gong, $emp->pnang);
                $this->info("    - [{$emp->EMPN}] {$emp->full_name_thai}");
                $this->info("      🏛️  {$orgStructure}");
            }
        }

        // ผู้ช่วยผู้อำนวยการฝ่าย
        if ($employeesByPosition->has('ช.อ')) {
            $this->info("  👨‍💼 ผู้ช่วยผู้อำนวยการฝ่าย ({$employeesByPosition['ช.อ']->count()} คน):");
            foreach ($employeesByPosition['ช.อ'] as $emp) {
                $orgStructure = $this->formatOrganizationStructure($emp->fay, $emp->gong, $emp->pnang);
                $this->info("    - [{$emp->EMPN}] {$emp->full_name_thai}");
                $this->info("      🏛️  {$orgStructure}");
            }
        }

        // หัวหน้ากอง
        if ($employeesByPosition->has('ก')) {
            $this->info("  🏢 หัวหน้ากอง ({$employeesByPosition['ก']->count()} คน):");
            $departmentHeads = $employeesByPosition['ก']->groupBy('gong');
            foreach ($departmentHeads as $dept => $heads) {
                foreach ($heads as $emp) {
                    $orgStructure = $this->formatOrganizationStructure($emp->fay, $emp->gong, $emp->pnang);
                    $this->info("    - [{$emp->EMPN}] {$emp->full_name_thai}");
                    $this->info("      🏛️  {$orgStructure}");
                    
                    // ตรวจสอบว่าหัวหน้ากองไม่มีแผนก
                    if (!empty($emp->pnang)) {
                        $this->info("      ⚠️  หัวหน้ากองไม่ควรมีแผนก: {$emp->pnang}");
                    }
                }
            }
        }

        // หัวหน้าแผนก
        if ($employeesByPosition->has('ห')) {
            $this->info("  👥 หัวหน้าแผนก ({$employeesByPosition['ห']->count()} คน):");
            $sectionHeads = $employeesByPosition['ห']->groupBy('gong');
            foreach ($sectionHeads as $dept => $heads) {
                $this->info("    📂 กอง: {$dept}");
                foreach ($heads as $emp) {
                    $orgStructure = $this->formatOrganizationStructure($emp->fay, $emp->gong, $emp->pnang);
                    $this->info("      - [{$emp->EMPN}] {$emp->full_name_thai}");
                    $this->info("        🏛️  {$orgStructure}");
                }
            }
        }

        // ผู้ปฏิบัติงาน (กรองให้เหลือเฉพาะคนที่มีแผนกและไม่ใช่ตำแหน่งหัวหน้า)
        $workers = $employees->filter(function ($emp) {
            return !empty($emp->pnang) && 
                   !in_array($emp->a_position, ['อ', 'ช.อ', 'ก', 'ห']);
        });

        if ($workers->count() > 0) {
            $this->info("  👨‍💻 ผู้ปฏิบัติงาน ({$workers->count()} คน):");
            $workersBySection = $workers->groupBy('pnang');
            foreach ($workersBySection as $section => $sectionWorkers) {
                $this->info("    📁 แผนก: {$section} ({$sectionWorkers->count()} คน):");
                foreach ($sectionWorkers as $emp) {
                    $orgStructure = $this->formatOrganizationStructure($emp->fay, $emp->gong, $emp->pnang);
                    $this->info("      - [{$emp->EMPN}] {$emp->full_name_thai} ({$emp->position_level})");
                    $this->info("        🏛️  {$orgStructure}");
                    
                    // ตรวจสอบตำแหน่งผู้ปฏิบัติงาน
                    if (in_array($emp->a_position, ['อ', 'ช.อ', 'ก', 'ห'])) {
                        $this->info("        ❌ ตำแหน่งไม่ถูกต้อง: {$emp->a_position}");
                    }
                }
            }
        }

        // สถิติ
        $this->info("\n  📊 สถิติ:");
        $this->info("    รวม: {$employees->count()} คน");
        $this->info("    ชาย: {$employees->filter->isMale()->count()} คน");
        $this->info("    หญิง: {$employees->filter->isFemale()->count()} คน");
        
        // แสดงตัวอย่างหมายเลขประจำตัว
        $empNumbers = $employees->pluck('EMPN')->sort()->values();
        $this->info("    หมายเลขประจำตัว: {$empNumbers->first()} - {$empNumbers->last()}");
        
        // สถิติโครงสร้าง
        $this->displayOrganizationStatistics($employees);
    }

    /**
     * จัดรูปแบบโครงสร้างองค์กร
     */
    private function formatOrganizationStructure(string $fay, string $gong, string $pnang): string
    {
        $structure = "ฝ่าย: {$fay}";
        
        if (!empty($gong)) {
            $structure .= " → กอง: {$gong}";
        } else {
            $structure .= " → กอง: -";
        }
        
        if (!empty($pnang)) {
            $structure .= " → แผนก: {$pnang}";
        } else {
            $structure .= " → แผนก: -";
        }
        
        return $structure;
    }

    /**
     * แสดงสถิติโครงสร้างองค์กร
     */
    private function displayOrganizationStatistics($employees): void
    {
        $this->info("\n  🏗️  สถิติโครงสร้าง:");
        
        // จำนวนกองในฝ่าย
        $departments = $employees->pluck('gong')->filter()->unique();
        $this->info("    จำนวนกอง: {$departments->count()} กอง");
        if ($departments->count() > 0) {
            $this->info("    รายชื่อกอง: " . $departments->implode(', '));
        }
        
        // จำนวนแผนกในฝ่าย
        $sections = $employees->pluck('pnang')->filter()->unique();
        $this->info("    จำนวนแผนก: {$sections->count()} แผนก");
        if ($sections->count() > 0) {
            $this->info("    รายชื่อแผนก: " . $sections->implode(', '));
        }
        
        // สถิติตามโครงสร้าง
        $this->info("\n  📈 การกระจายตามโครงสร้าง:");
        
        // พนักงานแต่ละกอง
        $empByDept = $employees->filter(function($emp) {
            return !empty($emp->gong);
        })->groupBy('gong');
        
        foreach ($empByDept as $dept => $deptEmployees) {
            $this->info("    {$dept}: {$deptEmployees->count()} คน");
        }
        
        // พนักงานระดับฝ่าย (ไม่มีกอง)
        $divisionLevel = $employees->filter(function($emp) {
            return empty($emp->gong);
        });
        
        if ($divisionLevel->count() > 0) {
            $this->info("    ระดับฝ่าย (ไม่มีกอง): {$divisionLevel->count()} คน");
        }
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
        $this->info("  ผู้ปฏิบัติงาน: {$allEmployees->filter->isWorker()->count()} คน (คาดหวัง: {$expectedWorkers})");
        $this->info("  รวมทั้งหมด: {$allEmployees->count()} คน (คาดหวัง: {$expectedTotal})");
        
        // แสดงสถิติหมายเลขประจำตัว
        $empNumbers = $allEmployees->pluck('EMPN')->sort();
        $uniqueNumbers = $empNumbers->unique();
        $this->info("\n🔢 สถิติหมายเลขประจำตัว:");
        $this->info("  จำนวนหมายเลขทั้งหมด: {$empNumbers->count()}");
        $this->info("  จำนวนหมายเลขที่ไม่ซ้ำ: {$uniqueNumbers->count()}");
        $this->info("  ช่วงหมายเลข: {$empNumbers->first()} - {$empNumbers->last()}");
        
        if ($empNumbers->count() !== $uniqueNumbers->count()) {
            $duplicates = $empNumbers->duplicates();
            $this->info("  ⚠️  พบหมายเลขซ้ำ: " . $duplicates->implode(', '));
        } else {
            $this->info("  ✅ ไม่มีหมายเลขซ้ำ");
        }
        
        // สถิติโครงสร้างทั้งองค์กร
        $this->info("\n🏗️  สถิติโครงสร้างทั้งองค์กร:");
        $allDivisions = $allEmployees->pluck('fay')->unique();
        $allDepartments = $allEmployees->pluck('gong')->filter()->unique();
        $allSections = $allEmployees->pluck('pnang')->filter()->unique();
        
        $this->info("  จำนวนฝ่ายทั้งหมด: {$allDivisions->count()} ฝ่าย");
        $this->info("  จำนวนกองทั้งหมด: {$allDepartments->count()} กอง");
        $this->info("  จำนวนแผนกทั้งหมด: {$allSections->count()} แผนก");
        
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
