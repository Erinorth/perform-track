<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RiskControl;
use App\Models\DivisionRisk;
use App\Models\OrganizationalRisk;

// Seeder สำหรับข้อมูลตัวอย่างการควบคุมความเสี่ยง
class RiskControlSeeder extends Seeder
{
    public function run(): void
    {
        \Log::info('Starting RiskControlSeeder');

        // ตรวจสอบว่ามี DivisionRisk หรือไม่
        $divisionRisks = DivisionRisk::all();

        if ($divisionRisks->isEmpty()) {
            $this->command->warn('ไม่พบข้อมูล DivisionRisk กรุณารัน DivisionRiskSeeder ก่อน');
            return;
        }

        // ข้อมูลการควบคุมตัวอย่างแยกตามประเภท
        $riskControlsData = [
            // การควบคุมแบบป้องกัน (Preventive)
            [
                'control_name' => 'การตรวจสอบสิทธิ์การเข้าถึงระบบ',
                'description' => 'กำหนดระบบตรวจสอบสิทธิ์การเข้าถึงข้อมูลและระบบสำคัญ ด้วยการใช้ Multi-Factor Authentication',
                'owner' => 'ฝ่ายเทคโนโลยีสารสนเทศ',
                'control_type' => 'preventive',
                'implementation_details' => 'ติดตั้งระบบ MFA, ทบทวนสิทธิ์รายเดือน, อบรมผู้ใช้งาน',
                'status' => 'active'
            ],
            [
                'control_name' => 'การอบรมความปลอดภัยประจำปี',
                'description' => 'การอบรมพนักงานเรื่องความปลอดภัยในการทำงานและการป้องกันความเสี่ยง',
                'owner' => 'ฝ่ายทรัพยากรมนุษย์',
                'control_type' => 'preventive',
                'implementation_details' => 'จัดอบรมปีละ 2 ครั้ง, ทดสอบความรู้หลังอบรม, ติดตามผล',
                'status' => 'active'
            ],
            [
                'control_name' => 'การกำหนดนโยบายและขั้นตอนการปฏิบัติงาน',
                'description' => 'จัดทำและทบทวนนโยบายการปฏิบัติงานเพื่อป้องกันความเสี่ยง',
                'owner' => 'ฝ่ายกฎหมายและกำกับดูแล',
                'control_type' => 'preventive',
                'implementation_details' => 'ทบทวนนโยบายรายปี, เผยแพร่ให้พนักงาน, ติดตามการปฏิบัติตาม',
                'status' => 'active'
            ],

            // การควบคุมแบบตรวจสอบ (Detective)
            [
                'control_name' => 'การสำรองข้อมูลและตรวจสอบ',
                'description' => 'ระบบสำรองข้อมูลอัตโนมัติและการตรวจสอบความสมบูรณ์ของข้อมูล',
                'owner' => 'ฝ่ายเทคโนโลยีสารสนเทศ',
                'control_type' => 'detective',
                'implementation_details' => 'สำรองข้อมูลทุก 6 ชั่วโมง, ทดสอบการกู้คืนรายสัปดาห์, รายงานสถานะรายวัน',
                'status' => 'active'
            ],
            [
                'control_name' => 'การตรวจสอบภายในรายไตรมาส',
                'description' => 'การตรวจสอบการปฏิบัติงานและการควบคุมภายในตามแผนที่กำหนด',
                'owner' => 'ฝ่ายตรวจสอบภายใน',
                'control_type' => 'detective',
                'implementation_details' => 'จัดทำแผนตรวจสอบประจำปี, ตรวจสอบตามกำหนดการ, รายงานผลการตรวจสอบ',
                'status' => 'active'
            ],
            [
                'control_name' => 'ระบบแจ้งเตือนความผิดปกติ',
                'description' => 'ระบบ monitoring และแจ้งเตือนเมื่อพบความผิดปกติในการดำเนินงาน',
                'owner' => 'ฝ่ายปฏิบัติการ',
                'control_type' => 'detective',
                'implementation_details' => 'ตั้งค่า alert ในระบบ, monitor 24/7, มีขั้นตอนการตอบสนอง',
                'status' => 'active'
            ],

            // การควบคุมแบบแก้ไข (Corrective)
            [
                'control_name' => 'แผนรองรับเหตุฉุกเฉิน (BCP)',
                'description' => 'แผนการดำเนินงานต่อเนื่องเมื่อเกิดเหตุฉุกเฉินหรือภาวะวิกฤต',
                'owner' => 'ฝ่ายบริหารความเสี่ยง',
                'control_type' => 'corrective',
                'implementation_details' => 'จัดทำแผน BCP, ทดสอบแผนทุก 6 เดือน, อบรมทีมงาน, ปรับปรุงตามผลการทดสอบ',
                'status' => 'active'
            ],
            [
                'control_name' => 'ขั้นตอนการแก้ไขปัญหาด่วน',
                'description' => 'กระบวนการแก้ไขปัญหาเร่งด่วนและการป้องกันปัญหาซ้ำ',
                'owner' => 'ฝ่ายปฏิบัติการ',
                'control_type' => 'corrective',
                'implementation_details' => 'มีทีมงานเวรรับผิดชอบ, ขั้นตอนการแก้ไขที่ชัดเจน, การวิเคราะห์สาเหตุ',
                'status' => 'active'
            ],

            // การควบคุมแบบชดเชย (Compensating)
            [
                'control_name' => 'การประกันภัยความเสี่ยงทางการดำเนินงาน',
                'description' => 'การจัดหาประกันภัยเพื่อชดเชยความเสียหายจากความเสี่ยงทางการดำเนินงาน',
                'owner' => 'ฝ่ายบริหารความเสี่ยง',
                'control_type' => 'compensating',
                'implementation_details' => 'ทบทวนและต่ออายุกรมธรรม์ประจำปี, ประเมินวงเงินคุ้มครอง, ติดตามข้อเรียกร้อง',
                'status' => 'active'
            ],
            [
                'control_name' => 'การใช้บริการจากผู้เชี่ยวชาญภายนอก',
                'description' => 'การจ้างที่ปรึกษาหรือผู้เชี่ยวชาญภายนอกเมื่อขาดความเชี่ยวชาญภายใน',
                'owner' => 'ฝ่ายจัดซื้อ',
                'control_type' => 'compensating',
                'implementation_details' => 'มีรายชื่อผู้เชี่ยวชาญที่เชื่อถือได้, สัญญาให้บริการที่ชัดเจน',
                'status' => 'active'
            ],

            // การควบคุมเพิ่มเติม
            [
                'control_name' => 'การตรวจสอบความถูกต้องของข้อมูลทางการเงิน',
                'description' => 'กระบวนการตรวจสอบและยืนยันความถูกต้องของรายงานทางการเงิน',
                'owner' => 'ฝ่ายบัญชีและการเงิน',
                'control_type' => 'detective',
                'implementation_details' => 'ทบทวนรายงานก่อนอนุมัติ, ใช้ระบบ 4 ตา, มีการ reconcile รายเดือน',
                'status' => 'active'
            ],
            [
                'control_name' => 'ระบบควบคุมการอนุมัติ',
                'description' => 'ระบบการอนุมัติแบบหลายขั้นตอนตามวงเงินและความสำคัญ',
                'owner' => 'ฝ่ายบัญชีและการเงิน',
                'control_type' => 'preventive',
                'implementation_details' => 'กำหนดอำนาจอนุมัติตามตำแหน่ง, ใช้ระบบ workflow อิเล็กทรอนิกส์',
                'status' => 'active'
            ]
        ];

        // สร้างข้อมูลการควบคุม
        foreach ($riskControlsData as $index => $controlData) {
            // สุ่มเลือก division risk
            $divisionRisk = $divisionRisks->random();
            
            RiskControl::create(array_merge($controlData, [
                'division_risk_id' => $divisionRisk->id
            ]));

            // แสดงความคืบหน้า
            if (($index + 1) % 5 === 0) {
                $this->command->info('สร้างข้อมูล Risk Control แล้ว ' . ($index + 1) . ' รายการ');
            }
        }

        // สร้างข้อมูลเพิ่มเติมด้วย Factory สำหรับแต่ละ DivisionRisk
        $this->command->info('กำลังสร้างข้อมูลเพิ่มเติมด้วย Factory...');
        
        foreach ($divisionRisks->take(10) as $divisionRisk) {
            // สร้างการควบคุม 2-5 รายการต่อ division risk
            $count = rand(2, 5);
            RiskControl::factory()
                ->count($count)
                ->forDivisionRisk($divisionRisk)
                ->create();
        }

        // สร้างข้อมูลเพิ่มเติมแบบสุ่ม
        RiskControl::factory()
            ->count(20)
            ->create();

        $totalControls = RiskControl::count();
        $activeControls = RiskControl::active()->count();
        $inactiveControls = RiskControl::inactive()->count();

        $this->command->info("สร้างข้อมูล Risk Controls เรียบร้อยแล้ว");
        $this->command->info("จำนวนรวม: {$totalControls} รายการ");
        $this->command->info("ใช้งาน: {$activeControls} รายการ");
        $this->command->info("ไม่ใช้งาน: {$inactiveControls} รายการ");

        // แสดงสถิติตามประเภท
        $preventive = RiskControl::byType('preventive')->count();
        $detective = RiskControl::byType('detective')->count();
        $corrective = RiskControl::byType('corrective')->count();
        $compensating = RiskControl::byType('compensating')->count();

        $this->command->table(
            ['ประเภทการควบคุม', 'จำนวน'],
            [
                ['ป้องกัน (Preventive)', $preventive],
                ['ตรวจสอบ (Detective)', $detective],
                ['แก้ไข (Corrective)', $corrective],
                ['ชดเชย (Compensating)', $compensating],
            ]
        );

        \Log::info('Completed RiskControlSeeder', [
            'total' => $totalControls,
            'active' => $activeControls,
            'inactive' => $inactiveControls
        ]);
    }
}
