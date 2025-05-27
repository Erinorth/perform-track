<?php
// ไฟล์: app\Http\Requests\UpdateRiskAssessmentRequest.php
// Request สำหรับตรวจสอบข้อมูลการแก้ไขการประเมินความเสี่ยง

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UpdateRiskAssessmentRequest extends FormRequest
{
    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการแก้ไขการประเมินความเสี่ยงหรือไม่
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
            'assessment_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100'
            ],
            'assessment_period' => [
                'required',
                'integer',
                'in:1,2'
            ],
            'likelihood_level' => [
                'required',
                'integer',
                'min:1',
                'max:4'
            ],
            'impact_level' => [
                'required',
                'integer',
                'min:1',
                'max:4'
            ],
            'division_risk_id' => [
                'required',
                'integer',
                'exists:division_risks,id'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000'
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
                'exists:risk_assessment_attachments,id'
            ],
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่กำหนดเอง
     */
    public function messages(): array
    {
        return [
            'assessment_year.required' => 'กรุณาระบุปีที่ประเมิน',
            'assessment_year.integer' => 'ปีที่ประเมินต้องเป็นตัวเลข',
            'assessment_year.min' => 'ปีที่ประเมินต้องไม่น้อยกว่า 2000',
            'assessment_year.max' => 'ปีที่ประเมินต้องไม่เกิน 2100',
            'assessment_period.required' => 'กรุณาระบุงวดการประเมิน',
            'assessment_period.integer' => 'งวดการประเมินต้องเป็นตัวเลข',
            'assessment_period.in' => 'งวดการประเมินต้องเป็น 1 (ครึ่งปีแรก) หรือ 2 (ครึ่งปีหลัง)',
            'likelihood_level.required' => 'กรุณาระบุระดับโอกาสเกิด',
            'likelihood_level.integer' => 'ระดับโอกาสเกิดต้องเป็นตัวเลข',
            'likelihood_level.min' => 'ระดับโอกาสเกิดต้องมีค่าอย่างน้อย 1',
            'likelihood_level.max' => 'ระดับโอกาสเกิดต้องมีค่าไม่เกิน 4',
            'impact_level.required' => 'กรุณาระบุระดับผลกระทบ',
            'impact_level.integer' => 'ระดับผลกระทบต้องเป็นตัวเลข',
            'impact_level.min' => 'ระดับผลกระทบต้องมีค่าอย่างน้อย 1',
            'impact_level.max' => 'ระดับผลกระทบต้องมีค่าไม่เกิน 4',
            'division_risk_id.required' => 'กรุณาเลือกความเสี่ยงระดับฝ่าย',
            'division_risk_id.integer' => 'รหัสความเสี่ยงระดับฝ่ายต้องเป็นตัวเลข',
            'division_risk_id.exists' => 'ความเสี่ยงระดับฝ่ายที่เลือกไม่มีอยู่ในระบบ',
            'notes.string' => 'บันทึกเพิ่มเติมต้องเป็นข้อความ',
            'notes.max' => 'บันทึกเพิ่มเติมมีความยาวได้ไม่เกิน 1,000 ตัวอักษร',
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
            'assessment_year' => 'ปีที่ประเมิน',
            'assessment_period' => 'งวดการประเมิน',
            'likelihood_level' => 'ระดับโอกาสเกิด',
            'impact_level' => 'ระดับผลกระทบ',
            'division_risk_id' => 'ความเสี่ยงระดับฝ่าย',
            'notes' => 'บันทึกเพิ่มเติม',
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
            'notes' => $this->notes ? trim($this->notes) : null,
            'assessment_year' => $this->assessment_year ?: date('Y'),
        ]);
    }

    /**
     * บันทึกข้อมูลเพิ่มเติมลง log เมื่อการตรวจสอบเกิดข้อผิดพลาด
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $riskAssessment = $this->route('risk_assessment') ?? $this->route('riskAssessment');
        
        Log::warning('การตรวจสอบข้อมูลการแก้ไขการประเมินความเสี่ยงล้มเหลว', [
            'risk_assessment_id' => $riskAssessment ? $riskAssessment->id : null,
            'assessment_year' => $riskAssessment ? $riskAssessment->assessment_year : null,
            'assessment_period' => $riskAssessment ? $riskAssessment->assessment_period : null,
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
