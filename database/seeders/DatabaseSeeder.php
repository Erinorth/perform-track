<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * เรียกใช้งาน seeders ทั้งหมด
     */
    public function run(): void
    {
        // เรียก Role และ Permission Seeder ก่อน
        $this->call(RolePermissionSeeder::class);
        
        // เรียก seeders อื่นๆ ตามลำดับ
        $this->call([
            LikelihoodCriterionSeeder::class,
            ImpactCriterionSeeder::class,
            OrganizationalRiskSeeder::class,
            DivisionRiskSeeder::class,
            RiskAssessmentSeeder::class,
            RiskControlSeeder::class,
        ]);
    }
}
