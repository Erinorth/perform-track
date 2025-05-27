<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\RiskControl;

// Request สำหรับบันทึกการควบคุมความเสี่ยง
class StoreRiskControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ตรวจสอบสิทธิ์ผ่าน Policy
        return $this->user()->can('create', RiskControl::class);
    }

    public function rules(): array
    {
        return [
            'division_risk_id' => [
                'required',
                'integer',
                'exists:divisionrisks,id',
            ],
            'control_name' => [
                'required',
                'string',
                'max:255',
                'unique:risk_controls,control_name,NULL,id,division_risk_id,' . $this->division_risk_id,
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'owner' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
            'control_type' => [
                'nullable',
                'in:preventive,detective,corrective,compensating',
            ],
            'implementation_details' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'division_risk_id.required' => 'กรุณาเลือกความเสี่ยงระดับฝ่าย',
            'division_risk_id.exists' => 'ความเสี่ยงระดับฝ่ายที่เลือกไม่ถูกต้อง',
            'control_name.required' => 'กรุณาระบุชื่อการควบคุม',
            'control_name.unique' => 'ชื่อการควบคุมนี้มีอยู่แล้วในความเสี่ยงเดียวกัน',
            'control_name.max' => 'ชื่อการควบคุมต้องไม่เกิน 255 ตัวอักษร',
            'description.max' => 'รายละเอียดต้องไม่เกิน 2,000 ตัวอักษร',
            'owner.max' => 'ชื่อผู้รับผิดชอบต้องไม่เกิน 255 ตัวอักษร',
            'status.required' => 'กรุณาเลือกสถานะ',
            'status.in' => 'สถานะต้องเป็น ใช้งาน หรือ ไม่ใช้งาน เท่านั้น',
            'control_type.in' => 'ประเภทการควบคุมไม่ถูกต้อง',
            'implementation_details.max' => 'รายละเอียดการดำเนินการต้องไม่เกิน 3,000 ตัวอักษร',
        ];
    }

    public function attributes(): array
    {
        return [
            'division_risk_id' => 'ความเสี่ยงระดับฝ่าย',
            'control_name' => 'ชื่อการควบคุม',
            'description' => 'รายละเอียด',
            'owner' => 'ผู้รับผิดชอบ',
            'status' => 'สถานะ',
            'control_type' => 'ประเภทการควบคุม',
            'implementation_details' => 'รายละเอียดการดำเนินการ',
        ];
    }

    protected function prepareForValidation()
    {
        // ทำความสะอาดข้อมูลก่อน validation
        $this->merge([
            'control_name' => trim($this->control_name),
            'description' => $this->description ? trim($this->description) : null,
            'owner' => $this->owner ? trim($this->owner) : null,
            'implementation_details' => $this->implementation_details ? trim($this->implementation_details) : null,
        ]);
    }
}
