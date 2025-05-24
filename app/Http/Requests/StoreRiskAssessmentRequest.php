<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRiskAssessmentRequest extends FormRequest
{
    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการสร้างการประเมินความเสี่ยงหรือไม่
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
            'assessment_year' => 'required|integer|min:2000|max:2100',
            'assessment_period' => 'required|integer|in:1,2',
            'likelihood_level' => 'required|integer|min:1|max:4',
            'impact_level' => 'required|integer|min:1|max:4',
            'division_risk_id' => 'required|exists:division_risks,id',
            'notes' => 'nullable|string|max:1000'
        ];
    }
    
    /**
     * กำหนดข้อความแสดงข้อผิดพลาด
     */
    public function messages(): array
    {
        return [
            'assessment_year.required' => 'กรุณาระบุปีที่ประเมิน',
            'assessment_year.integer' => 'ปีที่ประเมินต้องเป็นตัวเลข',
            'assessment_year.min' => 'ปีที่ประเมินไม่ถูกต้อง',
            'assessment_year.max' => 'ปีที่ประเมินไม่ถูกต้อง',
            'assessment_period.required' => 'กรุณาระบุงวดการประเมิน',
            'assessment_period.integer' => 'งวดการประเมินต้องเป็นตัวเลข',
            'assessment_period.in' => 'งวดการประเมินต้องเป็น 1 หรือ 2',
            'likelihood_level.required' => 'กรุณาระบุระดับโอกาสเกิด',
            'likelihood_level.integer' => 'ระดับโอกาสเกิดต้องเป็นตัวเลข',
            'likelihood_level.min' => 'ระดับโอกาสเกิดต้องมีค่าอย่างน้อย 1',
            'likelihood_level.max' => 'ระดับโอกาสเกิดต้องมีค่าไม่เกิน 4',
            'impact_level.required' => 'กรุณาระบุระดับผลกระทบ',
            'impact_level.integer' => 'ระดับผลกระทบต้องเป็นตัวเลข',
            'impact_level.min' => 'ระดับผลกระทบต้องมีค่าอย่างน้อย 1',
            'impact_level.max' => 'ระดับผลกระทบต้องมีค่าไม่เกิน 4',
            'division_risk_id.required' => 'กรุณาเลือกความเสี่ยงระดับส่วนงาน',
            'division_risk_id.exists' => 'ความเสี่ยงระดับส่วนงานที่เลือกไม่มีในระบบ',
            'notes.max' => 'บันทึกเพิ่มเติมมีความยาวได้ไม่เกิน 1000 ตัวอักษร'
        ];
    }
}
