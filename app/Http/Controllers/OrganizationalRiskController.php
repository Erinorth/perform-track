<?php
/**
 * ไฟล์: app\Http\Controllers\OrganizationalRiskController.php
 * Controller สำหรับจัดการความเสี่ยงระดับองค์กร
 * ใช้งานร่วมกับ Laravel 12, Inertia.js, Vue 3
 * ทำหน้าที่จัดการข้อมูลความเสี่ยงระดับองค์กรตั้งแต่การแสดงผล สร้าง แก้ไข และลบ
 */

namespace App\Http\Controllers;

use App\Models\OrganizationalRisk;  // นำเข้าโมเดล OrganizationalRisk สำหรับทำงานกับตารางในฐานข้อมูล
use App\Http\Requests\StoreOrganizationalRiskRequest;  // นำเข้า Form Request สำหรับการตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateOrganizationalRiskRequest;  // นำเข้า Form Request สำหรับการตรวจสอบข้อมูลการแก้ไข
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;  // นำเข้า Log facade สำหรับบันทึก log การทำงาน
use Inertia\Inertia;                 // นำเข้า Inertia สำหรับเชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // นำเข้า Auth facade สำหรับจัดการข้อมูลผู้ใช้ที่ล็อกอิน

class OrganizationalRiskController extends Controller
{
    /**
     * แสดงรายการความเสี่ยงระดับองค์กรทั้งหมด
     * เรียงลำดับตามปี (ล่าสุดก่อน) และตามชื่อความเสี่ยง
     * 
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมด เรียงตามปี (มากไปน้อย) และชื่อ
        $risks = OrganizationalRisk::with('departmentRisks')  // เพิ่ม with() เพื่อโหลดความสัมพันธ์
            ->orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();

        // บันทึก log การเข้าถึงหน้ารายการความเสี่ยง
        Log::info('เข้าถึงรายการความเสี่ยงระดับองค์กร', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

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
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนบันทึกโดย StoreOrganizationalRiskRequest
     * 
     * @param \App\Http\Requests\StoreOrganizationalRiskRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOrganizationalRiskRequest $request)
    {
        // ตรวจสอบข้อมูลอัตโนมัติโดย FormRequest และสร้างข้อมูลใหม่
        $risk = OrganizationalRisk::create($request->validated());
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('สร้างความเสี่ยงระดับองค์กรใหม่', [
            'id' => $risk->id,
            'name' => $risk->risk_name,
            'year' => $risk->year,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // ดึงข้อมูลความเสี่ยงทั้งหมดใหม่อีกครั้ง
        $risks = OrganizationalRisk::orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลความเสี่ยงทั้งหมด
        return redirect()->back()
            ->with('success', 'เพิ่มความเสี่ยงระดับองค์กรเรียบร้อยแล้ว')
            ->with('risks', $risks); // ส่งข้อมูลความเสี่ยงทั้งหมดกลับไปด้วย
    }

    /**
     * แสดงข้อมูลความเสี่ยงระดับองค์กรเฉพาะรายการ
     * หมายเหตุ: ไม่ได้ใช้ในปัจจุบัน เนื่องจากดูรายละเอียดผ่าน modal ใน Vue
     * 
     * @param \App\Models\OrganizationalRisk $organizationalRisk
     */
    public function show(OrganizationalRisk $organizationalRisk)
    {
        // ไม่ได้ใช้งานในขณะนี้ เนื่องจากแสดงข้อมูลทั้งหมดในหน้า index
    }

    /**
     * แสดงฟอร์มสำหรับแก้ไขความเสี่ยง
     * หมายเหตุ: ไม่ได้ใช้เนื่องจากใช้ Modal ใน Vue แทน
     * 
     * @param \App\Models\OrganizationalRisk $organizationalRisk
     */
    public function edit(OrganizationalRisk $organizationalRisk)
    {
        // ไม่ได้ใช้งานเนื่องจากการแก้ไขทำผ่าน Modal ใน frontend
    }

