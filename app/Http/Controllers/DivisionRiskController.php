<?php
/**
 * ไฟล์: app\Http\Controllers\DivisionRiskController.php
 * Controller สำหรับจัดการความเสี่ยงระดับฝ่าย
 * ใช้งานร่วมกับ Laravel 12, Inertia.js, Vue 3
 * ทำหน้าที่จัดการข้อมูลความเสี่ยงระดับฝ่ายตั้งแต่การแสดงผล สร้าง แก้ไข และลบ
 */

namespace App\Http\Controllers;

use App\Models\DivisionRisk;  // นำเข้าโมเดล DivisionRisk สำหรับทำงานกับตารางในฐานข้อมูล
use App\Models\OrganizationalRisk; // นำเข้าโมเดล OrganizationalRisk เพื่อใช้ในการเชื่อมโยงข้อมูล
use App\Http\Requests\StoreDivisionRiskRequest;  // นำเข้า Form Request สำหรับการตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateDivisionRiskRequest;  // นำเข้า Form Request สำหรับการตรวจสอบข้อมูลการแก้ไข
use Illuminate\Support\Facades\Log;  // นำเข้า Log facade สำหรับบันทึก log การทำงาน
use Inertia\Inertia;                 // นำเข้า Inertia สำหรับเชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // นำเข้า Auth facade สำหรับจัดการข้อมูลผู้ใช้ที่ล็อกอิน

class DivisionRiskController extends Controller
{
    /**
     * แสดงรายการความเสี่ยงระดับฝ่ายทั้งหมด
     * เรียงลำดับตามปี (ล่าสุดก่อน) และตามชื่อความเสี่ยง
     * 
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลความเสี่ยงระดับฝ่ายทั้งหมด เรียงตามปี (มากไปน้อย) และชื่อ
        // พร้อมโหลดข้อมูลความเสี่ยงระดับองค์กรที่เชื่อมโยง
        $risks = DivisionRisk::with('organizationalRisk')
            ->orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();

        // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมดเพื่อใช้ในตัวเลือกตอนสร้างความเสี่ยงระดับฝ่าย
        $organizationalRisks = OrganizationalRisk::orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();

        // บันทึก log การเข้าถึงหน้ารายการความเสี่ยง
        Log::info('เข้าถึงรายการความเสี่ยงระดับฝ่าย', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // ส่งข้อมูลไปยังหน้า Vue ผ่าน Inertia
        return Inertia::render('division_risk/DivisionRisk', [
            'risks' => $risks,
            'organizationalRisks' => $organizationalRisks
        ]);
    }

    /**
     * แสดงฟอร์มสำหรับสร้างความเสี่ยงใหม่
     * หมายเหตุ: ไม่ได้ใช้เนื่องจากใช้ Modal ใน Vue แทน
     */
    public function create()
    {
        // ไม่ได้ใช้งานเนื่องจากการสร้างข้อมูลใหม่ทำผ่าน Modal ใน frontend
    }

    /**
     * บันทึกข้อมูลความเสี่ยงระดับฝ่ายใหม่ลงฐานข้อมูล
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนบันทึกโดย StoreDivisionRiskRequest
     * 
     * @param \App\Http\Requests\StoreDivisionRiskRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreDivisionRiskRequest $request)
    {
        // ตรวจสอบข้อมูลอัตโนมัติโดย FormRequest และสร้างข้อมูลใหม่
        $risk = DivisionRisk::create($request->validated());
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('สร้างความเสี่ยงระดับฝ่ายใหม่', [
            'id' => $risk->id,
            'name' => $risk->risk_name,
            'year' => $risk->year,
            'organizational_risk_id' => $risk->organizational_risk_id,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // ดึงข้อมูลความเสี่ยงทั้งหมดใหม่อีกครั้ง
        $risks = DivisionRisk::with('organizationalRisk')
            ->orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลความเสี่ยงทั้งหมด
        return redirect()->back()
            ->with('success', 'เพิ่มความเสี่ยงระดับฝ่ายเรียบร้อยแล้ว')
            ->with('risks', $risks); // ส่งข้อมูลความเสี่ยงทั้งหมดกลับไปด้วย
    }

    /**
     * แสดงข้อมูลความเสี่ยงระดับฝ่ายเฉพาะรายการ
     * หมายเหตุ: ไม่ได้ใช้ในปัจจุบัน เนื่องจากดูรายละเอียดผ่าน modal ใน Vue
     * 
     * @param \App\Models\DivisionRisk $divisionRisk
     */
    public function show(DivisionRisk $divisionRisk)
    {
        // ไม่ได้ใช้งานในขณะนี้ เนื่องจากแสดงข้อมูลทั้งหมดในหน้า index
    }

