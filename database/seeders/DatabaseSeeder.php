<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationalRiskSeeder::class,
            DepartmentRiskSeeder::class,
            LikelihoodCriterionSeeder::class,
            ImpactCriterionSeeder::class,
            RiskAssessmentSeeder::class,
        ]);
    }
}
