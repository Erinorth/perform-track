<?php

namespace Database\Seeders;

use App\Models\DivisionRisk;
use App\Models\LikelihoodCriterion;
use Illuminate\Database\Seeder;

class LikelihoodCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $divisionRisks = DivisionRisk::all();
        
        if ($divisionRisks->isEmpty()) {
            $this->call(DivisionRiskSeeder::class);
            $divisionRisks = DivisionRisk::all();
        }

        foreach ($divisionRisks as $divisionRisk) {
            // สร้างเกณฑ์มาตรฐานของโอกาสที่จะเกิด (1-4) สำหรับแต่ละความเสี่ยงระดับฝ่าย
            $criteria = [
                [
                    'level' => 1,
                    'name' => 'น้อยมาก',
                    'description' => 'โอกาสเกิดน้อยมาก หรือไม่น่าจะเกิดขึ้น (น้อยกว่า 10%)',
                    'division_risk_id' => $divisionRisk->id,
                ],
                [
                    'level' => 2,
                    'name' => 'น้อย',
                    'description' => 'อาจเกิดขึ้นได้บ้าง แต่น้อย (10-30%)',
                    'division_risk_id' => $divisionRisk->id,
                ],
                [
                    'level' => 3,
                    'name' => 'ปานกลาง',
                    'description' => 'มีโอกาสเกิดขึ้นพอสมควร (31-70%)',
                    'division_risk_id' => $divisionRisk->id,
                ],
                [
                    'level' => 4,
                    'name' => 'สูง',
                    'description' => 'มีโอกาสเกิดขึ้นสูงมาก (มากกว่า 70%)',
                    'division_risk_id' => $divisionRisk->id,
                ],
            ];

            foreach ($criteria as $criterion) {
                LikelihoodCriterion::create($criterion);
            }
        }
    }
}
