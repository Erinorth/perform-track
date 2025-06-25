<?php

namespace Database\Factories;

use App\Models\DivisionRiskAttachment;
use App\Models\DivisionRisk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบของไฟล์แนบความเสี่ยงระดับหน่วยงาน
 */
class DivisionRiskAttachmentFactory extends Factory
{
    protected $model = DivisionRiskAttachment::class;

    public function definition(): array
    {
        // รายการนามสกุลไฟล์ที่ใช้ในระบบ
        $fileExtensions = ['pdf', 'docx', 'xlsx', 'pptx', 'jpg', 'png'];
        $extension = $this->faker->randomElement($fileExtensions);
        
        // รายการประเภทไฟล์ตาม MIME type
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        // รายการชื่อไฟล์ตัวอย่างเป็นภาษาไทย
        $sampleFileNames = [
            'รายละเอียดความเสี่ยงหน่วยงาน',
            'แผนปฏิบัติการจัดการความเสี่ยง',
            'รายงานติดตามความเสี่ยง',
            'มาตรการป้องกันความเสี่ยง',
            'แผนภูมิกระบวนการ',
            'เอกสารวิเคราะห์ความเสี่ยง',
            'คำอธิบายขั้นตอนการควบคุม',
            'รายงานผลการประเมิน'
        ];

        $fileName = $this->faker->randomElement($sampleFileNames);
        $timestamp = time();

        return [
            'division_risk_id' => DivisionRisk::factory(),
            'file_name' => $fileName . '.' . $extension,
            'file_path' => 'division_risk/' . $this->faker->numberBetween(1, 100) . '/' . $timestamp . '_' . $fileName . '.' . $extension,
            'file_type' => $mimeTypes[$extension],
            'file_size' => $this->faker->numberBetween(50000, 5000000), // 50KB - 5MB
        ];
    }

    /**
     * สร้างไฟล์แนบสำหรับความเสี่ยงระดับหน่วยงานที่ระบุ
     */
    public function forDivisionRisk(DivisionRisk $divisionRisk): static
    {
        return $this->state(fn (array $attributes) => [
            'division_risk_id' => $divisionRisk->id,
        ]);
    }

    /**
     * สร้างไฟล์ประเภท Excel
     */
    public function excel(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'file_name' => str_replace(pathinfo($attributes['file_name'], PATHINFO_EXTENSION), 'xlsx', $attributes['file_name']),
            'file_path' => str_replace(pathinfo($attributes['file_path'], PATHINFO_EXTENSION), 'xlsx', $attributes['file_path']),
        ]);
    }
}
