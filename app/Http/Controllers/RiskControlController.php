<?php
/**
 * ไฟล์: app\Http\Controllers\RiskControlController.php
 * คำอธิบาย: Controller สำหรับจัดการการควบคุมความเสี่ยงในระบบประเมินความเสี่ยง
 * เทคโนโลยี: Laravel 12, Inertia.js, Vue 3
 * ทำหน้าที่: จัดการข้อมูลการควบคุมความเสี่ยงและเอกสารแนบที่เกี่ยวข้อง
 * ความสัมพันธ์: เชื่อมโยงกับ RiskControl Model และส่งข้อมูลไปยัง Vue ผ่าน Inertia
 */

namespace App\Http\Controllers;

// นำเข้า Models และ Requests ที่เกี่ยวข้อง
use App\Models\RiskControl;  // โมเดลสำหรับจัดการข้อมูลการควบคุมความเสี่ยง
use App\Models\DivisionRisk;  // โมเดลสำหรับจัดการข้อมูลความเสี่ยงระดับฝ่าย
use App\Http\Requests\StoreRiskControlRequest;  // Form Request สำหรับตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateRiskControlRequest;  // Form Request สำหรับตรวจสอบข้อมูลการแก้ไข
use Illuminate\Http\Request;  // สำหรับจัดการคำขอจาก HTTP
use Illuminate\Support\Facades\Log;  // สำหรับบันทึก log การทำงาน
use Inertia\Inertia;  // เชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // จัดการข้อมูลผู้ใช้ที่ล็อกอิน
use Illuminate\Support\Facades\DB;

class RiskControlController extends Controller
{
    /**
     * แสดงรายการการควบคุมความเสี่ยงทั้งหมด
     * ดึงข้อมูลพร้อมความสัมพันธ์และเรียงลำดับตามสถานะและประเภทการควบคุม
     * 
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลการควบคุมความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลการควบคุมความเสี่ยงทั้งหมด พร้อมโหลดความสัมพันธ์
        $controls = RiskControl::with([
            'divisionRisk',
            'divisionRisk.organizationalRisk'
        ])
        ->orderBy('status', 'desc') // แสดง active ก่อน
        ->orderBy('control_type')
        ->orderBy('control_name')
        ->get();

        // ดึงข้อมูล division_risks สำหรับใช้ในฟอร์ม
        $divisionRisks = DivisionRisk::with('organizationalRisk')
            ->orderBy('risk_name')
            ->get();

        // สถิติการควบคุมความเสี่ยง
        $statistics = [
            'total_controls' => $controls->count(),
            'active_controls' => $controls->where('status', 'active')->count(),
            'inactive_controls' => $controls->where('status', 'inactive')->count(),
            'by_type' => $controls->groupBy('control_type')->map->count(),
        ];

        // บันทึก log การเข้าถึงหน้ารายการ
        Log::info('เข้าถึงรายการการควบคุมความเสี่ยง', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'total_controls' => $statistics['total_controls']
        ]);

        return Inertia::render('risk_control/RiskControl', [
            'controls' => $controls,
            'divisionRisks' => $divisionRisks,
            'statistics' => $statistics
        ]);
    }

    /**
     * แสดงฟอร์มสำหรับสร้างการควบคุมความเสี่ยงใหม่
     */
    public function create()
    {
        // ดึงข้อมูลความเสี่ยงระดับส่วนงานและองค์กร
        $divisionRisks = DivisionRisk::with('organizationalRisk')->get();
        
        return Inertia::render('risk_control/RiskControlForm', [
            'divisionRisks' => $divisionRisks,
            'controlTypes' => [
                'preventive' => 'การป้องกัน',
                'detective' => 'การตรวจจับ',
                'corrective' => 'การแก้ไข',
                'compensating' => 'การชดเชย'
            ],
            'statusOptions' => [
                'active' => 'ใช้งาน',
                'inactive' => 'ไม่ใช้งาน'
            ]
        ]);
    }

    /**
     * บันทึกข้อมูลการควบคุมความเสี่ยงใหม่ลงฐานข้อมูล
     * 
     * @param \App\Http\Requests\StoreRiskControlRequest $request คำขอที่ผ่านการตรวจสอบแล้ว
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function store(StoreRiskControlRequest $request)
    {
        // เริ่ม transaction เพื่อให้มั่นใจว่าข้อมูลถูกบันทึกครบทุกส่วนหรือไม่ถูกบันทึกเลย
        DB::beginTransaction();
        
        try {
            // สร้างข้อมูลการควบคุมความเสี่ยงใหม่โดยใช้ข้อมูลที่ผ่านการตรวจสอบแล้ว
            $control = RiskControl::create($request->validated());
            
            // บันทึกล็อกสำหรับการติดตามและตรวจสอบ
            Log::info('สร้างการควบคุมความเสี่ยงใหม่', [
                'id' => $control->id,
                'control_name' => $control->control_name,
                'control_type' => $control->control_type,
                'status' => $control->status,
                'user' => auth()->check() ? auth()->user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            // ยืนยันการทำรายการ
            DB::commit();
            
            // ดึงข้อมูลการควบคุมความเสี่ยงทั้งหมดมาใหม่หลังจากบันทึกข้อมูล
            $controls = RiskControl::with(['divisionRisk'])
                ->orderBy('status', 'desc')
                ->orderBy('control_type')
                ->orderBy('control_name')
                ->get();
                
            // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลล่าสุด
            return redirect()->back()
                ->with('success', 'เพิ่มการควบคุมความเสี่ยงเรียบร้อยแล้ว')
                ->with('controls', $controls);
                
        } catch (\Exception $e) {
            // ยกเลิกการทำรายการทั้งหมดหากเกิดข้อผิดพลาด
            DB::rollBack();
            
            Log::error('เกิดข้อผิดพลาดในการสร้างการควบคุมความเสี่ยง', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดของการควบคุมความเสี่ยง
     */
    public function show(RiskControl $riskControl)
    {
        // โหลดข้อมูลที่เกี่ยวข้อง
        $riskControl->load([
            'divisionRisk', 
            'divisionRisk.organizationalRisk'
        ]);
        
        return Inertia::render('risk_control/RiskControlShow', [
            'riskControl' => $riskControl
        ]);
    }

