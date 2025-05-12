<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationalRiskSeeder::class,
            DivisionRiskSeeder::class,
            LikelihoodCriterionSeeder::class,
            ImpactCriterionSeeder::class,
            RiskAssessmentSeeder::class,
        ]);
    }
}
