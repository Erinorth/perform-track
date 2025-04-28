<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationalRiskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'risk_name' => 'required|string|max:255',
            'description' => 'required|string',
            'year' => 'required|integer|min:2000|max:2100',
        ];
    }

    public function messages()
    {
        return [
            'risk_name.required' => 'กรุณาระบุชื่อความเสี่ยง',
            'risk_name.max' => 'ชื่อความเสี่ยงต้องมีความยาวไม่เกิน 255 ตัวอักษร',
            'description.required' => 'กรุณาระบุรายละเอียดความเสี่ยง',
            'year.required' => 'กรุณาระบุปี',
            'year.integer' => 'ปีต้องเป็นตัวเลขเท่านั้น',
            'year.min' => 'ปีต้องมากกว่าหรือเท่ากับ 2000',
            'year.max' => 'ปีต้องน้อยกว่าหรือเท่ากับ 2100',
        ];
    }
}
