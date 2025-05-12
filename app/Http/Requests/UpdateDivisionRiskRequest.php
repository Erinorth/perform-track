<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\DivisionRisk;

class UpdateDivisionRiskRequest extends FormRequest
{
    /**
     * กำหนดว่าผู้ใช้มีสิทธิ์ในการอัปเดตความเสี่ยงระดับฝ่ายหรือไม่
     */
    public function authorize(): bool
    {
        $divisionRisk = $this->route('divisionRisk');
        return Auth::check() && $divisionRisk instanceof DivisionRisk;
    }

    /**
     * กำหนดกฎการตรวจสอบความถูกต้องของข้อมูล
     */
    public function rules(): array
    {
        return [
            'risk_name' => 'required|string|max:255',
            'description' => 'required|string',
            'organizational_risk_id' => 'nullable|exists:organizational_risks,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'attachments_to_delete' => 'nullable|array',
            'attachments_to_delete.*' => 'integer|exists:division_risk_attachments,id',
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่กำหนดเอง
     */
    public function messages(): array
    {
        return [
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'risk_name.max' => 'ชื่อความเสี่ยงต้องไม่เกิน 255 ตัวอักษร',
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            'organizational_risk_id.exists' => 'ความเสี่ยงระดับองค์กรที่เลือกไม่มีอยู่ในระบบ',
            'attachments.*.file' => 'ไฟล์แนบต้องเป็นไฟล์ที่ถูกต้อง',
            'attachments.*.mimes' => 'ไฟล์แนบต้องเป็นรูปแบบ PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG หรือ PNG เท่านั้น',
            'attachments.*.max' => 'ขนาดไฟล์แนบต้องไม่เกิน 10MB',
            'attachments_to_delete.*.exists' => 'ไฟล์แนบที่ต้องการลบไม่มีอยู่ในระบบ',
        ];
    }
}
