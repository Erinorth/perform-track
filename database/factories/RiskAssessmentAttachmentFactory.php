<?php

namespace Database\Factories;

use App\Models\RiskAssessmentAttachment;
use App\Models\RiskAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบของไฟล์แนบการประเมินความเสี่ยง
 */
class RiskAssessmentAttachmentFactory extends Factory
{
    protected $model = RiskAssessmentAttachment::class;

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
            'แบบฟอร์มประเมินความเสี่ยง',
            'รายงานผลการประเมิน',
            'เอกสารหลักฐานการประเมิน',
            'แผนภูมิเมทริกซ์ความเสี่ยง',
            'รายงานสรุปความเสี่ยง',
            'เอกสารประกอบการประเมิน',
            'ภาพหน้าจอระบบ',
            'ตารางวิเคราะห์ความเสี่ยง'
        ];

        $fileName = $this->faker->randomElement($sampleFileNames);
        $timestamp = time();

        return [
            'risk_assessment_id' => RiskAssessment::factory(),
            'file_name' => $fileName . '.' . $extension,
            'file_path' => 'risk_assessment/' . $this->faker->numberBetween(1, 100) . '/' . $timestamp . '_' . $fileName . '.' . $extension,
            'file_type' => $mimeTypes[$extension],
            'file_size' => $this->faker->numberBetween(50000, 5000000), // 50KB - 5MB
        ];
    }

    /**
     * สร้างไฟล์แนบสำหรับการประเมินความเสี่ยงที่ระบุ
     */
    public function forRiskAssessment(RiskAssessment $riskAssessment): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_assessment_id' => $riskAssessment->id,
        ]);
    }

    /**
     * สร้างไฟล์ประเภทรูปภาพ
     */
    public function image(): static
    {
        $extensions = ['jpg', 'png'];
        $extension = $this->faker->randomElement($extensions);
        $mimeType = $extension === 'jpg' ? 'image/jpeg' : 'image/png';

        return $this->state(fn (array $attributes) => [
            'file_type' => $mimeType,
            'file_name' => str_replace(pathinfo($attributes['file_name'], PATHINFO_EXTENSION), $extension, $attributes['file_name']),
            'file_path' => str_replace(pathinfo($attributes['file_path'], PATHINFO_EXTENSION), $extension, $attributes['file_path']),
        ]);
    }
}
