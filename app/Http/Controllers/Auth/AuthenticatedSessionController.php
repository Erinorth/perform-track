<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LoginEGATRequest;  
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
use Illuminate\Support\Facades\DB;

class AuthenticatedSessionController extends Controller
{
    /**
     * แสดงหน้าล็อกอินปกติ
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * แสดงหน้าล็อกอิน EGAT
     */
    public function createEGAT(Request $request): Response
    {
        Log::info('แสดงหน้าล็อกอิน EGAT');
        
        return Inertia::render('auth/LoginEGAT', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * ประมวลผลล็อกอินปกติ
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        Log::info('เริ่มกระบวนการล็อกอินปกติ');
        
        // ตรวจสอบข้อมูลล็อกอิน
        $request->authenticate();
        
        // สร้าง session ใหม่
        $request->session()->regenerate();

        Log::info('ล็อกอินปกติสำเร็จ');
        
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * ประมวลผลล็อกอิน EGAT
     */
    public function storeEGAT(LoginEGATRequest $request): RedirectResponse
    {
        // ตรวจสอบ rate limiting
        $request->ensureIsNotRateLimited();

        $username = $request->input('egatid');
        $password = $request->input('password');

        Log::info('เริ่มกระบวนการล็อกอิน EGAT', [
            'username' => $username,
            'ip' => $request->ip()
        ]);

        try {
            Log::debug('กำลังเชื่อมต่อ SOAP Web Service');

            // ใช้ Mock SOAP Client ในขณะทดสอบ
            if (app()->bound('nusoap_client')) {
                // ใช้ Mock Client ที่ถูก bind ไว้
                $client = app('nusoap_client');
            } else {
                // ใช้ SOAP Client จริง
                $client = new nusoap_client("http://webservices.egat.co.th/authentication/au_provi.php?wsdl", true);
                $client->soap_defencoding = 'UTF-8';
                $client->decode_utf8 = false;
            }

            // เรียกใช้งาน SOAP Web Service
            $result = $client->call("validate_user", [
                "a" => $username,
                "b" => $password
            ]);

            Log::debug('ผลลัพธ์จาก SOAP Web Service', [
                'username' => $username,
                'result' => $result ? 'success' : 'failed'
            ]);

            // ถ้าตรวจสอบผ่าน
            if ($result) {
                Log::info('ตรวจสอบข้อมูลผู้ใช้ในระบบ', [
                    'email' => $username.'@egat.co.th'
                ]);
                
                // ค้นหาข้อมูลใน Census
                $connection = app()->environment('testing') ? 'testing_mmddata' : 'mmddata';
                $census = CenSus::on($connection)->where('EMPN', $username)->first();
                
                Log::info('ผลการค้นหาข้อมูล Census', [
                    'username' => $username,
                    'census_found' => $census ? 'true' : 'false',
                    'department' => $census?->pnang,
                    'section' => $census?->gong,
                    'position' => $census?->a_position,
                    'connection' => $connection
                ]);

                // ตรวจสอบว่ามีผู้ใช้อยู่แล้วหรือไม่
                $existingUser = User::where('email', $username . '@egat.co.th')->first();
                
                if ($existingUser) {
                    Log::info('พบผู้ใช้ที่มีอยู่แล้ว - อัปเดตข้อมูล', [
                        'user_id' => $existingUser->id,
                        'email_verified_at_before' => $existingUser->email_verified_at?->toDateTimeString()
                    ]);
                    
                    // เตรียมข้อมูลสำหรับอัปเดต
                    $updateData = [
                        'egat_id' => $username, // แปลงเป็น string
                        'email_verified_at' => now(), // อัปเดต email_verified_at
                    ];
                    
                    // เพิ่มข้อมูลจาก Census ถ้ามี
                    if ($census) {
                        $updateData['company'] = 'EGAT';
                        $updateData['department'] = $census->pnang;
                        $updateData['position'] = $census->a_position;
                        
                        Log::info('อัปเดตข้อมูลจาก Census', [
                            'department' => $census->pnang,
                            'section' => $census->gong,
                            'position' => $census->a_position
                        ]);
                    }
                    
                    // อัปเดตข้อมูลใน database
                    DB::transaction(function () use ($existingUser, $updateData) {
                        $existingUser->update($updateData);
                    });
                    
                    // รีเฟรชข้อมูล
                    $existingUser->refresh();
                    $user = $existingUser;
                    
                    Log::info('อัปเดตข้อมูลผู้ใช้สำเร็จ', [
                        'user_id' => $user->id,
                        'email_verified_at_after' => $user->email_verified_at?->toDateTimeString(),
                        'department_updated' => $user->department,
                        'position_updated' => $user->position
                    ]);
                    
                } else {
                    Log::info('สร้างผู้ใช้ใหม่');
                    
                    // เตรียมข้อมูลสำหรับสร้างผู้ใช้ใหม่
                    $userData = [
                        'egat_id' => $username, // เก็บเป็น string
                        'name' => $username,
                        'email' => $username . '@egat.co.th',
                        'password' => Hash::make($password),
                        'email_verified_at' => now(),
                        'company' => 'EGAT',
                        'department' => $census?->pnang ?? null,
                        'position' => $census?->a_position ?? null,
                    ];
                    
                    // สร้างผู้ใช้ใหม่
                    $user = User::create($userData);
                    
                    Log::info('สร้างผู้ใช้ใหม่สำเร็จ', [
                        'user_id' => $user->id,
                        'department' => $user->department,
                        'position' => $user->position,
                        'email_verified_at' => $user->email_verified_at?->toDateTimeString()
                    ]);
                }

                // ล็อกอินผู้ใช้
                Auth::login($user);
                $request->session()->regenerate();
                
                // ล้าง rate limiting
                $request->clearRateLimiting();

                Log::info('ล็อกอิน EGAT สำเร็จ', [
                    'user_id' => $user->id,
                    'username' => $username,
                    'current_department' => $user->department,
                    'current_position' => $user->position,
                    'email_verified_at' => $user->email_verified_at?->toDateTimeString()
                ]);
                
                return redirect()->intended(route('dashboard', absolute: false));
            } else {
                // บันทึกความพยายามล็อกอินที่ผิด
                $request->recordRateLimitAttempt();
                
                Log::warning('ข้อมูลล็อกอินไม่ถูกต้อง', [
                    'username' => $username,
                    'ip' => $request->ip()
                ]);
            }

        } catch (\Exception $e) {
            // บันทึกความพยายามล็อกอินที่ผิด
            $request->recordRateLimitAttempt();
            
            Log::error('ข้อผิดพลาดในการเชื่อมต่อ SOAP Web Service', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw ValidationException::withMessages([
                'egatid' => 'ไม่สามารถเชื่อมต่อกับระบบตรวจสอบได้ กรุณาลองใหม่อีกครั้ง',
            ]);
        }

        // ถ้าล็อกอินไม่สำเร็จ
        throw ValidationException::withMessages([
            'egatid' => trans('auth.failed'),
        ]);
    }

    /**
     * ประมวลผลล็อกเอาท์
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        
        Log::info('เริ่มกระบวนการล็อกเอาท์', [
            'user_id' => $userId
        ]);
        
        // ล็อกเอาท์ผู้ใช้
        Auth::guard('web')->logout();

        // ทำลาย session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('ล็อกเอาท์สำเร็จ', [
            'user_id' => $userId
        ]);

        return redirect('/');
    }

    /**
     * ตรวจสอบการเชื่อมต่อ SOAP (สำหรับการทดสอบ)
     */
    public function checkSoapConnection(): array
    {
        try {
            $client = new nusoap_client("http://webservices.egat.co.th/authentication/au_provi.php?wsdl", true);
            
            return [
                'status' => 'success',
                'message' => 'SOAP connection established'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
