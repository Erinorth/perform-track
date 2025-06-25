<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /* public function __construct()
    {
        $this->middleware('auth'); // Middleware สำหรับตรวจสอบการเข้าสู่ระบบ
    } */

    public function permission(Request $request): Response
    {
        $users = User::with('permissions')->get();

        $pivotData = $users->map(function ($user) {
            $permissions = ['view', 'create', 'update', 'delete'];
            $result = ['id' => $user->id, 'egat_id' => $user->egat_id, 'name' => $user->name];
        
            foreach ($permissions as $permission) {
                $permissionName = "data.".$permission;
                $result[$permission] = $user->permissions->contains('name', $permissionName) ? true : false;
            }
        
            return $result;
        });

        return Inertia::render('settings/permission/Permission', [
            'users' => $pivotData, // ส่งข้อมูล users ไปยัง Vue Component
        ]);
    }

    public function update(Request $request)
    {
        Log::info('เริ่มต้นการอัปเดตสิทธิ์', [
            'request_data' => $request->all()
        ]);
        
        try {
            // ตรวจสอบว่ามี id ในคำขอหรือไม่
            if (!$request->has('id')) {
                Log::error('ไม่พบ id ในคำขอ', ['request' => $request->all()]);
                return response()->json(['success' => false, 'message' => 'User ID is required'], 422);
            }
            
            $userId = $request->id;
            Log::info('ค้นหาผู้ใช้', ['user_id' => $userId]);
            
            // หาผู้ใช้
            $user = User::findOrFail($userId);
            Log::info('พบผู้ใช้', [
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            
            // วนลูปตามคีย์ในคำขอ
            foreach ($request->all() as $key => $value) {
                // ข้ามคีย์ id และคีย์อื่นๆ ที่ไม่ใช่ permission
                if ($key === 'id' || !in_array($key, ['view', 'create', 'update', 'delete'])) {
                    continue;
                }
                
                // สร้างชื่อสิทธิ์
                $permissionName = "data.{$key}";
                Log::info('กำลังประมวลผลสิทธิ์', [
                    'permission' => $key,
                    'full_name' => $permissionName,
                    'value' => $value
                ]);
                
                // ตรวจสอบว่ามี permission ในระบบหรือไม่
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                Log::info('ดึงหรือสร้างสิทธิ์สำเร็จ', [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name
                ]);
                
                // เพิ่มหรือลบสิทธิ์ตามค่า Boolean
                if ($value === true) {
                    Log::info('กำลังให้สิทธิ์แก่ผู้ใช้', [
                        'user_id' => $user->id,
                        'permission_name' => $permissionName
                    ]);
                    $user->givePermissionTo($permission);
                    Log::info('ให้สิทธิ์แก่ผู้ใช้สำเร็จ');
                } else {
                    Log::info('กำลังถอนสิทธิ์จากผู้ใช้', [
                        'user_id' => $user->id,
                        'permission_name' => $permissionName
                    ]);
                    $user->revokePermissionTo($permission);
                    Log::info('ถอนสิทธิ์จากผู้ใช้สำเร็จ');
                }
            }
            
            Log::info('การอัปเดตสิทธิ์เสร็จสมบูรณ์', [
                'user_id' => $user->id
            ]);
            
            return Redirect::back()->with('success', true);
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดระหว่างการอัปเดตสิทธิ์', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}