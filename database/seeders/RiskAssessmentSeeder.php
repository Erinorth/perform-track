<?php

namespace Database\Seeders;

use App\Models\DivisionRisk;
use App\Models\RiskAssessment;
use Illuminate\Database\Seeder;

class RiskAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $divisionRisks = DivisionRisk::all();
        
        if ($divisionRisks->isEmpty()) {
            $this->call(DivisionRiskSeeder::class);
            $divisionRisks = DivisionRisk::all();
        }

        foreach ($divisionRisks as $divisionRisk) {
            // สร้างผลการประเมินย้อนหลัง 2 งวด สำหรับปี 2024
            $periods = [
                ['year' => 2024, 'period' => 1],
                ['year' => 2024, 'period' => 2],
            ];

            foreach ($periods as $p) {
                RiskAssessment::create([
                    'assessment_year' => $p['year'],
                    'assessment_period' => $p['period'],
                    'likelihood_level' => rand(1, 4),
                    'impact_level' => rand(1, 4),
                    'notes' => "ผลการประเมินครึ่งปีที่ {$p['period']} ปี {$p['year']}",
                    'division_risk_id' => $divisionRisk->id,
                ]);
            }
        }
    }
}
