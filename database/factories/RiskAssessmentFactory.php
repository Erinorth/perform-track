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
        return [
            'assessment_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'likelihood_level' => fake()->numberBetween(1, 4),
            'impact_level' => fake()->numberBetween(1, 4),
            'notes' => fake()->paragraph(1),
            'division_risk_id' => DivisionRisk::factory(),
        ];
    }
}
