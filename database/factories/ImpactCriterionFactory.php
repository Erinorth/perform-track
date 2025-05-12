<?php

namespace Database\Factories;

use App\Models\DivisionRisk;
use App\Models\ImpactCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImpactCriterionFactory extends Factory
{
    protected $model = ImpactCriterion::class;

    public function definition(): array
    {
        $levels = [
            1 => 'น้อยมาก',
            2 => 'น้อย',
            3 => 'ปานกลาง',
            4 => 'สูง'
        ];
        
        $level = fake()->numberBetween(1, 4);
        
        return [
            'level' => $level,
            'name' => $levels[$level],
            'description' => fake()->paragraph(1),
            'division_risk_id' => DivisionRisk::factory(),
        ];
    }
}
