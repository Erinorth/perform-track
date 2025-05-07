<?php
/**
 * ไฟล์: app\Http\Requests\UpdateOrganizationalRiskRequest.php
 * คำอธิบาย: Form Request สำหรับตรวจสอบข้อมูลในการอัปเดตความเสี่ยงระดับองค์กร
 * 
 * คลาสนี้รับผิดชอบในการ:
 * - ตรวจสอบความถูกต้องของข้อมูลที่ส่งมาจากฟอร์มแก้ไขความเสี่ยง
 * - กำหนดกฎการตรวจสอบฟิลด์ต่างๆ ทั้งข้อมูลพื้นฐานและเอกสารแนบ
 * - ให้ข้อความแจ้งเตือนเป็นภาษาไทยที่เป็นมิตรกับผู้ใช้
 * - บันทึกข้อมูลการตรวจสอบที่ล้มเหลวเพื่อการแก้ไขปัญหา
 * 
 * ทำงานร่วมกับ: OrganizationalRiskController (ใช้ในเมธอด update)
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UpdateOrganizationalRiskRequest extends FormRequest
{
    /**
     * ตรวจสอบสิทธิ์การใช้งาน
     * 
     * ฟังก์ชันนี้ตรวจสอบว่าผู้ใช้งานมีสิทธิ์ในการอัปเดตข้อมูลความเสี่ยงหรือไม่
     * ถ้าต้องการใช้ระบบตรวจสอบสิทธิ์ (Policy) ให้แก้ไขเป็น:
     * return auth()->user()->can('update', $this->route('organizationalRisk'));
     * 
     * ในรุ่นนี้ยังอนุญาตให้ทุกคนสามารถแก้ไขข้อมูลได้ หากต้องการจำกัดสิทธิ์
     * ในอนาคต สามารถสร้าง Policy ใหม่และแก้ไขฟังก์ชันนี้
     * 
     * @return bool ค่า true ถ้าผู้ใช้มีสิทธิ์, false ถ้าไม่มีสิทธิ์
     */
    public function authorize(): bool
    {
        // สำหรับตอนนี้ อนุญาตให้ทุกคนที่เข้าถึงได้สามารถแก้ไขข้อมูลได้
        // เพื่อความปลอดภัยมากขึ้น ควรแก้ไขเพื่อใช้ระบบสิทธิ์ในอนาคต
        return true;
    }

    /**
     * กำหนดกฎการตรวจสอบข้อมูล
     * 
     * กำหนดเงื่อนไขการตรวจสอบสำหรับแต่ละฟิลด์:
     * - risk_name: ชื่อความเสี่ยงต้องไม่เป็นค่าว่างและมีความยาวไม่เกิน 255 ตัวอักษร
     * - description: รายละเอียดความเสี่ยงต้องไม่เป็นค่าว่าง
     * - attachments: ไฟล์แนบเป็นทางเลือก (ไม่บังคับ) แต่ถ้ามีต้องเป็น array 
     * - attachments.*: แต่ละไฟล์ต้องเป็นประเภท PDF, Word, Excel หรือรูปภาพ และขนาดไม่เกิน 10MB
     *   (จำกัดขนาดเพื่อป้องกันการใช้ทรัพยากรเซิร์ฟเวอร์มากเกินไปและลดเวลาในการอัปโหลด)
     * - attachments_to_delete: รายการ ID ของเอกสารแนบที่ต้องการลบ (เป็นทางเลือก)
     * - attachments_to_delete.*: แต่ละ ID ต้องเป็นตัวเลขและมีอยู่ในตาราง organizational_risk_attachments
     * 
     * @return array กฎการตรวจสอบสำหรับแต่ละฟิลด์
     */
    public function rules()
    {
        return [
            'risk_name' => 'required|string|max:255',
            'description' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240', // 10MB
            'attachments_to_delete' => 'nullable|array',
            'attachments_to_delete.*' => 'integer|exists:organizational_risk_attachments,id',
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่เป็นมิตรกับผู้ใช้
     * 
     * กำหนดข้อความแจ้งเตือนเป็นภาษาไทยเพื่อให้ผู้ใช้เข้าใจง่าย
     * โดยระบุข้อความเฉพาะสำหรับแต่ละกฎการตรวจสอบที่ล้มเหลว
     * 
     * ข้อความเหล่านี้จะปรากฏในฟอร์มเมื่อผู้ใช้กรอกข้อมูลไม่ถูกต้อง
     * เช่น เมื่อไม่ได้ระบุชื่อความเสี่ยง หรืออัปโหลดไฟล์ที่ไม่รองรับ
     * 
     * @return array ข้อความแจ้งเตือนสำหรับแต่ละกฎการตรวจสอบ
     */
    public function messages()
    {
        return [
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'risk_name.max' => 'ชื่อความเสี่ยงต้องมีความยาวไม่เกิน 255 ตัวอักษร',
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            'attachments.*.file' => 'ไฟล์แนบไม่ถูกต้อง',
            'attachments.*.mimes' => 'รองรับเฉพาะไฟล์ PDF, Word, Excel และรูปภาพเท่านั้น',
            'attachments.*.max' => 'ขนาดไฟล์ต้องไม่เกิน 10MB',
            'attachments_to_delete.*.exists' => 'ไม่พบไฟล์ที่ต้องการลบ',
        ];
    }
    
    /**
     * ดำเนินการเมื่อการตรวจสอบล้มเหลว
     * 
     * เมื่อข้อมูลที่ส่งมาไม่ผ่านการตรวจสอบ ฟังก์ชันนี้จะบันทึกข้อมูลเพิ่มเติมลงใน log
     * เพื่อใช้ในการวิเคราะห์ปัญหาและติดตามการใช้งานที่ไม่ถูกต้อง
     * 
     * ข้อมูลที่บันทึกประกอบด้วย:
     * - ID และชื่อของความเสี่ยงที่กำลังอัปเดต
     * - รายการข้อผิดพลาดทั้งหมดที่ตรวจพบ
     * - ข้อมูลที่ผู้ใช้ส่งมา (ยกเว้นข้อมูลที่ละเอียดอ่อน)
     * - ชื่อผู้ใช้และ IP address สำหรับการตรวจสอบย้อนกลับ
     * 
     * @param \Illuminate\Contracts\Validation\Validator $validator ตัวตรวจสอบที่มีข้อผิดพลาด
     * @return void
     * @throws \Illuminate\Validation\ValidationException ส่งต่อข้อผิดพลาดไปยัง Laravel
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
            'input' => $this->except(['_token', '_method']), // ยกเว้นข้อมูลที่ละเอียดอ่อน
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'ip' => $this->ip(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // เรียกใช้ parent method เพื่อส่งข้อผิดพลาดไปยัง Laravel ตามปกติ
        parent::failedValidation($validator);
    }
}
