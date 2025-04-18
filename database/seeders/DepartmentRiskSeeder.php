<?php

namespace Database\Seeders;

use App\Models\DepartmentRisk;
use App\Models\OrganizationalRisk;
use Illuminate\Database\Seeder;

class DepartmentRiskSeeder extends Seeder
{
    public function run(): void
    {
        // ดึงความเสี่ยงระดับองค์กรที่มีอยู่
        $orgRisks = OrganizationalRisk::all();
        
        if ($orgRisks->isEmpty()) {
            $this->call(OrganizationalRiskSeeder::class);
            $orgRisks = OrganizationalRisk::all();
        }

        // สร้างข้อมูลตัวอย่างที่มีความหมาย
        $departmentRisks = [
            // ความเสี่ยงด้านการเงิน
            [
                'risk_name' => 'การบริหารงบประมาณไม่เป็นไปตามแผน',
                'description' => 'ความเสี่ยงที่การใช้จ่ายงบประมาณของฝ่ายอาจเกินกว่าที่กำหนด หรือไม่เป็นไปตามแผนงานที่วางไว้',
                'year' => 2024,
                'organizational_risk_id' => $orgRisks->where('risk_name', 'ความเสี่ยงด้านการเงิน')->first()->id ?? $orgRisks->first()->id,
            ],
            [
                'risk_name' => 'การจัดซื้อจัดจ้างล่าช้า',
                'description' => 'ความเสี่ยงที่กระบวนการจัดซื้อจัดจ้างอาจล่าช้า ส่งผลให้การดำเนินงานตามแผนงานหรือโครงการไม่เป็นไปตามกำหนด',
                'year' => 2024,
                'organizational_risk_id' => $orgRisks->where('risk_name', 'ความเสี่ยงด้านการเงิน')->first()->id ?? $orgRisks->first()->id,
            ],
            
            // ความเสี่ยงด้านเทคโนโลยี
            [
                'risk_name' => 'ระบบ IT สำคัญไม่พร้อมใช้งาน',
                'description' => 'ความเสี่ยงที่ระบบเทคโนโลยีสารสนเทศสำคัญของฝ่ายอาจไม่พร้อมใช้งาน ส่งผลกระทบต่อการปฏิบัติงาน',
                'year' => 2024,
                'organizational_risk_id' => $orgRisks->where('risk_name', 'ความเสี่ยงด้านเทคโนโลยีและการรักษาความปลอดภัยทางไซเบอร์')->first()->id ?? $orgRisks->first()->id,
            ],
        ];

        foreach ($departmentRisks as $risk) {
            DepartmentRisk::create($risk);
        }

        // สร้างข้อมูลเพิ่มเติมแบบสุ่ม
        foreach ($orgRisks as $orgRisk) {
            DepartmentRisk::factory()->count(2)->create([
                'organizational_risk_id' => $orgRisk->id,
                'year' => 2024,
            ]);
        }
    }
}
