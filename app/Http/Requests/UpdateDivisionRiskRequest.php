<?php
// ไฟล์: app\Http\Requests\UpdateDivisionRiskRequest.php
// Request สำหรับตรวจสอบข้อมูลการแก้ไขความเสี่ยงระดับฝ่าย

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UpdateDivisionRiskRequest extends FormRequest
{
    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการแก้ไขความเสี่ยงระดับฝ่ายหรือไม่
     */
    public function authorize(): bool
    {
        return Auth::check(); // ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
    }

    /**
     * กำหนดกฎการตรวจสอบความถูกต้องของข้อมูล
     */
    public function rules(): array
    {
        $divisionRiskId = $this->route('division_risk') ? 
            $this->route('division_risk')->id : 
            $this->route('divisionRisk')->id;

        return [
            'risk_name' => [
                'required',
                'string',
                'max:255',
                'unique:division_risks,risk_name,' . $divisionRiskId . ',id,organizational_risk_id,' . $this->organizational_risk_id
            ],
            'description' => [
                'required',
                'string',
                'max:2000'
            ],
            'organizational_risk_id' => [
                'nullable',
                'integer',
                'exists:organizational_risks,id'
            ],
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => [
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                'max:10240' // 10MB
            ],
            'attachments_to_delete' => 'nullable|array',
            'attachments_to_delete.*' => [
                'integer',
                'exists:division_risk_attachments,id'
            ],
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่กำหนดเอง
     */
    public function messages(): array
    {
        return [
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'risk_name.string' => 'ชื่อความเสี่ยงต้องเป็นข้อความ',
            'risk_name.max' => 'ชื่อความเสี่ยงต้องไม่เกิน 255 ตัวอักษร',
            'risk_name.unique' => 'ชื่อความเสี่ยงนี้มีอยู่แล้วในความเสี่ยงระดับองค์กรเดียวกัน',
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            'description.string' => 'รายละเอียดความเสี่ยงต้องเป็นข้อความ',
            'description.max' => 'รายละเอียดความเสี่ยงต้องไม่เกิน 2,000 ตัวอักษร',
            'organizational_risk_id.integer' => 'รหัสความเสี่ยงระดับองค์กรต้องเป็นตัวเลข',
            'organizational_risk_id.exists' => 'ความเสี่ยงระดับองค์กรที่เลือกไม่มีอยู่ในระบบ',
            'attachments.array' => 'ไฟล์แนบต้องเป็นรูปแบบ array',
            'attachments.max' => 'สามารถแนบไฟล์ได้สูงสุด 10 ไฟล์',
            'attachments.*.file' => 'ไฟล์แนบไม่ถูกต้อง',
            'attachments.*.mimes' => 'รองรับเฉพาะไฟล์ PDF, Word, Excel และรูปภาพเท่านั้น',
            'attachments.*.max' => 'ขนาดไฟล์ต้องไม่เกิน 10MB',
            'attachments_to_delete.array' => 'รายการไฟล์ที่ต้องการลบต้องเป็นรูปแบบ array',
            'attachments_to_delete.*.integer' => 'รหัสไฟล์ที่ต้องการลบต้องเป็นตัวเลข',
            'attachments_to_delete.*.exists' => 'ไฟล์แนบที่ต้องการลบไม่มีอยู่ในระบบ',
        ];
    }

    /**
     * กำหนดชื่อฟิลด์ที่ใช้ในข้อความแสดงข้อผิดพลาด
     */
    public function attributes(): array
    {
        return [
            'risk_name' => 'ชื่อความเสี่ยง',
            'description' => 'รายละเอียดความเสี่ยง',
            'organizational_risk_id' => 'ความเสี่ยงระดับองค์กร',
            'attachments' => 'ไฟล์แนบ',
            'attachments_to_delete' => 'ไฟล์ที่ต้องการลบ',
        ];
    }

    /**
     * เตรียมข้อมูลก่อนการตรวจสอบ
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'risk_name' => $this->risk_name ? trim($this->risk_name) : null,
            'description' => $this->description ? trim($this->description) : null,
            'organizational_risk_id' => $this->organizational_risk_id ?: null,
        ]);
    }

    /**
     * บันทึกข้อมูลเพิ่มเติมลง log เมื่อการตรวจสอบเกิดข้อผิดพลาด
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $divisionRisk = $this->route('division_risk') ?? $this->route('divisionRisk');
        
        Log::warning('การตรวจสอบข้อมูลการแก้ไขความเสี่ยงระดับฝ่ายล้มเหลว', [
            'division_risk_id' => $divisionRisk ? $divisionRisk->id : null,
            'risk_name' => $divisionRisk ? $divisionRisk->risk_name : null,
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['attachments', '_token', '_method']),
            'user_id' => Auth::id(),
            'user_name' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        parent::failedValidation($validator);
    }
}
