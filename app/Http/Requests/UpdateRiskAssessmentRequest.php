<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\RiskAssessment;

class UpdateRiskAssessmentRequest extends FormRequest
{
    /**
     * กำหนดว่าผู้ใช้มีสิทธิ์ในการอัปเดตการประเมินความเสี่ยงหรือไม่
     */
    public function authorize(): bool
    {
        $riskAssessment = $this->route('riskAssessment');
        return Auth::check() && $riskAssessment instanceof RiskAssessment;
    }

    /**
     * กำหนดกฎการตรวจสอบความถูกต้องของข้อมูล
     */
    public function rules(): array
    {
        return [
            'assessment_date' => 'required|date',
            'likelihood_level' => 'required|integer|min:1|max:4',
            'impact_level' => 'required|integer|min:1|max:4',
            'division_risk_id' => 'required|exists:division_risks,id',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'attachments_to_delete' => 'nullable|array',
            'attachments_to_delete.*' => 'integer|exists:risk_assessment_attachments,id',
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่กำหนดเอง
     */
    public function messages(): array
    {
        return [
            'assessment_date.required' => 'กรุณาระบุวันที่ประเมิน',
            'assessment_date.date' => 'วันที่ประเมินต้องอยู่ในรูปแบบวันที่ที่ถูกต้อง',
            'likelihood_level.required' => 'กรุณาระบุระดับโอกาสเกิด',
            'likelihood_level.integer' => 'ระดับโอกาสเกิดต้องเป็นตัวเลข',
            'likelihood_level.min' => 'ระดับโอกาสเกิดต้องมีค่าอย่างน้อย 1',
            'likelihood_level.max' => 'ระดับโอกาสเกิดต้องมีค่าไม่เกิน 4',
            'impact_level.required' => 'กรุณาระบุระดับผลกระทบ',
            'impact_level.integer' => 'ระดับผลกระทบต้องเป็นตัวเลข',
            'impact_level.min' => 'ระดับผลกระทบต้องมีค่าอย่างน้อย 1',
            'impact_level.max' => 'ระดับผลกระทบต้องมีค่าไม่เกิน 4',
            'division_risk_id.required' => 'กรุณาเลือกความเสี่ยงระดับฝ่าย',
            'division_risk_id.exists' => 'ความเสี่ยงระดับฝ่ายที่เลือกไม่มีอยู่ในระบบ',
            'attachments.*.file' => 'ไฟล์แนบต้องเป็นไฟล์ที่ถูกต้อง',
            'attachments.*.mimes' => 'ไฟล์แนบต้องเป็นรูปแบบ PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG หรือ PNG เท่านั้น',
            'attachments.*.max' => 'ขนาดไฟล์แนบต้องไม่เกิน 10MB',
            'attachments_to_delete.*.exists' => 'ไฟล์แนบที่ต้องการลบไม่มีอยู่ในระบบ',
        ];
    }
}
