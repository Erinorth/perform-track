<?php
/**
 * ไฟล์: app\Http\Requests\UpdateDivisionRiskRequest.php
 * FormRequest สำหรับตรวจสอบข้อมูลการอัปเดตความเสี่ยงระดับฝ่าย
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UpdateDivisionRiskRequest extends FormRequest
{
    /**
     * กำหนดว่าผู้ใช้มีสิทธิ์ในการดำเนินการตาม request นี้หรือไม่
     *
     * @return bool
     */
    public function authorize()
    {
        // ใช้ Policy ในการตรวจสอบสิทธิ์หรือตรวจสอบตามตำแหน่งหน้าที่ของผู้ใช้
        return true; // สามารถปรับเปลี่ยนตามความเหมาะสม เช่น ใช้ Gate หรือ Policy
    }

    /**
     * กำหนดกฎสำหรับการตรวจสอบข้อมูล
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'risk_name' => 'required|string|max:255',
            'description' => 'required|string',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 10),
            'organizational_risk_id' => 'nullable|exists:organizational_risks,id'
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนสำหรับกฎการตรวจสอบแต่ละรายการ
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'risk_name.max' => 'ชื่อความเสี่ยงต้องมีความยาวไม่เกิน 255 ตัวอักษร',
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            'year.required' => 'กรุณาระบุปีของความเสี่ยง',
            'year.integer' => 'ปีต้องเป็นตัวเลขเท่านั้น',
            'year.min' => 'ปีต้องมีค่าอย่างน้อย 2000',
            'year.max' => 'ปีต้องมีค่าไม่เกิน ' . (date('Y') + 10),
            'organizational_risk_id.exists' => 'ความเสี่ยงระดับองค์กรที่เลือกไม่ถูกต้อง'
        ];
    }

    /**
     * บันทึก log เมื่อ validation ล้มเหลว
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning('การตรวจสอบข้อมูลสำหรับการอัปเดตความเสี่ยงระดับฝ่ายล้มเหลว', [
            'errors' => $validator->errors()->toArray(),
            'inputs' => $this->except(['_token']),
            'id' => $this->route('divisionRisk')->id ?? 'ไม่ระบุ',
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        parent::failedValidation($validator);
    }
}
