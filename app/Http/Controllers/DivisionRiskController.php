<?php
/**
 * ไฟล์: app\Http\Controllers\DivisionRiskController.php
 * คำอธิบาย: Controller สำหรับจัดการความเสี่ยงระดับฝ่ายในระบบประเมินความเสี่ยง
 * เทคโนโลยี: Laravel 12, Inertia.js, Vue 3
 * ทำหน้าที่: จัดการข้อมูลความเสี่ยงระดับฝ่ายและเอกสารแนบที่เกี่ยวข้อง
 * ความสัมพันธ์: เชื่อมโยงกับ DivisionRisk Model และส่งข้อมูลไปยัง Vue ผ่าน Inertia
 */

namespace App\Http\Controllers;

// นำเข้า Models และ Requests ที่เกี่ยวข้อง
use App\Models\DivisionRisk;  // โมเดลสำหรับจัดการข้อมูลความเสี่ยงระดับฝ่าย
use App\Models\DivisionRiskAttachment;  // โมเดลสำหรับจัดการข้อมูลเอกสารแนบ
use App\Http\Requests\StoreDivisionRiskRequest;  // Form Request สำหรับตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateDivisionRiskRequest;  // Form Request สำหรับตรวจสอบข้อมูลการแก้ไข
use Illuminate\Http\Request;  // สำหรับจัดการคำขอจาก HTTP
use Illuminate\Support\Facades\Log;  // สำหรับบันทึก log การทำงาน
use Inertia\Inertia;  // เชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // จัดการข้อมูลผู้ใช้ที่ล็อกอิน
use Illuminate\Support\Facades\Storage;  // จัดการไฟล์ในระบบ

