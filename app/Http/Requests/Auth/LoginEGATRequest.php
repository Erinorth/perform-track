<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginEGATRequest extends FormRequest
{
    /**
     * กำหนดสิทธิ์ในการทำ request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * กำหนดกฎการ validation
     */
    public function rules(): array
    {
        return [
            'egatid' => ['required', 'string', 'min:1', 'max:20'], // เปลี่ยนจาก numeric เป็น string
            'password' => ['required', 'string', 'min:1'], // เปลี่ยน min จาก 6 เป็น 1
        ];
    }

    /**
     * กำหนดข้อความ error แบบกำหนดเอง
     */
    public function messages(): array
    {
        return [
            'egatid.required' => 'กรุณากรอก EGAT ID',
            'egatid.string' => 'EGAT ID ต้องเป็นข้อความ',
            'egatid.min' => 'EGAT ID ต้องมีอย่างน้อย 1 ตัวอักษร',
            'egatid.max' => 'EGAT ID ต้องไม่เกิน 20 ตัวอักษร',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.string' => 'รหัสผ่านต้องเป็นข้อความ',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 1 ตัวอักษร',
        ];
    }

    /**
     * ตรวจสอบว่าไม่ถูก rate limit
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'egatid' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * บันทึกความพยายามล็อกอินที่ผิด
     */
    public function recordRateLimitAttempt(): void
    {
        RateLimiter::hit($this->throttleKey());
    }

    /**
     * ล้างการนับ rate limiting
     */
    public function clearRateLimiting(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * สร้าง key สำหรับ rate limiting
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('egatid')).'|'.$this->ip());
    }
}
