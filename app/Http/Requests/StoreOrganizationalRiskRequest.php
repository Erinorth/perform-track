<?php
/**
 * ไฟล์: app\Http\Requests\StoreOrganizationalRiskRequest.php
 * คลาสสำหรับตรวจสอบข้อมูลคำขอในการเพิ่มความเสี่ยงระดับองค์กรใหม่
 * ใช้เพื่อแยกกฎการตรวจสอบข้อมูล (validation rules) ออกจาก Controller
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;  // นำเข้า Log facade สำหรับบันทึก log
use Illuminate\Support\Facades\Auth;

class StoreOrganizationalRiskRequest extends FormRequest
{
    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ที่จะทำคำขอนี้หรือไม่
     * 
     * @return bool true หากผู้ใช้มีสิทธิ์, false หากไม่มีสิทธิ์
     */
    public function authorize(): bool
    {
        // ควรแก้ไขเป็น true หรือใส่ตรรกะการตรวจสอบสิทธิ์ที่เหมาะสม
        // เช่น ตรวจสอบว่าผู้ใช้เป็นแอดมินหรือไม่
        // return auth()->user()->hasRole('admin');
        return true; // อนุญาตให้ทุกคนสามารถส่งคำขอนี้ได้
    }

    /**
     * กำหนดกฎการตรวจสอบความถูกต้องของข้อมูลที่ใช้กับคำขอนี้
     * 
     * @return array กฎการตรวจสอบสำหรับแต่ละฟิลด์
     */
    public function rules(): array
    {
        return [
            'risk_name' => 'required|string|max:255',  // ชื่อความเสี่ยง: จำเป็น, เป็นข้อความ, ความยาวไม่เกิน 255 ตัวอักษร
            'description' => 'required|string',        // รายละเอียดความเสี่ยง: จำเป็น, เป็นข้อความ
            'year' => 'required|integer|min:2000|max:2100', // ปี: จำเป็น, เป็นตัวเลขจำนวนเต็ม, อยู่ในช่วง 2000-2100
        ];
    }

    /**
     * กำหนดข้อความแจ้งเตือนที่กำหนดเองเมื่อการตรวจสอบล้มเหลว
     * ช่วยให้ข้อความที่แสดงต่อผู้ใช้เป็นภาษาไทยที่อ่านเข้าใจง่าย
     * 
     * @return array ข้อความแจ้งเตือนสำหรับแต่ละกฎการตรวจสอบ
     */
    public function messages(): array
    {
        return [
            // ข้อความสำหรับฟิลด์ risk_name
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'risk_name.max' => 'ชื่อความเสี่ยงต้องมีความยาวไม่เกิน 255 ตัวอักษร',
            
            // ข้อความสำหรับฟิลด์ description
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            
            // ข้อความสำหรับฟิลด์ year
            'year.required' => 'กรุณาระบุปี',
            'year.integer' => 'ปีต้องเป็นตัวเลขเท่านั้น',
            'year.min' => 'ปีต้องมากกว่าหรือเท่ากับ 2000',
            'year.max' => 'ปีต้องน้อยกว่าหรือเท่ากับ 2100',
        ];
    }
    
    /**
     * บันทึกข้อมูลเพิ่มเติมลง log เมื่อการตรวจสอบเกิดข้อผิดพลาด
     * ช่วยในการติดตามและแก้ไขปัญหาการกรอกข้อมูลของผู้ใช้
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // บันทึก log เมื่อการตรวจสอบล้มเหลว
        Log::info('การตรวจสอบข้อมูลความเสี่ยงระดับองค์กรล้มเหลว', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->input(), // ข้อมูลที่ผู้ใช้ส่งมา
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'ip' => $this->ip()
        ]);
        
        // เรียกใช้ parent method ตามปกติเพื่อให้การทำงานอื่นๆ ดำเนินต่อไป
        parent::failedValidation($validator);
    }
}
