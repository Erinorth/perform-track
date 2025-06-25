<?php

namespace Database\Factories;

use App\Models\RiskControlAttachment;
use App\Models\RiskControl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบของไฟล์แนบการควบคุมความเสี่ยง
 */
class RiskControlAttachmentFactory extends Factory
{
    protected $model = RiskControlAttachment::class;

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
            'คู่มือการควบคุมความเสี่ยง',
            'ขั้นตอนการดำเนินงาน',
            'รายงานการตรวจสอบการควบคุม',
            'เอกสารหลักฐานการควบคุม',
            'แผนการปรับปรุงการควบคุม',
            'รายงานประสิทธิผลการควบคุม',
            'เอกสารการอบรมพนักงาน',
            'ใบรับรองการตรวจสอบ'
        ];

        $fileName = $this->faker->randomElement($sampleFileNames);
        $timestamp = time();

        return [
            'risk_control_id' => RiskControl::factory(),
            'file_name' => $fileName . '.' . $extension,
            'file_path' => 'risk_control/' . $this->faker->numberBetween(1, 100) . '/' . $timestamp . '_' . $fileName . '.' . $extension,
            'file_type' => $mimeTypes[$extension],
            'file_size' => $this->faker->numberBetween(50000, 5000000), // 50KB - 5MB
        ];
    }

    /**
     * สร้างไฟล์แนบสำหรับการควบคุมความเสี่ยงที่ระบุ
     */
    public function forRiskControl(RiskControl $riskControl): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_control_id' => $riskControl->id,
        ]);
    }

    /**
     * สร้างไฟล์ประเภทเอกสาร Word
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_name' => str_replace(pathinfo($attributes['file_name'], PATHINFO_EXTENSION), 'docx', $attributes['file_name']),
            'file_path' => str_replace(pathinfo($attributes['file_path'], PATHINFO_EXTENSION), 'docx', $attributes['file_path']),
        ]);
    }
}
