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
            // สร้างผลการประเมินย้อนหลังทุก 3 เดือน สำหรับปี 2024
            $assessmentDates = [
                '2024-01-15',
                '2024-04-15',
                '2024-07-15',
                '2024-10-15',
            ];

            foreach ($assessmentDates as $date) {
                $quarter = floor((int)date('m', strtotime($date)) / 3) + 1;
                
                RiskAssessment::create([
                    'assessment_date' => $date,
                    'likelihood_level' => rand(1, 4),
                    'impact_level' => rand(1, 4),
                    'notes' => "ผลการประเมินประจำไตรมาส {$quarter} ปี " . date('Y', strtotime($date)),
                    'division_risk_id' => $divisionRisk->id,
                ]);
            }
        }
    }
}