    /**
     * อัปเดตข้อมูลความเสี่ยงระดับองค์กรที่มีอยู่
     * ทำการตรวจสอบความถูกต้องของข้อมูลก่อนอัปเดตโดย UpdateOrganizationalRiskRequest
     * 
     * @param \App\Http\Requests\UpdateOrganizationalRiskRequest $request
     * @param \App\Models\OrganizationalRisk $organizationalRisk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOrganizationalRiskRequest $request, OrganizationalRisk $organizationalRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบและเปรียบเทียบการเปลี่ยนแปลง
        $oldData = $organizationalRisk->toArray();
        
        // รับข้อมูลที่ผ่านการตรวจสอบจาก FormRequest
        $validated = $request->validated();
        
        // อัปเดตข้อมูลในฐานข้อมูล
        $organizationalRisk->update($validated);
        
        // บันทึก log สำหรับการตรวจสอบ พร้อมข้อมูลที่เปลี่ยนแปลงเพื่อการติดตาม
        Log::info('อัปเดตความเสี่ยงระดับองค์กร', [
            'id' => $organizationalRisk->id,
            'name' => $organizationalRisk->risk_name,
            'changes' => array_diff_assoc($validated, $oldData),
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'อัปเดตความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับองค์กร (Soft Delete)
     * หมายเหตุ: ข้อมูลจะไม่ถูกลบจริงจากฐานข้อมูลหากใช้ SoftDeletes
     * 
     * @param \App\Models\OrganizationalRisk $organizationalRisk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(OrganizationalRisk $organizationalRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบก่อนที่จะลบ
        $oldData = $organizationalRisk->toArray();
        
        // ตรวจสอบว่ามีความเสี่ยงระดับสายงานที่เชื่อมโยงกับความเสี่ยงนี้หรือไม่
        $hasDepartmentRisks = $organizationalRisk->departmentRisks()->exists();
        
        if ($hasDepartmentRisks) {
            // ถ้ามีความเสี่ยงระดับสายงานที่เชื่อมโยง ให้แจ้งเตือนและยกเลิกการลบ
            return redirect()->back()->with('error', 'ไม่สามารถลบความเสี่ยงนี้ได้เนื่องจากมีความเสี่ยงระดับสายงานที่เชื่อมโยงอยู่');
        }
        
        // ลบข้อมูล (Soft Delete หากมีการกำหนดในโมเดล)
        $organizationalRisk->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับองค์กร', [
            'id' => $oldData['id'],
            'name' => $oldData['risk_name'],
            'year' => $oldData['year'],
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับองค์กรหลายรายการพร้อมกัน (Bulk Delete)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDestroy(Request $request)
    {
        // ตรวจสอบข้อมูลที่ส่งมา
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:organizational_risks,id'
        ]);
        
        // ดึง IDs ที่ต้องการลบ
        $ids = $validated['ids'];
        
        // ตรวจสอบว่ามีความเสี่ยงใดบ้างที่มีความเสี่ยงระดับสายงานเชื่อมโยงอยู่
        $risksWithDependencies = OrganizationalRisk::whereIn('id', $ids)
            ->whereHas('departmentRisks')
            ->pluck('id')
            ->toArray();
        
        if (!empty($risksWithDependencies)) {
            // ถ้ามีความเสี่ยงที่ไม่สามารถลบได้ กรองออกจากรายการที่จะลบ
            $idsToDelete = array_diff($ids, $risksWithDependencies);
            
            // สร้างข้อความแจ้งเตือน
            $errorMessage = count($risksWithDependencies) === count($ids)
                ? 'ไม่สามารถลบความเสี่ยงที่เลือกได้เนื่องจากมีความเสี่ยงระดับสายงานที่เชื่อมโยงอยู่'
                : 'ไม่สามารถลบบางรายการได้เนื่องจากมีความเสี่ยงระดับสายงานที่เชื่อมโยงอยู่';
            
            // ถ้าไม่มีรายการที่สามารถลบได้เลย ให้ส่งข้อความแจ้งเตือนกลับไป
            if (empty($idsToDelete)) {
                return response()->json(['error' => $errorMessage], 422);
            }
            
            // อัปเดตรายการที่จะลบ
            $ids = $idsToDelete;
        }
        
        // ลบข้อมูลตามรายการที่สามารถลบได้
        $deletedCount = OrganizationalRisk::whereIn('id', $ids)->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับองค์กรหลายรายการ', [
            'deleted_count' => $deletedCount,
            'requested_ids' => $validated['ids'],
            'actual_deleted_ids' => $ids,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // สร้างข้อความแจ้งเตือนตามผลลัพธ์
        $successMessage = 'ลบความเสี่ยงระดับองค์กรจำนวน ' . $deletedCount . ' รายการเรียบร้อยแล้ว';
        
        if (isset($errorMessage)) {
            // กรณีมีบางรายการที่ไม่สามารถลบได้
            return response()->json([
                'message' => $successMessage,
                'warning' => $errorMessage,
                'deleted_count' => $deletedCount,
                'failed_ids' => $risksWithDependencies
            ]);
        }

        $risks = OrganizationalRisk::orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();
        
        // กรณีลบได้ทั้งหมด
        return redirect()->back()
            ->with('success', $successMessage)
            ->with('risks', $risks)
            ->with('deleted_count', $deletedCount);
    }
}
