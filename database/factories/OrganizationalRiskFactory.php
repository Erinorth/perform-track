<?php

namespace Database\Factories;

use App\Models\OrganizationalRisk;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationalRiskFactory extends Factory
{
    protected $model = OrganizationalRisk::class;

    public function definition(): array
    {
        return [
            'risk_name' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
        ];
    }
}