    /**
     * แสดงฟอร์มสำหรับแก้ไขความเสี่ยง
     * หมายเหตุ: ไม่ได้ใช้เนื่องจากใช้ Modal ใน Vue แทน
     * 
     * @param \App\Models\DivisionRisk $divisionRisk
     */
    public function edit(DivisionRisk $divisionRisk)
    {
        // ไม่ได้ใช้งานเนื่องจากการแก้ไขทำผ่าน Modal ใน frontend
    }

    /**
     * อัปเดตข้อมูลความเสี่ยงระดับฝ่ายที่มีอยู่
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนอัปเดตโดย UpdateDivisionRiskRequest
     * 
     * @param \App\Http\Requests\UpdateDivisionRiskRequest $request
     * @param \App\Models\DivisionRisk $divisionRisk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateDivisionRiskRequest $request, DivisionRisk $divisionRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบและเปรียบเทียบการเปลี่ยนแปลง
        $oldData = $divisionRisk->toArray();
        
        // รับข้อมูลที่ผ่านการตรวจสอบจาก FormRequest
        $validated = $request->validated();
        
        // อัปเดตข้อมูลในฐานข้อมูล
        $divisionRisk->update($validated);
        
        // บันทึก log สำหรับการตรวจสอบ พร้อมข้อมูลที่เปลี่ยนแปลงเพื่อการติดตาม
        Log::info('อัปเดตความเสี่ยงระดับฝ่าย', [
            'id' => $divisionRisk->id,
            'name' => $divisionRisk->risk_name,
            'changes' => array_diff_assoc($validated, $oldData),
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'อัปเดตความเสี่ยงระดับฝ่ายเรียบร้อยแล้ว');
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับฝ่าย (Soft Delete)
     * หมายเหตุ: ข้อมูลจะไม่ถูกลบจริงจากฐานข้อมูลหากใช้ SoftDeletes
     * 
     * @param \App\Models\DivisionRisk $divisionRisk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DivisionRisk $divisionRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบก่อนที่จะลบ
        $oldData = $divisionRisk->toArray();
        
        // ตรวจสอบว่ามีการประเมินความเสี่ยงที่เชื่อมโยงกับความเสี่ยงนี้หรือไม่
        $hasRiskAssessments = $divisionRisk->riskAssessments()->exists();
        
        // ตรวจสอบว่ามีเกณฑ์การประเมินที่เชื่อมโยงกับความเสี่ยงนี้หรือไม่
        $hasImpactCriteria = $divisionRisk->impactCriteria()->exists();
        $hasLikelihoodCriteria = $divisionRisk->likelihoodCriteria()->exists();
        
        if ($hasRiskAssessments || $hasImpactCriteria || $hasLikelihoodCriteria) {
            // ถ้ามีข้อมูลที่เชื่อมโยง ให้แจ้งเตือนและยกเลิกการลบ
            return redirect()->back()->with('error', 'ไม่สามารถลบความเสี่ยงนี้ได้เนื่องจากมีข้อมูลการประเมินหรือเกณฑ์ที่เชื่อมโยงอยู่');
        }
        
        // ลบข้อมูล (Soft Delete หากมีการกำหนดในโมเดล)
        $divisionRisk->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับฝ่าย', [
            'id' => $oldData['id'],
            'name' => $oldData['risk_name'],
            'year' => $oldData['year'],
            'organizational_risk_id' => $oldData['organizational_risk_id'],
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับฝ่ายเรียบร้อยแล้ว');
    }
}
