<?php

namespace Database\Seeders;

use App\Models\DepartmentRisk;
use App\Models\ImpactCriterion;
use Illuminate\Database\Seeder;

class ImpactCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $departmentRisks = DepartmentRisk::all();
        
        if ($departmentRisks->isEmpty()) {
            $this->call(DepartmentRiskSeeder::class);
            $departmentRisks = DepartmentRisk::all();
        }

        foreach ($departmentRisks as $departmentRisk) {
            // สร้างเกณฑ์มาตรฐานของผลกระทบ (1-4) สำหรับแต่ละความเสี่ยงระดับฝ่าย
            $criteria = [
                [
                    'level' => 1,
                    'name' => 'น้อยมาก',
                    'description' => 'ผลกระทบเล็กน้อย ไม่มีนัยสำคัญต่อการดำเนินงาน',
                    'department_risk_id' => $departmentRisk->id,
                ],
                [
                    'level' => 2,
                    'name' => 'น้อย',
                    'description' => 'ผลกระทบเล็กน้อยที่สามารถจัดการได้ในระดับปฏิบัติการ',
                    'department_risk_id' => $departmentRisk->id,
                ],
                [
                    'level' => 3,
                    'name' => 'ปานกลาง',
                    'description' => 'ผลกระทบปานกลางที่ต้องมีการปรับแผนการดำเนินงาน',
                    'department_risk_id' => $departmentRisk->id,
                ],
                [
                    'level' => 4,
                    'name' => 'สูง',
                    'description' => 'ผลกระทบรุนแรงที่อาจส่งผลต่อการบรรลุเป้าหมายขององค์กร',
                    'department_risk_id' => $departmentRisk->id,
                ],
            ];

            foreach ($criteria as $criterion) {
                ImpactCriterion::create($criterion);
            }
        }
    }
}
