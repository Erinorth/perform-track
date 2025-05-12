<?php

namespace Database\Seeders;

use App\Models\DivisionRisk;
use App\Models\OrganizationalRisk;
use Illuminate\Database\Seeder;

class DivisionRiskSeeder extends Seeder
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
        $divisionRisks = [
            // ความเสี่ยงด้านการเงิน
            [
                'risk_name' => 'การบริหารงบประมาณไม่เป็นไปตามแผน',
                'description' => 'ความเสี่ยงที่การใช้จ่ายงบประมาณของฝ่ายอาจเกินกว่าที่กำหนด หรือไม่เป็นไปตามแผนงานที่วางไว้',
                'organizational_risk_id' => $orgRisks->where('risk_name', 'ความเสี่ยงด้านการเงิน')->first()->id ?? $orgRisks->first()->id,
            ],
            [
                'risk_name' => 'การจัดซื้อจัดจ้างล่าช้า',
                'description' => 'ความเสี่ยงที่กระบวนการจัดซื้อจัดจ้างอาจล่าช้า ส่งผลให้การดำเนินงานตามแผนงานหรือโครงการไม่เป็นไปตามกำหนด',
                'organizational_risk_id' => $orgRisks->where('risk_name', 'ความเสี่ยงด้านการเงิน')->first()->id ?? $orgRisks->first()->id,
            ],
            
            // ความเสี่ยงด้านเทคโนโลยี
            [
                'risk_name' => 'ระบบ IT สำคัญไม่พร้อมใช้งาน',
                'description' => 'ความเสี่ยงที่ระบบเทคโนโลยีสารสนเทศสำคัญของฝ่ายอาจไม่พร้อมใช้งาน ส่งผลกระทบต่อการปฏิบัติงาน',
                'organizational_risk_id' => $orgRisks->where('risk_name', 'ความเสี่ยงด้านเทคโนโลยีและการรักษาความปลอดภัยทางไซเบอร์')->first()->id ?? $orgRisks->first()->id,
            ],
        ];

        foreach ($divisionRisks as $risk) {
            DivisionRisk::create($risk);
        }

        // สร้างข้อมูลเพิ่มเติมแบบสุ่ม
        foreach ($orgRisks as $orgRisk) {
            DivisionRisk::factory()->count(2)->create([
                'organizational_risk_id' => $orgRisk->id,
            ]);
        }
    }
}