class DivisionRiskController extends Controller
{
    /**
     * แสดงรายการความเสี่ยงระดับฝ่ายทั้งหมด
     * ดึงข้อมูลพร้อมความสัมพันธ์และเรียงลำดับตามชื่อความเสี่ยง
     * 
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลความเสี่ยงระดับฝ่ายทั้งหมด พร้อมโหลดความสัมพันธ์
        $risks = DivisionRisk::with(['riskAssessments', 'attachments', 'organizationalRisk'])
            ->orderBy('risk_name')  // เรียงตามชื่อความเสี่ยง
            ->get();  // ดึงข้อมูลทั้งหมด

        // บันทึก log การเข้าถึงหน้ารายการความเสี่ยง เพื่อติดตามการใช้งาน
        Log::info('เข้าถึงรายการความเสี่ยงระดับฝ่าย', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // ส่งข้อมูลไปยังหน้า Vue ผ่าน Inertia
        return Inertia::render('division_risk/DivisionRisk', [
            'risks' => $risks  // ส่งข้อมูลความเสี่ยงไปยัง Vue component
        ]);
    }

    /**
     * บันทึกข้อมูลความเสี่ยงระดับฝ่ายใหม่ลงฐานข้อมูล
     * 
     * @param \App\Http\Requests\StoreDivisionRiskRequest $request คำขอที่ผ่านการตรวจสอบแล้ว
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function store(StoreDivisionRiskRequest $request)
    {
        // สร้างข้อมูลความเสี่ยงใหม่โดยใช้ข้อมูลที่ผ่านการตรวจสอบแล้ว
        $risk = DivisionRisk::create($request->validated());
        
        // จัดการเอกสารแนบที่ส่งมาพร้อมคำขอ
        $this->handleAttachments($request, $risk);
        
        // บันทึกล็อกสำหรับการติดตามและตรวจสอบ
        Log::info('สร้างความเสี่ยงฝ่ายใหม่', [
            'id' => $risk->id, 
            'name' => $risk->risk_name, 
            'user' => Auth::check() ? Auth::user()->name : null,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // ดึงข้อมูลความเสี่ยงทั้งหมดมาใหม่หลังจากบันทึกข้อมูล
        $risks = DivisionRisk::with('riskAssessments')
            ->orderBy('risk_name')
            ->get();
            
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลล่าสุด
        return redirect()->back()
            ->with('success', 'เพิ่มความเสี่ยงฝ่ายเรียบร้อยแล้ว')
            ->with('risks', $risks);
    }

    /**
     * อัปเดตข้อมูลความเสี่ยงระดับฝ่ายที่มีอยู่
     * 
     * @param \App\Http\Requests\UpdateDivisionRiskRequest $request คำขออัปเดตที่ผ่านการตรวจสอบแล้ว
     * @param \App\Models\DivisionRisk $divisionRisk ข้อมูลความเสี่ยงที่ต้องการอัปเดต
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function update(UpdateDivisionRiskRequest $request, DivisionRisk $divisionRisk)
    {
        try {
            // อัปเดตข้อมูลพื้นฐาน
            $divisionRisk->update([
                'risk_name' => $request->risk_name,
                'description' => $request->description,
                'organizational_risk_id' => $request->organizational_risk_id,
            ]);
            
            // จัดการไฟล์แนบเสมอ โดยไม่ต้องตรวจสอบว่ามีไฟล์หรือไม่
            $this->handleAttachments($request, $divisionRisk);
            
            // จัดการไฟล์ที่ต้องการลบ
            if ($request->has('attachments_to_delete') && is_array($request->attachments_to_delete)) {
                foreach ($request->attachments_to_delete as $attachmentId) {
                    $attachment = DivisionRiskAttachment::find($attachmentId);
                    if ($attachment && $attachment->division_risk_id == $divisionRisk->id) {
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
            $updatedRisk = DivisionRisk::with('attachments')->find($divisionRisk->id);
            
            return redirect()->back()->with([
                'message' => 'อัปเดตข้อมูลความเสี่ยงเรียบร้อยแล้ว',
                'updatedRisk' => $updatedRisk  // ส่งข้อมูลที่อัปเดตแล้วกลับไป
            ]);
        } catch (\Exception $e) {
            Log::error('การอัปเดตความเสี่ยงล้มเหลว: ' . $e->getMessage(), [
                'risk_id' => $divisionRisk->id,
                'user' => Auth::user()->name ?? 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับฝ่าย (Soft Delete)
     * ตรวจสอบเงื่อนไขที่ไม่อนุญาตให้ลบก่อนดำเนินการ
     * 
     * @param \App\Models\DivisionRisk $divisionRisk ข้อมูลความเสี่ยงที่ต้องการลบ
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function destroy(DivisionRisk $divisionRisk)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบและบันทึก log
        $oldData = $divisionRisk->toArray();
        
        // ตรวจสอบว่ามีการประเมินความเสี่ยงที่เชื่อมโยงกับความเสี่ยงนี้หรือไม่
        $hasRiskAssessments = $divisionRisk->riskAssessments()->exists();
        
        // ป้องกันการลบข้อมูลที่มีการเชื่อมโยงกับข้อมูลอื่น
        if ($hasRiskAssessments) {
            return redirect()->back()->with('error', 'ไม่สามารถลบความเสี่ยงนี้ได้เนื่องจากมีการประเมินความเสี่ยงที่เชื่อมโยงอยู่');
        }
        
        // ดำเนินการลบข้อมูล (Soft Delete)
        $divisionRisk->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับฝ่าย', [
            'id' => $oldData['id'],
            'name' => $oldData['risk_name'],
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับฝ่ายเรียบร้อยแล้ว');
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับฝ่ายหลายรายการพร้อมกัน (Bulk Delete)
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
            'ids.*' => 'integer|exists:division_risks,id'  // แต่ละ ID ต้องเป็นตัวเลขและมีอยู่ในฐานข้อมูล
        ]);
        
        // ดึง IDs ที่ต้องการลบจากข้อมูลที่ตรวจสอบแล้ว
        $ids = $validated['ids'];
        
        // ตรวจสอบว่ามีความเสี่ยงใดบ้างที่มีการประเมินความเสี่ยงเชื่อมโยงอยู่
        $risksWithDependencies = DivisionRisk::whereIn('id', $ids)
            ->whereHas('riskAssessments')  // มีความสัมพันธ์กับตาราง risk_assessments
            ->pluck('id')  // ดึงเฉพาะ id
            ->toArray();  // แปลงเป็น array
        
        // ถ้ามีความเสี่ยงที่ไม่สามารถลบได้เนื่องจากมีการเชื่อมโยง
        if (!empty($risksWithDependencies)) {
            // กรองเอาเฉพาะ ID ที่สามารถลบได้
            $idsToDelete = array_diff($ids, $risksWithDependencies);
            
            // สร้างข้อความแจ้งเตือนตามสถานการณ์
            $errorMessage = count($risksWithDependencies) === count($ids)
                ? 'ไม่สามารถลบความเสี่ยงที่เลือกได้เนื่องจากมีการประเมินความเสี่ยงที่เชื่อมโยงอยู่'
                : 'ไม่สามารถลบบางรายการได้เนื่องจากมีการประเมินความเสี่ยงที่เชื่อมโยงอยู่';
            
            // ถ้าไม่มีรายการที่สามารถลบได้เลย
            if (empty($idsToDelete)) {
                return response()->json(['error' => $errorMessage], 422);
            }
            
            // อัปเดตรายการที่จะลบให้เหลือเฉพาะรายการที่สามารถลบได้
            $ids = $idsToDelete;
        }
        
        // ลบข้อมูลตามรายการที่สามารถลบได้
        $deletedCount = DivisionRisk::whereIn('id', $ids)->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบความเสี่ยงระดับฝ่ายหลายรายการ', [
            'deleted_count' => $deletedCount,
            'requested_ids' => $validated['ids'],
            'actual_deleted_ids' => $ids,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // สร้างข้อความแจ้งผลลัพธ์
        $successMessage = 'ลบความเสี่ยงระดับฝ่ายจำนวน ' . $deletedCount . ' รายการเรียบร้อยแล้ว';
        
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
        $risks = DivisionRisk::orderBy('risk_name')
            ->get();
        
        // กรณีลบได้ทั้งหมด ส่งข้อมูลกลับไปยังหน้าเดิม
        return redirect()->back()
            ->with('success', $successMessage)
            ->with('risks', $risks)
            ->with('deleted_count', $deletedCount);
    }

    /**
     * ประมวลผลไฟล์เอกสารแนบเดี่ยว
     * เมธอดพื้นฐานสำหรับการจัดการไฟล์เดียว ใช้ร่วมกันระหว่างเมธอดอื่นๆ
     * เพื่อลดความซ้ำซ้อนของโค้ด
     * 
     * @param \Illuminate\Http\UploadedFile $file ไฟล์ที่อัปโหลด
     * @param \App\Models\DivisionRisk $risk ความเสี่ยงที่เชื่อมโยงกับไฟล์
     * @param string $storagePath พาธสำหรับจัดเก็บไฟล์ (ค่าเริ่มต้น: attachments/division-risks)
     * @return array ข้อมูลเอกสารแนบที่บันทึกแล้ว
     */
    private function processAttachmentFile($file, $risk, $storagePath = 'attachments/division-risks')
    {
        // เก็บไฟล์ในพื้นที่จัดเก็บสาธารณะ
        $path = $file->store($storagePath, 'public');
        
        // สร้างเอกสารแนบในฐานข้อมูล
        $attachment = $risk->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize()
        ]);
        
        return $attachment->toArray();
    }

    /**
     * จัดการเอกสารแนบหลายไฟล์สำหรับความเสี่ยงฝ่าย
     * 
     * @param mixed $request คำขอที่มีไฟล์แนบหลายไฟล์
     * @param DivisionRisk $risk ข้อมูลความเสี่ยง
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
                    DivisionRiskAttachment::create([
                        'division_risk_id' => $risk->id,
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
     * เพิ่มเอกสารแนบเดี่ยวสำหรับความเสี่ยงฝ่าย (API endpoint)
     * 
     * @param Request $request คำขอที่มีไฟล์แนบเดียว
     * @param DivisionRisk $risk ข้อมูลความเสี่ยง
     * @return \Illuminate\Http\JsonResponse ผลลัพธ์การดำเนินการในรูปแบบ JSON
     */
    public function storeAttachment(Request $request, DivisionRisk $risk)
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
                'division_risk_attachments'
            );
            
            // บันทึก log สำหรับติดตาม
            Log::info('เพิ่มเอกสารแนบสำหรับความเสี่ยงฝ่าย', [
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
                'risk_id' => $divisionRisk->id,
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
     * ลบเอกสารแนบของความเสี่ยงฝ่าย
     * 
     * @param DivisionRisk $divisionRisk ข้อมูลความเสี่ยงที่ต้องการลบเอกสารแนบ
     * @param int $attachmentId ID ของเอกสารแนบที่ต้องการลบ
     * @return \Illuminate\Http\JsonResponse ผลลัพธ์การดำเนินการในรูปแบบ JSON
     */
    public function destroyAttachment(DivisionRisk $divisionRisk, $attachmentId)
    {
        try {
            // ค้นหาเอกสารแนบตาม ID ที่เชื่อมโยงกับความเสี่ยงนี้
            $attachment = $divisionRisk->attachments()->findOrFail($attachmentId);
            
            // ลบไฟล์จาก storage
            Storage::disk('public')->delete($attachment->file_path);
            
            // ลบข้อมูลจากฐานข้อมูล
            $attachment->delete();
            
            // บันทึกล็อกสำหรับการตรวจสอบ
            Log::info('ลบเอกสารแนบสำหรับความเสี่ยงฝ่าย', [
                'risk_id' => $divisionRisk->id,
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
                'risk_id' => $divisionRisk->id,
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
     * @param DivisionRisk $divisionRisk ข้อมูลความเสี่ยงที่ต้องการลบเอกสารแนบ
     * @return void
     */
    private function handleAttachmentsToDelete($request, $divisionRisk)
    {
        // ตรวจสอบว่ามีรายการเอกสารแนบที่ต้องการลบหรือไม่
        if ($request->has('attachments_to_delete') && is_array($request->attachments_to_delete)) {
            // วนลูปลบเอกสารแนบทีละรายการ
            foreach ($request->attachments_to_delete as $attachmentId) {
                try {
                    // ค้นหาเอกสารแนบที่ต้องการลบ
                    $attachment = $divisionRisk->attachments()->findOrFail($attachmentId);
                    
                    // ลบไฟล์จาก storage
                    Storage::disk('public')->delete($attachment->file_path);
                    
                    // ลบข้อมูลจากฐานข้อมูล
                    $attachment->delete();
                    
                    // บันทึกล็อกสำหรับการตรวจสอบ
                    Log::info('ลบเอกสารแนบสำหรับความเสี่ยงฝ่ายขณะอัปเดต', [
                        'risk_id' => $divisionRisk->id,
                        'attachment_id' => $attachmentId,
                        'file_name' => $attachment->file_name,
                        'user' => Auth::check() ? Auth::user()->name : null,
                        'timestamp' => now()->format('Y-m-d H:i:s')
                    ]);
                } catch (\Exception $e) {
                    // บันทึกล็อกกรณีเกิดข้อผิดพลาด แต่ไม่หยุดการทำงาน
                    Log::error('เกิดข้อผิดพลาดในการลบเอกสารแนบขณะอัปเดต', [
                        'risk_id' => $divisionRisk->id,
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
     * ดาวน์โหลดเอกสารแนบของความเสี่ยงฝ่าย
     * 
     * @param DivisionRisk $divisionRisk ข้อมูลความเสี่ยงที่ต้องการดาวน์โหลดเอกสารแนบ
     * @param int $attachmentId ID ของเอกสารแนบที่ต้องการดาวน์โหลด
     * @return \Illuminate\Http\Response ไฟล์ดาวน์โหลด
     */
    public function downloadAttachment(DivisionRisk $divisionRisk, $attachmentId)
    {
        try {
            // ค้นหาเอกสารแนบตาม ID ที่เชื่อมโยงกับความเสี่ยงนี้
            $attachment = $divisionRisk->attachments()->findOrFail($attachmentId);
            
            // ตรวจสอบว่าไฟล์มีอยู่จริงในระบบ
            if (!Storage::disk('public')->exists($attachment->file_path)) {
                Log::error('ไม่พบไฟล์เอกสารแนบในระบบ', [
                    'risk_id' => $divisionRisk->id,
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
                'risk_id' => $divisionRisk->id,
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
                'risk_id' => $divisionRisk->id,
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
     * แสดงเอกสารแนบของความเสี่ยงฝ่ายในเบราว์เซอร์
     * 
     * @param DivisionRisk $divisionRisk ข้อมูลความเสี่ยงที่ต้องการแสดงเอกสารแนบ
     * @param int $attachmentId ID ของเอกสารแนบที่ต้องการแสดง
     * @return \Illuminate\Http\Response ไฟล์สำหรับแสดงในเบราว์เซอร์
     */
    public function viewAttachment(DivisionRisk $divisionRisk, $attachmentId)
    {
        $attachment = DivisionRiskAttachment::where('id', $attachmentId)
            ->where('division_risk_id', $divisionRisk->id)
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