    /**
     * แสดงฟอร์มสำหรับแก้ไขการควบคุมความเสี่ยง
     */
    public function edit(RiskControl $riskControl)
    {
        // โหลดความสัมพันธ์ที่จำเป็น
        $riskControl->load(['divisionRisk', 'divisionRisk.organizationalRisk']);
        
        // ดึงข้อมูลที่จำเป็นสำหรับฟอร์มแก้ไข
        $divisionRisks = DivisionRisk::with('organizationalRisk')->get();
        
        return Inertia::render('risk_control/RiskControlForm', [
            'riskControl' => $riskControl,
            'divisionRisks' => $divisionRisks,
            'controlTypes' => [
                'preventive' => 'การป้องกัน',
                'detective' => 'การตรวจจับ',
                'corrective' => 'การแก้ไข',
                'compensating' => 'การชดเชย'
            ],
            'statusOptions' => [
                'active' => 'ใช้งาน',
                'inactive' => 'ไม่ใช้งาน'
            ]
        ]);
    }

    /**
     * อัปเดตข้อมูลการควบคุมความเสี่ยงที่มีอยู่
     * 
     * @param \App\Http\Requests\UpdateRiskControlRequest $request คำขออัปเดตที่ผ่านการตรวจสอบแล้ว
     * @param \App\Models\RiskControl $riskControl ข้อมูลการควบคุมความเสี่ยงที่ต้องการอัปเดต
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function update(UpdateRiskControlRequest $request, RiskControl $riskControl)
    {
        // เริ่ม transaction
        DB::beginTransaction();
        
        try {
            // อัปเดตข้อมูลพื้นฐาน
            $riskControl->update($request->validated());
            
            // ยืนยันการทำรายการ
            DB::commit();
                
            // ดึงข้อมูลที่อัปเดตเรียบร้อยแล้วพร้อมความสัมพันธ์
            $updatedControl = RiskControl::with([
                'divisionRisk'
            ])->find($riskControl->id);
            
            // บันทึกล็อกสำหรับการติดตาม
            Log::info('อัปเดตการควบคุมความเสี่ยง', [
                'id' => $riskControl->id,
                'control_name' => $riskControl->control_name,
                'user' => auth()->check() ? auth()->user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            return redirect()->back()->with([
                'message' => 'อัปเดตข้อมูลการควบคุมความเสี่ยงเรียบร้อยแล้ว',
                'updatedControl' => $updatedControl
            ]);
        } catch (\Exception $e) {
            // ยกเลิกการทำรายการทั้งหมดหากเกิดข้อผิดพลาด
            DB::rollBack();
            
            Log::error('การอัปเดตการควบคุมความเสี่ยงล้มเหลว: ' . $e->getMessage(), [
                'control_id' => $riskControl->id,
                'user' => auth()->user()->name ?? 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * ลบข้อมูลการควบคุมความเสี่ยง (Soft Delete)
     * 
     * @param \App\Models\RiskControl $riskControl ข้อมูลการควบคุมความเสี่ยงที่ต้องการลบ
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function destroy(RiskControl $riskControl)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบและบันทึก log
        $oldData = $riskControl->toArray();
        
        // ดำเนินการลบข้อมูล (Soft Delete)
        $riskControl->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบการควบคุมความเสี่ยง', [
            'id' => $oldData['id'],
            'control_name' => $oldData['control_name'] ?? null,
            'control_type' => $oldData['control_type'] ?? null,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบการควบคุมความเสี่ยงเรียบร้อยแล้ว');
    }

    /**
     * เปลี่ยนสถานะการควบคุมความเสี่ยง
     */
    public function toggleStatus(RiskControl $riskControl)
    {
        $newStatus = $riskControl->status === 'active' ? 'inactive' : 'active';
        $riskControl->update(['status' => $newStatus]);

        Log::info('เปลี่ยนสถานะการควบคุมความเสี่ยง', [
            'id' => $riskControl->id,
            'control_name' => $riskControl->control_name,
            'old_status' => $riskControl->status === 'active' ? 'inactive' : 'active',
            'new_status' => $newStatus,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ'
        ]);

        return redirect()->back()->with('success', 'เปลี่ยนสถานะการควบคุมความเสี่ยงเรียบร้อยแล้ว');
    }
}
