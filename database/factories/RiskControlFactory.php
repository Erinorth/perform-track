<?php

namespace Database\Factories;

use App\Models\RiskControl;
use App\Models\DivisionRisk;
use Illuminate\Database\Eloquent\Factories\Factory;

// Factory สำหรับสร้างข้อมูลทดสอบ RiskControl
class RiskControlFactory extends Factory
{
    protected $model = RiskControl::class;

    public function definition(): array
    {
        $controlTypes = ['preventive', 'detective', 'corrective', 'compensating'];
        $statuses = ['active', 'inactive'];
        
        // รายชื่อการควบคุมตัวอย่าง
        $controlNames = [
            'การตรวจสอบสิทธิ์การเข้าถึงระบบ',
            'การสำรองข้อมูลอัตโนมัติ',
            'การตรวจสอบความถูกต้องของข้อมูลทางการเงิน',
            'แผนรองรับเหตุฉุกเฉิน',
            'การประกันภัยความเสี่ยงทางการดำเนินงาน',
            'ระบบแจ้งเตือนความผิดปกติ',
            'การอบรมความปลอดภัยประจำปี',
            'การตรวจสอบภายในรายไตรมาส',
            'ระบบควบคุมการอนุมัติ',
            'การทบทวนนโยบายรายปี',
            'การตรวจสอบการปฏิบัติตามกฎระเบียบ',
            'ระบบติดตามและรายงาน',
            'การจัดการเอกสารและบันทึก',
            'ระบบการแจ้งเหตุการณ์',
            'การประเมินผู้ขายและคู่ค้า'
        ];

        // รายชื่อผู้รับผิดชอบตัวอย่าง
        $owners = [
            'ฝ่ายเทคโนโลยีสารสนเทศ',
            'ฝ่ายบัญชีและการเงิน',
            'ฝ่ายบริหารความเสี่ยง',
            'ฝ่ายตรวจสอบภายใน',
            'ฝ่ายทรัพยากรมนุษย์',
            'ฝ่ายกฎหมายและกำกับดูแล',
            'ฝ่ายปฏิบัติการ',
            'ฝ่ายความปลอดภัย',
            'ฝ่ายวิศวกรรม',
            'ฝ่ายจัดซื้อ'
        ];

        return [
            'division_risk_id' => DivisionRisk::factory(),
            'control_name' => $this->faker->randomElement($controlNames),
            'description' => $this->faker->paragraph(2),
            'owner' => $this->faker->randomElement($owners),
            'status' => $this->faker->randomElement($statuses),
            'control_type' => $this->faker->randomElement($controlTypes),
            'implementation_details' => $this->faker->paragraph(3),
        ];
    }

    /**
     * State สำหรับการควบคุมที่ใช้งาน
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * State สำหรับการควบคุมที่ไม่ใช้งาน
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * State สำหรับการควบคุมแบบป้องกัน
     */
    public function preventive(): static
    {
        return $this->state(fn (array $attributes) => [
            'control_type' => 'preventive',
            'control_name' => 'การป้องกัน: ' . $this->faker->randomElement([
                'การตรวจสอบสิทธิ์การเข้าถึง',
                'การอบรมพนักงาน',
                'การกำหนดนโยบายและขั้นตอน'
            ]),
        ]);
    }

    /**
     * State สำหรับการควบคุมแบบตรวจสอบ
     */
    public function detective(): static
    {
        return $this->state(fn (array $attributes) => [
            'control_type' => 'detective',
            'control_name' => 'การตรวจสอบ: ' . $this->faker->randomElement([
                'ระบบแจ้งเตือนและ monitoring',
                'การตรวจสอบภายใน',
                'การสำรองข้อมูลและตรวจสอบ'
            ]),
        ]);
    }

    /**
     * State สำหรับการควบคุมแบบแก้ไข
     */
    public function corrective(): static
    {
        return $this->state(fn (array $attributes) => [
            'control_type' => 'corrective',
            'control_name' => 'การแก้ไข: ' . $this->faker->randomElement([
                'แผนรองรับเหตุฉุกเฉิน',
                'ขั้นตอนการแก้ไขปัญหา',
                'การปรับปรุงระบบหลังเกิดเหตุ'
            ]),
        ]);
    }

    /**
     * State สำหรับการควบคุมแบบชดเชย
     */
    public function compensating(): static
    {
        return $this->state(fn (array $attributes) => [
            'control_type' => 'compensating',
            'control_name' => 'การชดเชย: ' . $this->faker->randomElement([
                'การประกันภัย',
                'การใช้บริการจากบุคคลภายนอก',
                'ระบบสำรองทดแทน'
            ]),
        ]);
    }

    /**
     * State สำหรับการควบคุมที่มี DivisionRisk ที่กำหนด
     */
    public function forDivisionRisk(DivisionRisk $divisionRisk): static
    {
        return $this->state(fn (array $attributes) => [
            'division_risk_id' => $divisionRisk->id,
        ]);
    }

    /**
     * State สำหรับการควบคุมที่มีเจ้าของที่กำหนด
     */
    public function withOwner(string $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner' => $owner,
        ]);
    }
}
