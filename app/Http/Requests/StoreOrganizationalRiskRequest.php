<?php
// ไฟล์: app\Http\Requests\StoreOrganizationalRiskRequest.php
// Request สำหรับตรวจสอบข้อมูลการสร้างความเสี่ยงระดับองค์กร
// ใช้เพื่อแยกกฎการตรวจสอบข้อมูล (validation rules) ออกจาก Controller

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StoreOrganizationalRiskRequest extends FormRequest
{
    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ที่จะทำคำขอนี้หรือไม่
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
        return [
            'risk_name' => [
                'required',
                'string',
                'max:255',
                'unique:organizational_risks,risk_name'
            ],
            'description' => [
                'required',
                'string',
                'max:2000'
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
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'risk_name.string' => 'ชื่อความเสี่ยงต้องเป็นข้อความ',
            'risk_name.max' => 'ชื่อความเสี่ยงต้องไม่เกิน 255 ตัวอักษร',
            'risk_name.unique' => 'ชื่อความเสี่ยงนี้มีอยู่แล้วในระบบ',
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            'description.string' => 'รายละเอียดความเสี่ยงต้องเป็นข้อความ',
            'description.max' => 'รายละเอียดความเสี่ยงต้องไม่เกิน 2,000 ตัวอักษร',
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
            'risk_name' => 'ชื่อความเสี่ยง',
            'description' => 'รายละเอียดความเสี่ยง',
            'attachments' => 'ไฟล์แนบ',
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
        ]);
    }

    /**
     * บันทึกข้อมูลเพิ่มเติมลง log เมื่อการตรวจสอบเกิดข้อผิดพลาด
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        Log::warning('การตรวจสอบข้อมูลความเสี่ยงระดับองค์กรล้มเหลว', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['attachments']), // ไม่บันทึกข้อมูลไฟล์
            'user_id' => Auth::id(),
            'user_name' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        parent::failedValidation($validator);
    }
}
