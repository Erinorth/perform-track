<?php
/**
 * ไฟล์: app\Http\Controllers\OrganizationalRiskController.php
 * คำอธิบาย: Controller สำหรับจัดการความเสี่ยงระดับองค์กรในระบบประเมินความเสี่ยง
 * เทคโนโลยี: Laravel 12, Inertia.js, Vue 3
 * ทำหน้าที่: จัดการข้อมูลความเสี่ยงระดับองค์กรและเอกสารแนบที่เกี่ยวข้อง
 * ความสัมพันธ์: เชื่อมโยงกับ OrganizationalRisk Model และส่งข้อมูลไปยัง Vue ผ่าน Inertia
 */

namespace App\Http\Controllers;

// นำเข้า Models และ Requests ที่เกี่ยวข้อง
use App\Models\OrganizationalRisk;  // โมเดลสำหรับจัดการข้อมูลความเสี่ยงระดับองค์กร
use App\Models\OrganizationalRiskAttachment;  // โมเดลสำหรับจัดการข้อมูลเอกสารแนบ
use App\Http\Requests\StoreOrganizationalRiskRequest;  // Form Request สำหรับตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateOrganizationalRiskRequest;  // Form Request สำหรับตรวจสอบข้อมูลการแก้ไข
use Illuminate\Http\Request;  // สำหรับจัดการคำขอจาก HTTP
use Illuminate\Support\Facades\Log;  // สำหรับบันทึก log การทำงาน
use Inertia\Inertia;  // เชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // จัดการข้อมูลผู้ใช้ที่ล็อกอิน
use Illuminate\Support\Facades\Storage;  // จัดการไฟล์ในระบบ

