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
use Illuminate\Support\Facades\Hash;

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

        Log::info('เริ่มกระบวนการล็อกอิน EGAT', ['username' => $username]);

        try {
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
                $user = User::firstOrCreate(
                    ['email' => $username . '@egat.co.th'],
                    [
                        'name' => $username,
                        'password' => Hash::make('egatwebservice')
                    ]
                );

                if ($census = $user->census()->first()) {
                    Log::debug('ตรวจสอบบทบาทผู้ใช้', [
                        'user_id' => $user->id,
                        'current_roles' => $user->getRoleNames()
                    ]);

                    $position = $census->a_position;
                    $currentRoles = $user->getRoleNames();
                
                    Log::info('Current roles before update', ['user_id' => $user->id, 'roles' => $currentRoles]);
                
                    foreach ($currentRoles as $role) {
                        if ($role !== 'admin') {
                            $user->removeRole($role);
                            Log::info('Role removed', ['user_id' => $user->id, 'role' => $role]);
                        }
                    }
                
                    switch ($position) {
                        case 'ห':
                            $user->assignRole('head');
                            $user->assignRole('egat');
                            Log::info('Role assigned', ['user_id' => $user->id, 'role' => 'head']);
                            break;
                        case 'ก':
                            $user->assignRole('chift');
                            $user->assignRole('egat');
                            Log::info('Role assigned', ['user_id' => $user->id, 'role' => 'chift']);
                            break;
                        case 'อ':
                            $user->assignRole('director');
                            $user->assignRole('egat');
                            Log::info('Role assigned', ['user_id' => $user->id, 'role' => 'director']);
                            break;
                        default:
                            Log::info('No role assigned', ['user_id' => $user->id, 'position' => $position]);
                            break;
                    }
                
                    $updatedRoles = $user->getRoleNames();

                    Log::info('อัปเดตบทบาทผู้ใช้สำเร็จ', [
                        'user_id' => $user->id,
                        'new_roles' => $user->getRoleNames()
                    ]);
                }

                Auth::login($user);
                $request->session()->regenerate();

                Log::info('ล็อกอินสำเร็จ', ['user_id' => $user->id]);
                return redirect()->intended(route('dashboard', absolute: false));
            }

            Log::warning('ข้อมูลล็อกอินไม่ถูกต้อง', ['username' => $username]);
            return back()->withErrors(['username' => 'Invalid credentials']);

        } catch (\Exception $e) {
            Log::error('ข้อผิดพลาดในการล็อกอิน EGAT', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return back()->withErrors(['error' => 'Authentication service error']);
        }
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
