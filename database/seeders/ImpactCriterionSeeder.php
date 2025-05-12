<?php

namespace Database\Seeders;

use App\Models\DivisionRisk;
use App\Models\ImpactCriterion;
use Illuminate\Database\Seeder;

class ImpactCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $divisionRisks = DivisionRisk::all();
        
        if ($divisionRisks->isEmpty()) {
            $this->call(DivisionRiskSeeder::class);
            $divisionRisks = DivisionRisk::all();
        }

        foreach ($divisionRisks as $divisionRisk) {
            // สร้างเกณฑ์มาตรฐานของผลกระทบ (1-4) สำหรับแต่ละความเสี่ยงระดับฝ่าย
            $criteria = [
                [
                    'level' => 1,
                    'name' => 'น้อยมาก',
                    'description' => 'ผลกระทบเล็กน้อย ไม่มีนัยสำคัญต่อการดำเนินงาน',
                    'division_risk_id' => $divisionRisk->id,
                ],
                [
                    'level' => 2,
                    'name' => 'น้อย',
                    'description' => 'ผลกระทบเล็กน้อยที่สามารถจัดการได้ในระดับปฏิบัติการ',
                    'division_risk_id' => $divisionRisk->id,
                ],
                [
                    'level' => 3,
                    'name' => 'ปานกลาง',
                    'description' => 'ผลกระทบปานกลางที่ต้องมีการปรับแผนการดำเนินงาน',
                    'division_risk_id' => $divisionRisk->id,
                ],
                [
                    'level' => 4,
                    'name' => 'สูง',
                    'description' => 'ผลกระทบรุนแรงที่อาจส่งผลต่อการบรรลุเป้าหมายขององค์กร',
                    'division_risk_id' => $divisionRisk->id,
                ],
            ];

            foreach ($criteria as $criterion) {
                ImpactCriterion::create($criterion);
            }
        }
    }
}
