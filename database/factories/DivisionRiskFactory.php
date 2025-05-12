<?php

namespace Database\Factories;

use App\Models\DivisionRisk;
use App\Models\OrganizationalRisk;
use Illuminate\Database\Eloquent\Factories\Factory;

class DivisionRiskFactory extends Factory
{
    protected $model = DivisionRisk::class;

    public function definition(): array
    {
        return [
            'risk_name' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'year' => fake()->numberBetween(2023, 2025),
            'organizational_risk_id' => OrganizationalRisk::factory(),
        ];
    }
}
