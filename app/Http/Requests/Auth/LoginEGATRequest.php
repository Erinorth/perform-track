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
     * กำหนดว่าผู้ใช้ได้รับอนุญาตให้ทำการร้องขอนี้หรือไม่
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * กำหนดกฎการตรวจสอบที่ใช้กับการร้องขอ
     */
    public function rules(): array
    {
        return [
            'egatid' => ['required', 'numeric', 'digits:6'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * กำหนดข้อความแสดงข้อผิดพลาดแบบกำหนดเอง
     */
    public function messages(): array
    {
        return [
            'egatid.required' => 'กรุณากรอก EGAT ID',
            'egatid.numeric' => 'EGAT ID ต้องเป็นตัวเลข',
            'egatid.digits' => 'EGAT ID ต้องเป็นตัวเลข 6 ตัว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.string' => 'รหัสผ่านต้องเป็นข้อความ',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร',
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
     * ล้าง rate limiting
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
