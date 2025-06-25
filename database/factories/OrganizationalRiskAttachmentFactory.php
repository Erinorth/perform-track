<?php

namespace Database\Factories;

use App\Models\OrganizationalRiskAttachment;
use App\Models\OrganizationalRisk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบของไฟล์แนบความเสี่ยงระดับองค์กร
 */
class OrganizationalRiskAttachmentFactory extends Factory
{
    protected $model = OrganizationalRiskAttachment::class;

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
            'แผนบริหารความเสี่ยง',
            'รายงานประเมินความเสี่ยง',
            'นโยบายบริหารความเสี่ยง',
            'คู่มือการดำเนินงาน',
            'แบบฟอร์มประเมินความเสี่ยง',
            'รายงานการตรวจสอบ',
            'มาตรการควบคุมความเสี่ยง',
            'เอกสารอ้างอิง'
        ];

        $fileName = $this->faker->randomElement($sampleFileNames);
        $timestamp = time();

        return [
            'organizational_risk_id' => OrganizationalRisk::factory(),
            'file_name' => $fileName . '.' . $extension,
            'file_path' => 'organizational_risk/' . $this->faker->numberBetween(1, 100) . '/' . $timestamp . '_' . $fileName . '.' . $extension,
            'file_type' => $mimeTypes[$extension],
            'file_size' => $this->faker->numberBetween(50000, 5000000), // 50KB - 5MB
        ];
    }

    /**
     * สร้างไฟล์แนบสำหรับความเสี่ยงระดับองค์กรที่ระบุ
     */
    public function forOrganizationalRisk(OrganizationalRisk $organizationalRisk): static
    {
        return $this->state(fn (array $attributes) => [
            'organizational_risk_id' => $organizationalRisk->id,
        ]);
    }

    /**
     * สร้างไฟล์ประเภท PDF
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'application/pdf',
            'file_name' => str_replace(pathinfo($attributes['file_name'], PATHINFO_EXTENSION), 'pdf', $attributes['file_name']),
            'file_path' => str_replace(pathinfo($attributes['file_path'], PATHINFO_EXTENSION), 'pdf', $attributes['file_path']),
        ]);
    }
}
