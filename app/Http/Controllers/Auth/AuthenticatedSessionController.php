<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;
use nusoap_client;
use App\Models\User;
use App\Models\CenSus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function createEGAT(Request $request): Response
    {
        return Inertia::render('auth/LoginEGAT', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function storeEGAT(Request $request): RedirectResponse
    {
        $username = $request->input('egatid');
        $password = $request->input('password');

        // สร้างคีย์สำหรับ Rate Limiter โดยใช้ username + IP
        $throttleKey = Str::transliterate(Str::lower($username).'|'.$request->ip());

        // ตรวจสอบว่ามีการพยายามเกินจำนวนครั้งที่กำหนดหรือไม่
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'egatid' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        Log::info('เริ่มกระบวนการล็อกอิน EGAT', ['username' => $username]);

        Log::debug('กำลังเชื่อมต่อ SOAP Web Service');

        $client = new nusoap_client("http://webservices.egat.co.th/authentication/au_provi.php?wsdl", true);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;

        $result = $client->call("validate_user", [
            "a" => $username,
            "b" => $password
        ]);

        Log::debug('ผลลัพธ์จาก SOAP', ['response' => $result]);

        // ตรวจสอบค่าตรงๆ เป็น boolean
        if ($result) {
            Log::info('ตรวจสอบผู้ใช้ในฐานข้อมูล', ['email' => $username.'@egat.co.th']);
            
            // ล้าง Rate Limiter เมื่อล็อกอินสำเร็จ
            RateLimiter::clear($throttleKey);
            
            // ดึงข้อมูลจาก Census ตาม EGAT ID
            $census = CenSus::where('EMPN', $username)->first();
            
            Log::info('ข้อมูล Census', [
                'username' => $username,
                'census_found' => $census ? 'true' : 'false',
                'department' => $census?->pnang,
                'position' => $census?->a_position
            ]);

            // ตรวจสอบว่ามีผู้ใช้อยู่แล้วหรือไม่
            $existingUser = User::where('email', $username . '@egat.co.th')->first();
            
            if ($existingUser) {
                Log::info('อัปเดตข้อมูลผู้ใช้ที่มีอยู่', ['user_id' => $existingUser->id]);
                
                // อัปเดตข้อมูลตำแหน่งให้เป็นปัจจุบัน
                $updateData = [
                    'egat_id' => $username,
                    'email_verified_at' => now(),
                ];
                
                // อัปเดตข้อมูลจาก Census หากมีข้อมูล
                if ($census) {
                    $updateData['company'] = 'EGAT';
                    $updateData['department'] = $census->pnang;
                    $updateData['position'] = $census->a_position;
                    
                    Log::info('อัปเดตข้อมูลจาก Census', [
                        'department' => $census->pnang,
                        'position' => $census->a_position
                    ]);
                }
                
                $existingUser->update($updateData);
                $user = $existingUser;
                
            } else {
                Log::info('สร้างผู้ใช้ใหม่');
                
                // สร้างผู้ใช้ใหม่
                $userData = [
                    'egat_id' => $username,
                    'name' => $username,
                    'email' => $username . '@egat.co.th',
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'company' => 'EGAT',
                    'department' => $census?->pnang ?? null,
                    'position' => $census?->a_position ?? null,
                ];
                
                $user = User::create($userData);
                
                Log::info('สร้างผู้ใช้ใหม่สำเร็จ', [
                    'user_id' => $user->id,
                    'department' => $user->department,
                    'position' => $user->position
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();

            Log::info('ล็อกอินสำเร็จ', [
                'user_id' => $user->id,
                'current_department' => $user->department,
                'current_position' => $user->position
            ]);
            
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // เพิ่มจำนวนครั้งที่ล้มเหลวใน Rate Limiter
        RateLimiter::hit($throttleKey);

        Log::warning('ข้อมูลล็อกอินไม่ถูกต้อง', ['username' => $username]);
        throw ValidationException::withMessages([
            'egatid' => trans('auth.failed'),
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
