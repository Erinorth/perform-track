<?php
// ไฟล์: app\Http\Requests\StoreRiskControlRequest.php
// Request สำหรับตรวจสอบข้อมูลการสร้างการควบคุมความเสี่ยง

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StoreRiskControlRequest extends FormRequest
{
    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการสร้างการควบคุมความเสี่ยงหรือไม่
     */
    public function authorize(): bool
    {
        return Auth::check(); // ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
    }

    /**
     * กำหนดกฎการตรวจสอบข้อมูล
     */
    public function rules(): array
    {
        return [
            'division_risk_id' => [
                'required',
                'integer',
                'exists:division_risks,id',
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
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => [
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                'max:10240' // 10MB
            ],
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่กำหนดเอง
     */
    public function messages(): array
    {
        return [
            'division_risk_id.required' => 'กรุณาเลือกความเสี่ยงระดับฝ่าย',
            'division_risk_id.integer' => 'รหัสความเสี่ยงระดับฝ่ายต้องเป็นตัวเลข',
            'division_risk_id.exists' => 'ความเสี่ยงระดับฝ่ายที่เลือกไม่ถูกต้อง',
            'control_name.required' => 'กรุณาระบุชื่อการควบคุม',
            'control_name.string' => 'ชื่อการควบคุมต้องเป็นข้อความ',
            'control_name.max' => 'ชื่อการควบคุมต้องไม่เกิน 255 ตัวอักษร',
            'control_name.unique' => 'ชื่อการควบคุมนี้มีอยู่แล้วในความเสี่ยงเดียวกัน',
            'description.string' => 'รายละเอียดต้องเป็นข้อความ',
            'description.max' => 'รายละเอียดต้องไม่เกิน 2,000 ตัวอักษร',
            'owner.string' => 'ชื่อผู้รับผิดชอบต้องเป็นข้อความ',
            'owner.max' => 'ชื่อผู้รับผิดชอบต้องไม่เกิน 255 ตัวอักษร',
            'status.required' => 'กรุณาเลือกสถานะ',
            'status.in' => 'สถานะต้องเป็น ใช้งาน หรือ ไม่ใช้งาน เท่านั้น',
            'control_type.in' => 'ประเภทการควบคุมต้องเป็น การป้องกัน, การตรวจจับ, การแก้ไข หรือ การชดเชย',
            'implementation_details.string' => 'รายละเอียดการดำเนินการต้องเป็นข้อความ',
            'implementation_details.max' => 'รายละเอียดการดำเนินการต้องไม่เกิน 3,000 ตัวอักษร',
            'attachments.array' => 'ไฟล์แนบต้องเป็นรูปแบบ array',
            'attachments.max' => 'สามารถแนบไฟล์ได้สูงสุด 10 ไฟล์',
            'attachments.*.file' => 'ไฟล์แนบไม่ถูกต้อง',
            'attachments.*.mimes' => 'รองรับเฉพาะไฟล์ PDF, Word, Excel และรูปภาพเท่านั้น',
            'attachments.*.max' => 'ขนาดไฟล์ต้องไม่เกิน 10MB',
        ];
    }

    /**
     * กำหนดชื่อฟิลด์ที่ใช้ในข้อความแสดงข้อผิดพลาด
     */
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
            'attachments' => 'ไฟล์แนบ',
        ];
    }

    /**
     * เตรียมข้อมูลก่อนการตรวจสอบ
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'control_name' => $this->control_name ? trim($this->control_name) : null,
            'description' => $this->description ? trim($this->description) : null,
            'owner' => $this->owner ? trim($this->owner) : null,
            'implementation_details' => $this->implementation_details ? trim($this->implementation_details) : null,
        ]);
    }

    /**
     * บันทึกข้อมูลเพิ่มเติมลง log เมื่อการตรวจสอบเกิดข้อผิดพลาด
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        Log::warning('การตรวจสอบข้อมูลการควบคุมความเสี่ยงล้มเหลว', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['attachments']),
            'user_id' => Auth::id(),
            'user_name' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        parent::failedValidation($validator);
    }
}
