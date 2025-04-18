<?php

namespace Database\Seeders;

use App\Models\DepartmentRisk;
use App\Models\LikelihoodCriterion;
use Illuminate\Database\Seeder;

class LikelihoodCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $departmentRisks = DepartmentRisk::all();
        
        if ($departmentRisks->isEmpty()) {
            $this->call(DepartmentRiskSeeder::class);
            $departmentRisks = DepartmentRisk::all();
        }

        foreach ($departmentRisks as $departmentRisk) {
            // สร้างเกณฑ์มาตรฐานของโอกาสที่จะเกิด (1-4) สำหรับแต่ละความเสี่ยงระดับฝ่าย
            $criteria = [
                [
                    'level' => 1,
                    'name' => 'น้อยมาก',
                    'description' => 'โอกาสเกิดน้อยมาก หรือไม่น่าจะเกิดขึ้น (น้อยกว่า 10%)',
                    'department_risk_id' => $departmentRisk->id,
                ],
                [
                    'level' => 2,
                    'name' => 'น้อย',
                    'description' => 'อาจเกิดขึ้นได้บ้าง แต่น้อย (10-30%)',
                    'department_risk_id' => $departmentRisk->id,
                ],
                [
                    'level' => 3,
                    'name' => 'ปานกลาง',
                    'description' => 'มีโอกาสเกิดขึ้นพอสมควร (31-70%)',
                    'department_risk_id' => $departmentRisk->id,
                ],
                [
                    'level' => 4,
                    'name' => 'สูง',
                    'description' => 'มีโอกาสเกิดขึ้นสูงมาก (มากกว่า 70%)',
                    'department_risk_id' => $departmentRisk->id,
                ],
            ];

            foreach ($criteria as $criterion) {
                LikelihoodCriterion::create($criterion);
            }
        }
    }
}
