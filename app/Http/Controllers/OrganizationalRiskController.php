<?php
/**
 * ไฟล์: app\Http\Controllers\OrganizationalRiskController.php
 * Controller สำหรับจัดการความเสี่ยงระดับองค์กร
 * ใช้งานร่วมกับ Laravel 12, Inertia.js, Vue 3
 */

namespace App\Http\Controllers;

use App\Models\OrganizationalRisk;  // นำเข้าโมเดล OrganizationalRisk
use Illuminate\Http\Request;         // นำเข้าคลาส Request สำหรับจัดการคำขอ HTTP
use Illuminate\Support\Facades\Log;  // นำเข้า Log facade สำหรับบันทึก log
use Inertia\Inertia;                 // นำเข้า Inertia สำหรับเชื่อมต่อกับ Vue
use Illuminate\Support\Facades\Auth;

class OrganizationalRiskController extends Controller
{
    /**
     * แสดงรายการความเสี่ยงระดับองค์กรทั้งหมด
     * เรียงลำดับตามปี (ล่าสุดก่อน) และตามชื่อความเสี่ยง
     */
    public function index()
    {
        // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมด เรียงตามปี (มากไปน้อย) และชื่อ
        $risks = OrganizationalRisk::orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();

        // ส่งข้อมูลไปยังหน้า Vue ผ่าน Inertia
        return Inertia::render('organizational_risk/OrganizationalRisk', [
            'risks' => $risks
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
     * บันทึกข้อมูลความเสี่ยงระดับองค์กรใหม่ลงฐานข้อมูล
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนบันทึก
     */
    public function store(Request $request)
    {
        // สร้างข้อมูลโดยใช้ข้อมูลที่ผ่านการตรวจสอบแล้ว
        $risk = OrganizationalRisk::create($request->validated());
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('สร้างความเสี่ยงระดับองค์กรใหม่', [
            'id' => $risk->id,
            'name' => $risk->risk_name,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ'
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'เพิ่มความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
    }

    /**
     * แสดงข้อมูลความเสี่ยงระดับองค์กรเฉพาะรายการ
     * หมายเหตุ: ไม่ได้ใช้ในปัจจุบัน
     */
    public function show(OrganizationalRisk $organizationalRisk)
    {
        // ไม่ได้ใช้งานในขณะนี้ เนื่องจากแสดงข้อมูลทั้งหมดในหน้า index
    }

    /**
     * แสดงฟอร์มสำหรับแก้ไขความเสี่ยง
     * หมายเหตุ: ไม่ได้ใช้เนื่องจากใช้ Modal ใน Vue แทน
     */
    public function edit(OrganizationalRisk $organizationalRisk)
    {
        // ไม่ได้ใช้งานเนื่องจากการแก้ไขทำผ่าน Modal ใน frontend
    }

    /**
     * อัปเดตข้อมูลความเสี่ยงระดับองค์กรที่มีอยู่
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนอัปเดต
     */
    public function update(Request $request, OrganizationalRisk $organizationalRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบ
        $oldData = $organizationalRisk->toArray();
        
        // อัปเดตข้อมูล
        $validated = $request->validated();
        $organizationalRisk->update($validated);
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('อัปเดตความเสี่ยงระดับองค์กร', [
            'id' => $organizationalRisk->id,
            'name' => $organizationalRisk->risk_name,
            'changes' => array_diff_assoc($validated, $oldData),
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ'
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'อัปเดตความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับองค์กร (Soft Delete)
     * หมายเหตุ: ข้อมูลจะไม่ถูกลบจริงจากฐานข้อมูลหากใช้ SoftDeletes
     */
    public function destroy(OrganizationalRisk $organizationalRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบ
        $oldData = $organizationalRisk->toArray();
        
        // ลบข้อมูล (Soft Delete หากมีการกำหนดในโมเดล)
        $organizationalRisk->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับองค์กร', [
            'id' => $oldData['id'],
            'name' => $oldData['risk_name'],
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ'
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
    }
}