class OrganizationalRiskController extends Controller
{
    /**
     * แสดงรายการความเสี่ยงระดับองค์กรทั้งหมด
     * ดึงข้อมูลพร้อมความสัมพันธ์และเรียงลำดับตามชื่อความเสี่ยง
     * 
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมด พร้อมโหลดความสัมพันธ์
        $risks = OrganizationalRisk::with(['divisionRisks', 'attachments'])
            ->orderBy('risk_name')  // เรียงตามชื่อความเสี่ยง
            ->get();  // ดึงข้อมูลทั้งหมด

        // บันทึก log การเข้าถึงหน้ารายการความเสี่ยง เพื่อติดตามการใช้งาน
        Log::info('เข้าถึงรายการความเสี่ยงระดับองค์กร', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // ส่งข้อมูลไปยังหน้า Vue ผ่าน Inertia
        return Inertia::render('organizational_risk/OrganizationalRisk', [
            'risks' => $risks  // ส่งข้อมูลความเสี่ยงไปยัง Vue component
        ]);
    }

    /**
     * บันทึกข้อมูลความเสี่ยงระดับองค์กรใหม่ลงฐานข้อมูล
     * 
     * @param \App\Http\Requests\StoreOrganizationalRiskRequest $request คำขอที่ผ่านการตรวจสอบแล้ว
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function store(StoreOrganizationalRiskRequest $request)
    {
        // สร้างข้อมูลความเสี่ยงใหม่โดยใช้ข้อมูลที่ผ่านการตรวจสอบแล้ว
        $risk = OrganizationalRisk::create($request->validated());
        
        // จัดการเอกสารแนบที่ส่งมาพร้อมคำขอ
        $this->handleAttachments($request, $risk);
        
        // บันทึกล็อกสำหรับการติดตามและตรวจสอบ
        Log::info('สร้างความเสี่ยงองค์กรใหม่', [
            'id' => $risk->id, 
            'name' => $risk->risk_name, 
            'user' => Auth::check() ? Auth::user()->name : null,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // ดึงข้อมูลความเสี่ยงทั้งหมดมาใหม่หลังจากบันทึกข้อมูล
        $risks = OrganizationalRisk::with('divisionRisks')
            ->orderBy('risk_name')
            ->get();
            
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลล่าสุด
        return redirect()->back()
            ->with('success', 'เพิ่มความเสี่ยงองค์กรเรียบร้อยแล้ว')
            ->with('risks', $risks);
    }

    /**
     * แสดงรายละเอียดเต็มของความเสี่ยงระดับองค์กร
     * 
     * @param \App\Models\OrganizationalRisk $organizationalRisk ข้อมูลความเสี่ยงที่ต้องการแสดง
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลความเสี่ยงและความสัมพันธ์
     */
    public function show(OrganizationalRisk $organizationalRisk)
    {
        // โหลดข้อมูลความเสี่ยงพร้อมความสัมพันธ์
        $risk = OrganizationalRisk::with(['divisionRisks', 'attachments'])
            ->findOrFail($organizationalRisk->id);
        
        // บันทึก log การเข้าถึงรายละเอียดความเสี่ยง
        Log::info('เข้าถึงรายละเอียดความเสี่ยงระดับองค์กร', [
            'risk_id' => $risk->id,
            'risk_name' => $risk->risk_name,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // ส่งข้อมูลไปยังหน้า Vue
        return Inertia::render('organizational_risk/Show', [
            'risk' => $risk
        ]);
    }

    /**
     * อัปเดตข้อมูลความเสี่ยงระดับองค์กรที่มีอยู่
     * 
     * @param \App\Http\Requests\UpdateOrganizationalRiskRequest $request คำขออัปเดตที่ผ่านการตรวจสอบแล้ว
     * @param \App\Models\OrganizationalRisk $organizationalRisk ข้อมูลความเสี่ยงที่ต้องการอัปเดต
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function update(UpdateOrganizationalRiskRequest $request, OrganizationalRisk $organizationalRisk)
    {
        try {
            // อัปเดตข้อมูลพื้นฐาน
            $organizationalRisk->update([
                'risk_name' => $request->risk_name,
                'description' => $request->description,
            ]);
            
            // จัดการไฟล์แนบเสมอ โดยไม่ต้องตรวจสอบว่ามีไฟล์หรือไม่
            $this->handleAttachments($request, $organizationalRisk);
            
            // จัดการไฟล์ที่ต้องการลบ
            if ($request->has('attachments_to_delete') && is_array($request->attachments_to_delete)) {
                foreach ($request->attachments_to_delete as $attachmentId) {
                    $attachment = OrganizationalRiskAttachment::find($attachmentId);
                    if ($attachment && $attachment->organizational_risk_id == $organizationalRisk->id) {
                        // ลบไฟล์จริงจาก storage
                        if (Storage::exists('public/' . $attachment->file_path)) {
                            Storage::delete('public/' . $attachment->file_path);
                        }
                        // ลบข้อมูลจากฐานข้อมูล
                        $attachment->delete();
                    }
                }
            }
            
            // ดึงข้อมูลที่อัปเดตเรียบร้อยแล้วพร้อมเอกสารแนบ
            $updatedRisk = OrganizationalRisk::with('attachments')->find($organizationalRisk->id);
            
            return redirect()->back()->with([
                'message' => 'อัปเดตข้อมูลความเสี่ยงเรียบร้อยแล้ว',
                'updatedRisk' => $updatedRisk  // ส่งข้อมูลที่อัปเดตแล้วกลับไป
            ]);
        } catch (\Exception $e) {
            Log::error('การอัปเดตความเสี่ยงล้มเหลว: ' . $e->getMessage(), [
                'risk_id' => $organizationalRisk->id,
                'user' => Auth::user()->name ?? 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับองค์กร (Soft Delete)
     * ตรวจสอบเงื่อนไขที่ไม่อนุญาตให้ลบก่อนดำเนินการ
     * 
     * @param \App\Models\OrganizationalRisk $organizationalRisk ข้อมูลความเสี่ยงที่ต้องการลบ
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function destroy(OrganizationalRisk $organizationalRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบและบันทึก log
        $oldData = $organizationalRisk->toArray();
        
        // ตรวจสอบว่ามีความเสี่ยงระดับฝ่ายที่เชื่อมโยงกับความเสี่ยงนี้หรือไม่
        $hasDivisionRisks = $organizationalRisk->divisionRisks()->exists();
        
        // ป้องกันการลบข้อมูลที่มีการเชื่อมโยงกับข้อมูลอื่น
        if ($hasDivisionRisks) {
            return redirect()->back()->with('error', 'ไม่สามารถลบความเสี่ยงนี้ได้เนื่องจากมีความเสี่ยงระดับฝ่ายที่เชื่อมโยงอยู่');
        }
        
        // ดำเนินการลบข้อมูล (Soft Delete)
        $organizationalRisk->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับองค์กร', [
            'id' => $oldData['id'],
            'name' => $oldData['risk_name'],
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับองค์กรหลายรายการพร้อมกัน (Bulk Delete)
     * ตรวจสอบเงื่อนไขที่ไม่อนุญาตให้ลบก่อนดำเนินการแต่ละรายการ
     * 
     * @param \Illuminate\Http\Request $request คำขอพร้อมรายการ ID ที่ต้องการลบ
     * @return \Illuminate\Http\Response ผลลัพธ์การดำเนินการพร้อมข้อความแจ้งผล
     */
    public function bulkDestroy(Request $request)
    {
        // ตรวจสอบความถูกต้องของข้อมูล ID ที่ส่งมา
        $validated = $request->validate([
            'ids' => 'required|array',  // ต้องเป็น array ไม่เป็นค่าว่าง
            'ids.*' => 'integer|exists:organizational_risks,id'  // แต่ละ ID ต้องเป็นตัวเลขและมีอยู่ในฐานข้อมูล
        ]);
        
        // ดึง IDs ที่ต้องการลบจากข้อมูลที่ตรวจสอบแล้ว
        $ids = $validated['ids'];
        
        // ตรวจสอบว่ามีความเสี่ยงใดบ้างที่มีความเสี่ยงระดับฝ่ายเชื่อมโยงอยู่
        $risksWithDependencies = OrganizationalRisk::whereIn('id', $ids)
            ->whereHas('divisionRisks')  // มีความสัมพันธ์กับตาราง division_risks
            ->pluck('id')  // ดึงเฉพาะ id
            ->toArray();  // แปลงเป็น array
        
        // ถ้ามีความเสี่ยงที่ไม่สามารถลบได้เนื่องจากมีการเชื่อมโยง
        if (!empty($risksWithDependencies)) {
            // กรองเอาเฉพาะ ID ที่สามารถลบได้
            $idsToDelete = array_diff($ids, $risksWithDependencies);
            
            // สร้างข้อความแจ้งเตือนตามสถานการณ์
            $errorMessage = count($risksWithDependencies) === count($ids)
                ? 'ไม่สามารถลบความเสี่ยงที่เลือกได้เนื่องจากมีความเสี่ยงระดับฝ่ายที่เชื่อมโยงอยู่'
                : 'ไม่สามารถลบบางรายการได้เนื่องจากมีความเสี่ยงระดับฝ่ายที่เชื่อมโยงอยู่';
            
            // ถ้าไม่มีรายการที่สามารถลบได้เลย
            if (empty($idsToDelete)) {
                return response()->json(['error' => $errorMessage], 422);
            }
            
            // อัปเดตรายการที่จะลบให้เหลือเฉพาะรายการที่สามารถลบได้
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
        
        // สร้างข้อความแจ้งผลลัพธ์
        $successMessage = 'ลบความเสี่ยงระดับองค์กรจำนวน ' . $deletedCount . ' รายการเรียบร้อยแล้ว';
        
        // กรณีมีบางรายการที่ไม่สามารถลบได้
        if (isset($errorMessage)) {
            return response()->json([
                'message' => $successMessage,
                'warning' => $errorMessage,
                'deleted_count' => $deletedCount,
                'failed_ids' => $risksWithDependencies
            ]);
        }

        // ดึงข้อมูลความเสี่ยงทั้งหมดมาใหม่หลังจากลบข้อมูล
        $risks = OrganizationalRisk::orderBy('risk_name')
            ->get();
        
        // กรณีลบได้ทั้งหมด ส่งข้อมูลกลับไปยังหน้าเดิม
        return redirect()->back()
            ->with('success', $successMessage)
            ->with('risks', $risks)
            ->with('deleted_count', $deletedCount);
    }

    /**
     * ประมวลผลไฟล์เอกสารแนบเดี่ยว
     * 
     * เมธอดพื้นฐานสำหรับการจัดการไฟล์เดียว ใช้ร่วมกันระหว่างเมธอดอื่นๆ
     * เพื่อลดความซ้ำซ้อนของโค้ด
     * 
     * @param \Illuminate\Http\UploadedFile $file ไฟล์ที่อัปโหลด
     * @param \App\Models\OrganizationalRisk $risk ความเสี่ยงที่เชื่อมโยงกับไฟล์
     * @param string $storagePath พาธสำหรับจัดเก็บไฟล์ (ค่าเริ่มต้น: attachments/organizational-risks)
     * @return array ข้อมูลเอกสารแนบที่บันทึกแล้ว
     */
    private function processAttachmentFile($file, $risk, $storagePath = 'attachments/organizational-risks')
    {
        // เก็บไฟล์ในพื้นที่จัดเก็บสาธารณะ
        $path = $file->store($storagePath, 'public');
        
        // สร้างเอกสารแนบในฐานข้อมูล
        $attachment = $risk->attachment()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize()
        ]);
        
        return $attachment->toArray();
    }


    /**
     * จัดการเอกสารแนบหลายไฟล์สำหรับความเสี่ยงองค์กร
     * 
     * @param mixed $request คำขอที่มีไฟล์แนบหลายไฟล์
     * @param OrganizationalRisk $risk ข้อมูลความเสี่ยง
     * @return void
     * @throws \Exception กรณีเกิดข้อผิดพลาด
     */
    private function handleAttachments($request, $risk)
    {
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = 'risk_attachments/' . $risk->id;
                
                $path = $file->storeAs('public/' . $filePath, $fileName);
                
                if ($path) {
                    OrganizationalRiskAttachment::create([
                        'organizational_risk_id' => $risk->id,
                        'file_name' => $fileName,
                        'file_path' => $filePath . '/' . $fileName,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize()
                    ]);
                }
            }
        }
    }
    
    /**
     * เพิ่มเอกสารแนบเดี่ยวสำหรับความเสี่ยงองค์กร (API endpoint)
     * 
     * @param Request $request คำขอที่มีไฟล์แนบเดียว
     * @param OrganizationalRisk $risk ข้อมูลความเสี่ยง
     * @return \Illuminate\Http\JsonResponse ผลลัพธ์การดำเนินการในรูปแบบ JSON
     */
    public function storeAttachment(Request $request, OrganizationalRisk $risk)
    {
        try {
            // ตรวจสอบว่ามีไฟล์ที่อัปโหลดหรือไม่
            if (!$request->hasFile('attachment')) {
                return response()->json(['error' => 'ไม่พบไฟล์ที่อัปโหลด'], 400);
            }

            // ตรวจสอบความถูกต้องของไฟล์
            $request->validate([
                'attachment' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240'
            ]);

            // ดึงไฟล์จากคำขอ
            $file = $request->file('attachment');
            
            // ประมวลผลไฟล์โดยใช้เมธอดกลาง
            $attachment = $this->processAttachmentFile(
                $file,
                $risk,
                'organizational_risk_attachments'
            );
            
            // บันทึก log สำหรับติดตาม
            Log::info('เพิ่มเอกสารแนบสำหรับความเสี่ยงองค์กร', [
                'risk_id' => $risk->id,
                'file_name' => $file->getClientOriginalName(),
                'user' => Auth::check() ? Auth::user()->name : null,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            // ส่งข้อมูลกลับไปยัง client
            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดเอกสารแนบเรียบร้อยแล้ว',
                'attachment' => $attachment
            ]);
        } catch (\Exception $e) {
            // บันทึกล็อกกรณีเกิดข้อผิดพลาด
            Log::error('เกิดข้อผิดพลาดในการอัปโหลดเอกสารแนบ', [
                'risk_id' => $risk->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => Auth::check() ? Auth::user()->name : null,
            ]);
            
            // ส่งข้อความผิดพลาดกลับไปยัง client
            return response()->json([
                'error' => 'เกิดข้อผิดพลาดในการอัปโหลดเอกสารแนบ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ลบเอกสารแนบของความเสี่ยงองค์กร
     * 
     * @param OrganizationalRisk $organizationalRisk ข้อมูลความเสี่ยงที่ต้องการลบเอกสารแนบ
     * @param int $attachmentId ID ของเอกสารแนบที่ต้องการลบ
     * @return \Illuminate\Http\JsonResponse ผลลัพธ์การดำเนินการในรูปแบบ JSON
     */
    public function destroyAttachment(OrganizationalRisk $organizationalRisk, $attachmentId)
    {
        try {
            // ค้นหาเอกสารแนบตาม ID ที่เชื่อมโยงกับความเสี่ยงนี้
            $attachment = $organizationalRisk->attachments()->findOrFail($attachmentId);
            
            // ลบไฟล์จาก storage
            Storage::disk('public')->delete($attachment->file_path);
            
            // ลบข้อมูลจากฐานข้อมูล
            $attachment->delete();
            
            // บันทึกล็อกสำหรับการตรวจสอบ
            Log::info('ลบเอกสารแนบสำหรับความเสี่ยงองค์กร', [
                'risk_id' => $organizationalRisk->id,
                'attachment_id' => $attachmentId,
                'file_name' => $attachment->file_name,
                'user' => Auth::check() ? Auth::user()->name : null,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            // ส่งข้อความสำเร็จกลับไปยัง client
            return response()->json([
                'success' => true,
                'message' => 'ลบเอกสารแนบเรียบร้อยแล้ว'
            ]);
        } catch (\Exception $e) {
            // บันทึกล็อกกรณีเกิดข้อผิดพลาด
            Log::error('เกิดข้อผิดพลาดในการลบเอกสารแนบ', [
                'risk_id' => $organizationalRisk->id,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : null,
            ]);
            
            // ส่งข้อความผิดพลาดกลับไปยัง client
            return response()->json([
                'error' => 'เกิดข้อผิดพลาดในการลบเอกสารแนบ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * จัดการการลบเอกสารแนบในขณะอัปเดตข้อมูลความเสี่ยง
     * 
     * @param mixed $request คำขอที่มีรายการ ID ของเอกสารแนบที่ต้องการลบ
     * @param OrganizationalRisk $organizationalRisk ข้อมูลความเสี่ยงที่ต้องการลบเอกสารแนบ
     * @return void
     */
    private function handleAttachmentsToDelete($request, $organizationalRisk)
    {
        // ตรวจสอบว่ามีรายการเอกสารแนบที่ต้องการลบหรือไม่
        if ($request->has('attachments_to_delete') && is_array($request->attachments_to_delete)) {
            // วนลูปลบเอกสารแนบทีละรายการ
            foreach ($request->attachments_to_delete as $attachmentId) {
                try {
                    // ค้นหาเอกสารแนบที่ต้องการลบ
                    $attachment = $organizationalRisk->attachments()->findOrFail($attachmentId);
                    
                    // ลบไฟล์จาก storage
                    Storage::disk('public')->delete($attachment->file_path);
                    
                    // ลบข้อมูลจากฐานข้อมูล
                    $attachment->delete();
                    
                    // บันทึกล็อกสำหรับการตรวจสอบ
                    Log::info('ลบเอกสารแนบสำหรับความเสี่ยงองค์กรขณะอัปเดต', [
                        'risk_id' => $organizationalRisk->id,
                        'attachment_id' => $attachmentId,
                        'file_name' => $attachment->file_name,
                        'user' => Auth::check() ? Auth::user()->name : null,
                        'timestamp' => now()->format('Y-m-d H:i:s')
                    ]);
                } catch (\Exception $e) {
                    // บันทึกล็อกกรณีเกิดข้อผิดพลาด แต่ไม่หยุดการทำงาน
                    Log::error('เกิดข้อผิดพลาดในการลบเอกสารแนบขณะอัปเดต', [
                        'risk_id' => $organizationalRisk->id,
                        'attachment_id' => $attachmentId,
                        'error' => $e->getMessage(),
                        'user' => Auth::check() ? Auth::user()->name : null,
                    ]);
                    // ในกรณีนี้เราไม่ throw exception เพื่อให้สามารถลบรายการอื่นต่อไปได้
                }
            }
        }
    }

    /**
     * ดาวน์โหลดเอกสารแนบของความเสี่ยงองค์กร
     * 
     * @param OrganizationalRisk $organizationalRisk ข้อมูลความเสี่ยงที่ต้องการดาวน์โหลดเอกสารแนบ
     * @param int $attachmentId ID ของเอกสารแนบที่ต้องการดาวน์โหลด
     * @return \Illuminate\Http\Response ไฟล์ดาวน์โหลด
     */
    public function downloadAttachment(OrganizationalRisk $organizationalRisk, $attachmentId)
    {
        try {
            // ค้นหาเอกสารแนบตาม ID ที่เชื่อมโยงกับความเสี่ยงนี้
            $attachment = $organizationalRisk->attachments()->findOrFail($attachmentId);
            
            // ตรวจสอบว่าไฟล์มีอยู่จริงในระบบ
            if (!Storage::disk('public')->exists($attachment->file_path)) {
                Log::error('ไม่พบไฟล์เอกสารแนบในระบบ', [
                    'risk_id' => $organizationalRisk->id,
                    'attachment_id' => $attachmentId,
                    'file_path' => $attachment->file_path,
                    'user' => Auth::check() ? Auth::user()->name : null
                ]);
                
                return response()->json([
                    'error' => 'ไม่พบไฟล์เอกสารแนบในระบบ'
                ], 404);
            }
            
            // บันทึกล็อกการดาวน์โหลด
            Log::info('ดาวน์โหลดเอกสารแนบ', [
                'risk_id' => $organizationalRisk->id,
                'attachment_id' => $attachmentId,
                'file_name' => $attachment->file_name,
                'user' => Auth::check() ? Auth::user()->name : null,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            // ส่งไฟล์กลับไปยังผู้ใช้เพื่อดาวน์โหลด
            return Storage::disk('public')->download(
                $attachment->file_path, 
                $attachment->file_name
            );
        } catch (\Exception $e) {
            // บันทึกล็อกกรณีเกิดข้อผิดพลาด
            Log::error('เกิดข้อผิดพลาดในการดาวน์โหลดเอกสารแนบ', [
                'risk_id' => $organizationalRisk->id,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : null
            ]);
            
            return response()->json([
                'error' => 'เกิดข้อผิดพลาดในการดาวน์โหลดเอกสารแนบ: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * แสดงเอกสารแนบของความเสี่ยงองค์กรในเบราว์เซอร์
     * 
     * @param OrganizationalRisk $organizationalRisk ข้อมูลความเสี่ยงที่ต้องการแสดงเอกสารแนบ
     * @param int $attachmentId ID ของเอกสารแนบที่ต้องการแสดง
     * @return \Illuminate\Http\Response ไฟล์สำหรับแสดงในเบราว์เซอร์
     */
    public function viewAttachment(OrganizationalRisk $organizationalRisk, $attachmentId)
    {
        $attachment = OrganizationalRiskAttachment::where('id', $attachmentId)
            ->where('organizational_risk_id', $organizationalRisk->id)
            ->firstOrFail();
        
        $path = 'public/' . $attachment->file_path;
        
        Log::info('ตรวจสอบไฟล์', [
            'file_path' => $attachment->file_path,
            'full_path' => $path,
            'exists' => Storage::exists($path),
            'real_path' => Storage::path($path)
        ]);
        
        if (!Storage::exists($path)) {
            return response()->json(['error' => 'ไม่พบไฟล์เอกสารแนบในระบบ'], 404);
        }
        
        return response()->file(Storage::path($path));
    }
}
