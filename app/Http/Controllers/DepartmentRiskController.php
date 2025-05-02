<?php
/**
 * ไฟล์: app\Http\Controllers\DepartmentRiskController.php
 * Controller สำหรับจัดการความเสี่ยงระดับสายงาน
 * ใช้งานร่วมกับ Laravel 12, Inertia.js, Vue 3
 * ทำหน้าที่จัดการข้อมูลความเสี่ยงระดับสายงานตั้งแต่การแสดงผล สร้าง แก้ไข และลบ
 */

namespace App\Http\Controllers;

use App\Models\DepartmentRisk;  // นำเข้าโมเดล DepartmentRisk สำหรับทำงานกับตารางในฐานข้อมูล
use App\Models\OrganizationalRisk; // นำเข้าโมเดล OrganizationalRisk เพื่อใช้ในการเชื่อมโยงข้อมูล
use App\Http\Requests\StoreDepartmentRiskRequest;  // นำเข้า Form Request สำหรับการตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateDepartmentRiskRequest;  // นำเข้า Form Request สำหรับการตรวจสอบข้อมูลการแก้ไข
use Illuminate\Support\Facades\Log;  // นำเข้า Log facade สำหรับบันทึก log การทำงาน
use Inertia\Inertia;                 // นำเข้า Inertia สำหรับเชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // นำเข้า Auth facade สำหรับจัดการข้อมูลผู้ใช้ที่ล็อกอิน

class DepartmentRiskController extends Controller
{
    /**
     * แสดงรายการความเสี่ยงระดับสายงานทั้งหมด
     * เรียงลำดับตามปี (ล่าสุดก่อน) และตามชื่อความเสี่ยง
     * 
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลความเสี่ยงระดับสายงานทั้งหมด เรียงตามปี (มากไปน้อย) และชื่อ
        // พร้อมโหลดข้อมูลความเสี่ยงระดับองค์กรที่เชื่อมโยง
        $risks = DepartmentRisk::with('organizationalRisk')
            ->orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();

        // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมดเพื่อใช้ในตัวเลือกตอนสร้างความเสี่ยงระดับสายงาน
        $organizationalRisks = OrganizationalRisk::orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();

        // บันทึก log การเข้าถึงหน้ารายการความเสี่ยง
        Log::info('เข้าถึงรายการความเสี่ยงระดับสายงาน', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // ส่งข้อมูลไปยังหน้า Vue ผ่าน Inertia
        return Inertia::render('department_risk/DepartmentRisk', [
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
     * บันทึกข้อมูลความเสี่ยงระดับสายงานใหม่ลงฐานข้อมูล
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนบันทึกโดย StoreDepartmentRiskRequest
     * 
     * @param \App\Http\Requests\StoreDepartmentRiskRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreDepartmentRiskRequest $request)
    {
        // ตรวจสอบข้อมูลอัตโนมัติโดย FormRequest และสร้างข้อมูลใหม่
        $risk = DepartmentRisk::create($request->validated());
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('สร้างความเสี่ยงระดับสายงานใหม่', [
            'id' => $risk->id,
            'name' => $risk->risk_name,
            'year' => $risk->year,
            'organizational_risk_id' => $risk->organizational_risk_id,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // ดึงข้อมูลความเสี่ยงทั้งหมดใหม่อีกครั้ง
        $risks = DepartmentRisk::with('organizationalRisk')
            ->orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลความเสี่ยงทั้งหมด
        return redirect()->back()
            ->with('success', 'เพิ่มความเสี่ยงระดับสายงานเรียบร้อยแล้ว')
            ->with('risks', $risks); // ส่งข้อมูลความเสี่ยงทั้งหมดกลับไปด้วย
    }

    /**
     * แสดงข้อมูลความเสี่ยงระดับสายงานเฉพาะรายการ
     * หมายเหตุ: ไม่ได้ใช้ในปัจจุบัน เนื่องจากดูรายละเอียดผ่าน modal ใน Vue
     * 
     * @param \App\Models\DepartmentRisk $departmentRisk
     */
    public function show(DepartmentRisk $departmentRisk)
    {
        // ไม่ได้ใช้งานในขณะนี้ เนื่องจากแสดงข้อมูลทั้งหมดในหน้า index
    }

    /**
     * แสดงฟอร์มสำหรับแก้ไขความเสี่ยง
     * หมายเหตุ: ไม่ได้ใช้เนื่องจากใช้ Modal ใน Vue แทน
     * 
     * @param \App\Models\DepartmentRisk $departmentRisk
     */
    public function edit(DepartmentRisk $departmentRisk)
    {
        // ไม่ได้ใช้งานเนื่องจากการแก้ไขทำผ่าน Modal ใน frontend
    }

    /**
     * อัปเดตข้อมูลความเสี่ยงระดับสายงานที่มีอยู่
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนอัปเดตโดย UpdateDepartmentRiskRequest
     * 
     * @param \App\Http\Requests\UpdateDepartmentRiskRequest $request
     * @param \App\Models\DepartmentRisk $departmentRisk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateDepartmentRiskRequest $request, DepartmentRisk $departmentRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบและเปรียบเทียบการเปลี่ยนแปลง
        $oldData = $departmentRisk->toArray();
        
        // รับข้อมูลที่ผ่านการตรวจสอบจาก FormRequest
        $validated = $request->validated();
        
        // อัปเดตข้อมูลในฐานข้อมูล
        $departmentRisk->update($validated);
        
        // บันทึก log สำหรับการตรวจสอบ พร้อมข้อมูลที่เปลี่ยนแปลงเพื่อการติดตาม
        Log::info('อัปเดตความเสี่ยงระดับสายงาน', [
            'id' => $departmentRisk->id,
            'name' => $departmentRisk->risk_name,
            'changes' => array_diff_assoc($validated, $oldData),
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'อัปเดตความเสี่ยงระดับสายงานเรียบร้อยแล้ว');
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับสายงาน (Soft Delete)
     * หมายเหตุ: ข้อมูลจะไม่ถูกลบจริงจากฐานข้อมูลหากใช้ SoftDeletes
     * 
     * @param \App\Models\DepartmentRisk $departmentRisk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DepartmentRisk $departmentRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบก่อนที่จะลบ
        $oldData = $departmentRisk->toArray();
        
        // ตรวจสอบว่ามีการประเมินความเสี่ยงที่เชื่อมโยงกับความเสี่ยงนี้หรือไม่
        $hasRiskAssessments = $departmentRisk->riskAssessments()->exists();
        
        // ตรวจสอบว่ามีเกณฑ์การประเมินที่เชื่อมโยงกับความเสี่ยงนี้หรือไม่
        $hasImpactCriteria = $departmentRisk->impactCriteria()->exists();
        $hasLikelihoodCriteria = $departmentRisk->likelihoodCriteria()->exists();
        
        if ($hasRiskAssessments || $hasImpactCriteria || $hasLikelihoodCriteria) {
            // ถ้ามีข้อมูลที่เชื่อมโยง ให้แจ้งเตือนและยกเลิกการลบ
            return redirect()->back()->with('error', 'ไม่สามารถลบความเสี่ยงนี้ได้เนื่องจากมีข้อมูลการประเมินหรือเกณฑ์ที่เชื่อมโยงอยู่');
        }
        
        // ลบข้อมูล (Soft Delete หากมีการกำหนดในโมเดล)
        $departmentRisk->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับสายงาน', [
            'id' => $oldData['id'],
            'name' => $oldData['risk_name'],
            'year' => $oldData['year'],
            'organizational_risk_id' => $oldData['organizational_risk_id'],
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับสายงานเรียบร้อยแล้ว');
    }
}
