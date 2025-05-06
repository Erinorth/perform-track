<?php
/**
 * ไฟล์: app\Http\Requests\UpdateOrganizationalRiskRequest.php
 * คลาสสำหรับตรวจสอบข้อมูลคำขอในการอัปเดตความเสี่ยงระดับองค์กร
 * ใช้เพื่อแยกกฎการตรวจสอบข้อมูล (validation rules) ออกจาก Controller
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;  // นำเข้า Log facade สำหรับบันทึก log
use Illuminate\Support\Facades\Auth;

class UpdateOrganizationalRiskRequest extends FormRequest
{
    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ที่จะทำคำขออัปเดตข้อมูลหรือไม่
     * 
     * @return bool true หากผู้ใช้มีสิทธิ์, false หากไม่มีสิทธิ์
     */
    public function authorize(): bool
    {
        // ควรแก้ไขเป็น true หรือใส่ตรรกะการตรวจสอบสิทธิ์ที่เหมาะสม
        // เช่น ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการแก้ไขข้อมูลนี้หรือไม่
        // return auth()->user()->can('update', $this->route('organizationalRisk'));
        return true; // อนุญาตให้ทุกคนสามารถอัปเดตข้อมูลได้
    }

    /**
     * กำหนดกฎการตรวจสอบความถูกต้องของข้อมูลสำหรับการอัปเดต
     * 
     * @return array กฎการตรวจสอบสำหรับแต่ละฟิลด์
     */
    public function rules()
    {
        return [
            'risk_name' => 'required|string|max:255',
            'description' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'attachments_to_delete' => 'nullable|array',
            'attachments_to_delete.*' => 'integer|exists:organizational_risk_attachments,id',
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่กำหนดเองเมื่อการตรวจสอบล้มเหลว
     * แสดงข้อความภาษาไทยที่เข้าใจง่ายสำหรับผู้ใช้
     * 
     * @return array ข้อความแจ้งเตือนสำหรับแต่ละกฎการตรวจสอบ
     */
    public function messages()
    {
        return [
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            'attachments.*.file' => 'ไฟล์แนบไม่ถูกต้อง',
            'attachments.*.mimes' => 'รองรับเฉพาะไฟล์ PDF, Word, Excel และรูปภาพเท่านั้น',
            'attachments.*.max' => 'ขนาดไฟล์ต้องไม่เกิน 10MB',
            'attachments_to_delete.*.exists' => 'ไม่พบไฟล์ที่ต้องการลบ',
        ];
    }
    
    /**
     * ดำเนินการเมื่อการตรวจสอบล้มเหลว
     * บันทึกข้อมูลเพิ่มเติมลง log สำหรับการติดตามและแก้ไขปัญหา
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // ดึงข้อมูลความเสี่ยงที่กำลังอัปเดต
        $risk = $this->route('organizationalRisk');
        
        // บันทึก log เมื่อการตรวจสอบล้มเหลว
        Log::info('การตรวจสอบข้อมูลการอัปเดตความเสี่ยงระดับองค์กรล้มเหลว', [
            'risk_id' => $risk ? $risk->id : null,
            'risk_name' => $risk ? $risk->risk_name : null,
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['_token', '_method']), // ข้อมูลที่ผู้ใช้ส่งมา (ยกเว้นข้อมูล token)
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'ip' => $this->ip()
        ]);
        
        // เรียกใช้ parent method ตามปกติ
        parent::failedValidation($validator);
    }
    
    /**
     * เตรียมข้อมูลสำหรับการตรวจสอบ
     * ทำความสะอาดหรือปรับแต่งข้อมูลก่อนนำไปตรวจสอบ
     */
    protected function prepareForValidation()
    {
        // ถ้าไม่มีการส่งค่า active มา ให้กำหนดเป็น true โดยค่าเริ่มต้น
        if (!$this->has('active') && $this->isMethod('PUT')) {
            $this->merge(['active' => true]);
        }
    }
}
