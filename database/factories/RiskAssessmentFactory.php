<?php

namespace Database\Factories;

use App\Models\DivisionRisk;
use App\Models\RiskAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskAssessmentFactory extends Factory
{
    protected $model = RiskAssessment::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2023, 2025);
        $period = fake()->numberBetween(1, 2);
        return [
            'assessment_year' => $year,
            'assessment_period' => $period,
            'likelihood_level' => fake()->numberBetween(1, 4),
            'impact_level' => fake()->numberBetween(1, 4),
            'notes' => fake()->paragraph(1),
            'division_risk_id' => DivisionRisk::factory(),
        ];
    }
}
