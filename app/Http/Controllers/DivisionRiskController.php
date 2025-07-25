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
use App\Models\OrganizationalRisk;  // โมเดลสำหรับจัดการข้อมูลความเสี่ยงระดับหน่วยงาน
use App\Models\LikelihoodCriterion;  // โมเดลสำหรับจัดการข้อมูลเกณฑ์ความเสี่ยง
use App\Models\ImpactCriterion;  // โมเดลสำหรับจัดการข้อมูลเกณฑ์ผลกระทบ
use App\Http\Requests\StoreDivisionRiskRequest;  // Form Request สำหรับตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateDivisionRiskRequest;  // Form Request สำหรับตรวจสอบข้อมูลการแก้ไข
use Illuminate\Http\Request;  // สำหรับจัดการคำขอจาก HTTP
use Illuminate\Support\Facades\Log;  // สำหรับบันทึก log การทำงาน
use Inertia\Inertia;  // เชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // จัดการข้อมูลผู้ใช้ที่ล็อกอิน
use Illuminate\Support\Facades\Storage;  // จัดการไฟล์ในระบบ
use Illuminate\Support\Facades\DB;

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
        $risks = DivisionRisk::with(['riskAssessments',
            'attachments',
            'organizationalRisk',
            'likelihoodCriteria',
            'impactCriteria'  
            ])
            ->orderBy('risk_name')  // เรียงตามชื่อความเสี่ยง
            ->get();  // ดึงข้อมูลทั้งหมด

        $organizationalRisks = OrganizationalRisk::orderBy('risk_name')->get();

        // บันทึก log การเข้าถึงหน้ารายการความเสี่ยง เพื่อติดตามการใช้งาน
        Log::info('เข้าถึงรายการความเสี่ยงระดับฝ่าย', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // ส่งข้อมูลไปยังหน้า Vue ผ่าน Inertia
        return Inertia::render('division_risk/DivisionRisk', [
            'risks' => $risks,  // ส่งข้อมูลความเสี่ยงไปยัง Vue component
            'organizationalRisks' => $organizationalRisks
        ]);
    }

    /**
     * แสดงหน้าสร้างความเสี่ยงใหม่
     */
    public function create()
    {
        Log::info('เข้าถึงหน้าสร้างความเสี่ยงฝ่ายใหม่', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        return Inertia::render('division_risk/DivisionRiskForm',[
            'organizationalRisks' => OrganizationalRisk::orderBy('risk_name')->get(),  // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมด
        ]);
    }

    /**
     * บันทึกข้อมูลความเสี่ยงระดับองค์กรใหม่ลงฐานข้อมูล
     * 
     * @param \App\Http\Requests\StoreDivisionRiskRequest $request คำขอที่ผ่านการตรวจสอบแล้ว
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function store(StoreDivisionRiskRequest $request)
    {
        // เริ่ม transaction เพื่อให้มั่นใจว่าข้อมูลถูกบันทึกครบทุกส่วนหรือไม่ถูกบันทึกเลย
        DB::beginTransaction();
        
        try {
            // สร้างข้อมูลความเสี่ยงใหม่โดยใช้ข้อมูลที่ผ่านการตรวจสอบแล้ว
            $risk = DivisionRisk::create($request->validated());
            
            // จัดการเอกสารแนบที่ส่งมาพร้อมคำขอ
            $this->handleAttachments($request, $risk);
            
            // จัดการเกณฑ์โอกาสเกิดและผลกระทบ (ถ้ามี)
            if ($request->has('likelihood_criteria')) {
                $this->handleLikelihoodCriteria($risk, $request->likelihood_criteria);
            }
            
            if ($request->has('impact_criteria')) {
                $this->handleImpactCriteria($risk, $request->impact_criteria);
            }
            
            // บันทึกล็อกสำหรับการติดตามและตรวจสอบ
            Log::info('สร้างความเสี่ยงฝ่ายใหม่', [
                'id' => $risk->id, 
                'name' => $risk->risk_name, 
                'user' => auth()->check() ? auth()->user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            // ยืนยันการทำรายการ
            DB::commit();
            
            // ดึงข้อมูลความเสี่ยงทั้งหมดมาใหม่หลังจากบันทึกข้อมูล
            $risks = DivisionRisk::with(['riskAssessments', 'likelihoodCriteria', 'impactCriteria'])
                ->orderBy('risk_name')
                ->get();
                
            // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลล่าสุด
            return redirect()->back()
                ->with('success', 'เพิ่มความเสี่ยงฝ่ายเรียบร้อยแล้ว')
                ->with('risks', $risks);
                
        } catch (\Exception $e) {
            // ยกเลิกการทำรายการทั้งหมดหากเกิดข้อผิดพลาด
            DB::rollBack();
            
            Log::error('เกิดข้อผิดพลาดในการสร้างความเสี่ยงฝ่าย', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดของความเสี่ยงฝ่ายที่เลือก
     *
     * @param  \App\Models\DivisionRisk  $divisionRisk
     * @return \Inertia\Response
     */
    public function show(DivisionRisk $divisionRisk)
    {
        // บันทึกข้อมูลการเข้าดูรายละเอียดความเสี่ยง
        Log::info('User viewing division risk details', [
            'division_risk_id' => $divisionRisk->id,
            'risk_name' => $divisionRisk->risk_name,
            'user_id' => auth()->id() ?? 'guest'
        ]);

        // โหลดความสัมพันธ์ที่จำเป็นสำหรับการแสดงรายละเอียด
        $divisionRisk->load([
            'organizationalRisk', 
            'likelihoodCriteria', 
            'impactCriteria',
            'attachments',
            'riskAssessments' => function ($query) {
                // แก้ไขจุดนี้: เปลี่ยนจาก orderBy('assessment_date', 'desc') 
                // เป็น orderByDesc('assessment_year')->orderByDesc('assessment_period')
                $query->orderByDesc('assessment_year')->orderByDesc('assessment_period');
            }
        ]);

        // ส่งข้อมูลไปยังหน้า Vue พร้อมแยก props เฉพาะสำหรับแต่ละส่วน
        return Inertia::render('division_risk/DivisionRiskShow', [
            'risk' => $divisionRisk,
            'organizational_risk' => $divisionRisk->organizationalRisk,
            'likelihood_criteria' => $divisionRisk->likelihoodCriteria,
            'impact_criteria' => $divisionRisk->impactCriteria,
            'assessments' => $divisionRisk->riskAssessments
        ]);
    }

    /**
     * แสดงฟอร์มสำหรับแก้ไขความเสี่ยงระดับหน่วยงาน
     * ดึงข้อมูลความเสี่ยงที่ต้องการแก้ไขและข้อมูลความเสี่ยงระดับองค์กรเพื่อให้เลือก
     *
     * @param \App\Models\DivisionRisk $divisionRisk ข้อมูลความเสี่ยงที่ต้องการแก้ไข
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลที่จำเป็นสำหรับการแก้ไข
     */
    public function edit(DivisionRisk $divisionRisk)
    {
        // บันทึกข้อมูลการเข้าถึงฟอร์มแก้ไขความเสี่ยง
        Log::info('เข้าถึงฟอร์มแก้ไขความเสี่ยงระดับหน่วยงาน', [
            'division_risk_id' => $divisionRisk->id,
            'risk_name' => $divisionRisk->risk_name,
            'user_id' => auth()->id() ?? 'guest',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // โหลดความสัมพันธ์ที่จำเป็นสำหรับการแก้ไข
        $divisionRisk->load([
            'organizationalRisk', 
            'attachments', 
            'likelihoodCriteria', 
            'impactCriteria'
        ]);

        // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมดเพื่อให้ผู้ใช้เลือก
        $organizationalRisks = OrganizationalRisk::orderBy('risk_name')->get();

        // ส่งข้อมูลไปยังหน้า Vue พร้อมข้อมูลที่จำเป็นสำหรับการแก้ไข
        return Inertia::render('division_risk/DivisionRiskEdit', [
            'risk' => $divisionRisk,
            'organizationalRisks' => $organizationalRisks,
            'attachments' => $divisionRisk->attachments,
            'likelihoodCriteria' => $divisionRisk->likelihoodCriteria,
            'impactCriteria' => $divisionRisk->impactCriteria
        ]);
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
        // เริ่ม transaction
        DB::beginTransaction();
        
        try {
            // อัปเดตข้อมูลพื้นฐาน
            $divisionRisk->update([
                'risk_name' => $request->risk_name,
                'description' => $request->description,
                'organizational_risk_id' => $request->organizational_risk_id,
            ]);
            
            // จัดการไฟล์แนบและไฟล์ที่ต้องการลบ
            $this->handleAttachments($request, $divisionRisk);
            $this->handleAttachmentsToDelete($request, $divisionRisk);
            
            // จัดการเกณฑ์โอกาสเกิดและผลกระทบ (ถ้ามี)
            if ($request->has('likelihood_criteria')) {
                $this->handleLikelihoodCriteria($divisionRisk, $request->likelihood_criteria);
            }
            
            if ($request->has('impact_criteria')) {
                $this->handleImpactCriteria($divisionRisk, $request->impact_criteria);
            }
            
            // ยืนยันการทำรายการ
            DB::commit();
                
            // ดึงข้อมูลที่อัปเดตเรียบร้อยแล้วพร้อมเอกสารแนบและเกณฑ์
            $updatedRisk = DivisionRisk::with([
                'attachments', 
                'likelihoodCriteria', 
                'impactCriteria'
            ])->find($divisionRisk->id);
            
            return redirect()->back()->with([
                'message' => 'อัปเดตข้อมูลความเสี่ยงเรียบร้อยแล้ว',
                'updatedRisk' => $updatedRisk  // ส่งข้อมูลที่อัปเดตแล้วกลับไป
            ]);
        } catch (\Exception $e) {
            // ยกเลิกการทำรายการทั้งหมดหากเกิดข้อผิดพลาด
            DB::rollBack();
            
            Log::error('การอัปเดตความเสี่ยงล้มเหลว: ' . $e->getMessage(), [
                'risk_id' => $divisionRisk->id,
                'user' => auth()->user()->name ?? 'ไม่ระบุ',
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
     * จัดการเกณฑ์โอกาสเกิด (likelihood criteria)
     * 
     * @param DivisionRisk $divisionRisk ข้อมูลความเสี่ยงระดับฝ่าย
     * @param array $likelihoodCriteria ข้อมูลเกณฑ์โอกาสเกิด
     * @return void
     */
    private function handleLikelihoodCriteria(DivisionRisk $divisionRisk, array $likelihoodCriteria): void
    {
        // ลบข้อมูลเกณฑ์เดิม (ถ้ามี)
        $divisionRisk->likelihoodCriteria()->delete();
        
        // บันทึกข้อมูลเกณฑ์ใหม่
        foreach ($likelihoodCriteria as $criteria) {
            $divisionRisk->likelihoodCriteria()->create([
                'level' => $criteria['level'],
                'name' => $criteria['name'],
                'description' => $criteria['description'] ?? null
            ]);
        }
        
        // บันทึกล็อกสำหรับการตรวจสอบ
        Log::info('บันทึกเกณฑ์โอกาสเกิดความเสี่ยง', [
            'division_risk_id' => $divisionRisk->id,
            'risk_name' => $divisionRisk->risk_name,
            'criteria_count' => count($likelihoodCriteria),
            'user' => auth()->check() ? auth()->user()->name : 'ไม่ระบุ'
        ]);
    }
    
    /**
     * จัดการเกณฑ์ผลกระทบ (impact criteria)
     * 
     * @param DivisionRisk $divisionRisk ข้อมูลความเสี่ยงระดับฝ่าย
     * @param array $impactCriteria ข้อมูลเกณฑ์ผลกระทบ
     * @return void
     */
    private function handleImpactCriteria(DivisionRisk $divisionRisk, array $impactCriteria): void
    {
        // ลบข้อมูลเกณฑ์เดิม (ถ้ามี)
        $divisionRisk->impactCriteria()->delete();
        
        // บันทึกข้อมูลเกณฑ์ใหม่
        foreach ($impactCriteria as $criteria) {
            $divisionRisk->impactCriteria()->create([
                'level' => $criteria['level'],
                'name' => $criteria['name'],
                'description' => $criteria['description'] ?? null
            ]);
        }
        
        // บันทึกล็อกสำหรับการตรวจสอบ
        Log::info('บันทึกเกณฑ์ผลกระทบความเสี่ยง', [
            'division_risk_id' => $divisionRisk->id,
            'risk_name' => $divisionRisk->risk_name,
            'criteria_count' => count($impactCriteria),
            'user' => auth()->check() ? auth()->user()->name : 'ไม่ระบุ'
        ]);
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