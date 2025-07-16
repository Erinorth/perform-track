<?php
/**
 * ไฟล์: app\Http\Controllers\RiskAssessmentController.php
 * คำอธิบาย: Controller สำหรับจัดการการประเมินความเสี่ยงในระบบประเมินความเสี่ยง
 * เทคโนโลยี: Laravel 12, Inertia.js, Vue 3
 * ทำหน้าที่: จัดการข้อมูลการประเมินความเสี่ยงและเอกสารแนบที่เกี่ยวข้อง
 * ความสัมพันธ์: เชื่อมโยงกับ RiskAssessment Model และส่งข้อมูลไปยัง Vue ผ่าน Inertia
 */

namespace App\Http\Controllers;

// นำเข้า Models และ Requests ที่เกี่ยวข้อง
use App\Models\RiskAssessment;  // โมเดลสำหรับจัดการข้อมูลการประเมินความเสี่ยง
use App\Models\LikelihoodCriterion;  // โมเดลสำหรับจัดการข้อมูลเกณฑ์โอกาส
use App\Models\ImpactCriterion;  // โมเดลสำหรับจัดการข้อมูลเกณฑ์ผลกระทบ
use App\Models\RiskAssessmentAttachment;  // โมเดลสำหรับจัดการข้อมูลเอกสารแนบ
use App\Models\DivisionRisk;  // โมเดลสำหรับจัดการข้อมูลความเสี่ยงระดับฝ่าย
use App\Http\Requests\StoreRiskAssessmentRequest;  // Form Request สำหรับตรวจสอบข้อมูลการเพิ่ม
use App\Http\Requests\UpdateRiskAssessmentRequest;  // Form Request สำหรับตรวจสอบข้อมูลการแก้ไข
use Illuminate\Http\Request;  // สำหรับจัดการคำขอจาก HTTP
use Illuminate\Support\Facades\Log;  // สำหรับบันทึก log การทำงาน
use Inertia\Inertia;  // เชื่อมต่อกับ Vue frontend
use Illuminate\Support\Facades\Auth;  // จัดการข้อมูลผู้ใช้ที่ล็อกอิน
use Illuminate\Support\Facades\Storage;  // จัดการไฟล์ในระบบ
use Illuminate\Support\Facades\DB;

class RiskAssessmentController extends Controller
{
    /**
     * แสดงรายการการประเมินความเสี่ยงทั้งหมด
     * ดึงข้อมูลพร้อมความสัมพันธ์และเรียงลำดับตามปีและช่วงการประเมิน
     * 
     * @return \Inertia\Response หน้า Vue พร้อมข้อมูลการประเมินความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลการประเมินความเสี่ยงทั้งหมด พร้อมโหลดความสัมพันธ์
        $assessments = RiskAssessment::with([
            'attachments',
            'divisionRisk',
            'divisionRisk.impactCriteria', // เพิ่มการดึงข้อมูลเกณฑ์ผลกระทบ
            'divisionRisk.likelihoodCriteria' // เพิ่มการดึงข้อมูลเกณฑ์โอกาส
            ])
            ->orderByDesc('assessment_year')
            ->orderByDesc('assessment_period')
            ->get();

        // ดึงข้อมูล division_risks พร้อมเกณฑ์
        $divisionRisks = DivisionRisk::with([
            'impactCriteria',
            'likelihoodCriteria'
        ])->orderBy('risk_name')->get();

        // บันทึก log การเข้าถึงหน้ารายการ
        Log::info('เข้าถึงรายการการประเมินความเสี่ยง', [
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'has_criteria_data' => $divisionRisks->first() ? 
                ($divisionRisks->first()->likelihoodCriteria->count() > 0 ? 'มี' : 'ไม่มี') : 'ไม่มีข้อมูล'
        ]);

        return Inertia::render('risk_assessment/RiskAssessment', [
            'assessments' => $assessments,
            'divisionRisks' => $divisionRisks
        ]);
    }

    /**
     * แสดงฟอร์มสำหรับสร้างการประเมินความเสี่ยงใหม่
     */
    public function create()
    {
        // ดึงข้อมูลความเสี่ยงระดับส่วนงานและองค์กร
        $divisionRisks = DivisionRisk::with('organizationalRisk')->get();
        
        // ดึงข้อมูลเกณฑ์โอกาสและผลกระทบสำหรับแต่ละความเสี่ยง
        $likelihoodCriteria = LikelihoodCriterion::all()->groupBy('division_risk_id');
        $impactCriteria = ImpactCriterion::all()->groupBy('division_risk_id');
        
        return Inertia::render('risk_assessment/RiskAssessmentForm', [
            'divisionRisks' => $divisionRisks,
            'likelihoodCriteria' => $likelihoodCriteria,
            'impactCriteria' => $impactCriteria
        ]);
    }

    /**
     * บันทึกข้อมูลการประเมินความเสี่ยงใหม่ลงฐานข้อมูล
     * 
     * @param \App\Http\Requests\StoreRiskAssessmentRequest $request คำขอที่ผ่านการตรวจสอบแล้ว
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function store(StoreRiskAssessmentRequest $request)
    {
        // เริ่ม transaction เพื่อให้มั่นใจว่าข้อมูลถูกบันทึกครบทุกส่วนหรือไม่ถูกบันทึกเลย
        DB::beginTransaction();
        
        try {
            // สร้างข้อมูลการประเมินความเสี่ยงใหม่โดยใช้ข้อมูลที่ผ่านการตรวจสอบแล้ว
            $assessment = RiskAssessment::create($request->validated());
            
            // จัดการเอกสารแนบที่ส่งมาพร้อมคำขอ
            $this->handleAttachments($request, $assessment);
            
            // บันทึกล็อกสำหรับการติดตามและตรวจสอบ
            Log::info('สร้างการประเมินความเสี่ยงใหม่', [
                'id' => $assessment->id,
                'assessment_year' => $assessment->assessment_year,
                'assessment_period' => $assessment->assessment_period,
                'user' => auth()->check() ? auth()->user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            // ยืนยันการทำรายการ
            DB::commit();
            
            // ดึงข้อมูลการประเมินความเสี่ยงทั้งหมดมาใหม่หลังจากบันทึกข้อมูล
            $assessments = RiskAssessment::with(['divisionRisk'])
                ->orderByDesc('assessment_year')
                ->orderByDesc('assessment_period')
                ->get();
                
            // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จและข้อมูลล่าสุด
            return redirect()->back()
                ->with('success', 'เพิ่มการประเมินความเสี่ยงเรียบร้อยแล้ว')
                ->with('assessments', $assessments);
                
        } catch (\Exception $e) {
            // ยกเลิกการทำรายการทั้งหมดหากเกิดข้อผิดพลาด
            DB::rollBack();
            
            Log::error('เกิดข้อผิดพลาดในการสร้างการประเมินความเสี่ยง', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดของการประเมินความเสี่ยง
     */
    public function show(RiskAssessment $riskAssessment)
    {
        // โหลดข้อมูลที่เกี่ยวข้อง
        $riskAssessment->load([
            'divisionRisk', 
            'divisionRisk.organizationalRisk',
            'divisionRisk.likelihoodCriteria',
            'divisionRisk.impactCriteria',
            'attachments'
        ]);
        
        return Inertia::render('risk_assessment/RiskAssessmentShow', [
            'riskAssessment' => $riskAssessment
        ]);
    }

    /**
     * แสดงฟอร์มสำหรับแก้ไขการประเมินความเสี่ยง
     */
    public function edit(RiskAssessment $riskAssessment)
    {
        // โหลดความสัมพันธ์ที่จำเป็น
        $riskAssessment->load(['divisionRisk', 'divisionRisk.organizationalRisk']);
        
        // ดึงข้อมูลที่จำเป็นสำหรับฟอร์มแก้ไข
        $divisionRisks = DivisionRisk::with('organizationalRisk')->get();
        $likelihoodCriteria = LikelihoodCriterion::all()->groupBy('division_risk_id');
        $impactCriteria = ImpactCriterion::all()->groupBy('division_risk_id');
        
        return Inertia::render('risk_assessment/RiskAssessmentEdit', [
            'riskAssessment' => $riskAssessment,
            'divisionRisks' => $divisionRisks,
            'likelihoodCriteria' => $likelihoodCriteria,
            'impactCriteria' => $impactCriteria
        ]);
    }

    /**
     * อัปเดตข้อมูลการประเมินความเสี่ยงที่มีอยู่
     * 
     * @param \App\Http\Requests\UpdateRiskAssessmentRequest $request คำขออัปเดตที่ผ่านการตรวจสอบแล้ว
     * @param \App\Models\RiskAssessment $riskAssessment ข้อมูลการประเมินความเสี่ยงที่ต้องการอัปเดต
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function update(UpdateRiskAssessmentRequest $request, RiskAssessment $riskAssessment)
    {
        // เริ่ม transaction
        DB::beginTransaction();
        
        try {
            // อัปเดตข้อมูลพื้นฐาน
            $riskAssessment->update([
                'assessment_year' => $request->assessment_year,
                'assessment_period' => $request->assessment_period,
                'likelihood_level' => $request->likelihood_level,
                'impact_level' => $request->impact_level,
                'risk_score' => $request->risk_score,
                'division_risk_id' => $request->division_risk_id,
                'notes' => $request->notes,
            ]);
            
            // จัดการไฟล์แนบและไฟล์ที่ต้องการลบ
            $this->handleAttachments($request, $riskAssessment);
            $this->handleAttachmentsToDelete($request, $riskAssessment);
            
            // ยืนยันการทำรายการ
            DB::commit();
                
            // ดึงข้อมูลที่อัปเดตเรียบร้อยแล้วพร้อมเอกสารแนบและความสัมพันธ์
            $updatedAssessment = RiskAssessment::with([
                'attachments',
                'divisionRisk'
            ])->find($riskAssessment->id);
            
            return redirect()->back()->with([
                'message' => 'อัปเดตข้อมูลการประเมินความเสี่ยงเรียบร้อยแล้ว',
                'updatedAssessment' => $updatedAssessment  // ส่งข้อมูลที่อัปเดตแล้วกลับไป
            ]);
        } catch (\Exception $e) {
            // ยกเลิกการทำรายการทั้งหมดหากเกิดข้อผิดพลาด
            DB::rollBack();
            
            Log::error('การอัปเดตการประเมินความเสี่ยงล้มเหลว: ' . $e->getMessage(), [
                'assessment_id' => $riskAssessment->id,
                'user' => auth()->user()->name ?? 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * ลบข้อมูลการประเมินความเสี่ยง (Soft Delete)
     * 
     * @param \App\Models\RiskAssessment $riskAssessment ข้อมูลการประเมินความเสี่ยงที่ต้องการลบ
     * @return \Illuminate\Http\RedirectResponse Redirect กลับพร้อมข้อความแจ้งผล
     */
    public function destroy(RiskAssessment $riskAssessment)
    {
        // เก็บข้อมูลเก่าไว้สำหรับการตรวจสอบและบันทึก log
        $oldData = $riskAssessment->toArray();
        
        // ดำเนินการลบข้อมูล (Soft Delete)
        $riskAssessment->delete();
        
        // บันทึก log สำหรับการตรวจสอบ
        Log::info('ลบการประเมินความเสี่ยง', [
            'id' => $oldData['id'],
            'assessment_year' => $oldData['assessment_year'] ?? null,
            'assessment_period' => $oldData['assessment_period'] ?? null,
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
        
        // กลับไปยังหน้าเดิมพร้อมข้อความแจ้งสำเร็จ
        return redirect()->back()->with('success', 'ลบการประเมินความเสี่ยงเรียบร้อยแล้ว');
    }


  /**
   * จัดการไฟล์แนบสำหรับการประเมินความเสี่ยง
   * 
   * @param \Illuminate\Http\Request $request คำขอพร้อมไฟล์แนบ
   * @param \App\Models\RiskAssessment $riskAssessment ข้อมูลการประเมินความเสี่ยงที่ต้องการแนบไฟล์
   * @return void
   */
  protected function handleAttachments(Request $request, RiskAssessment $riskAssessment)
  {
      // ตรวจสอบว่ามีไฟล์แนบหรือไม่
      if ($request->hasFile('attachments')) {
          foreach ($request->file('attachments') as $file) {
              // ตรวจสอบไฟล์และตั้งชื่อไฟล์
              $filename = uniqid() . '_' . $file->getClientOriginalName();
              
              // กำหนดพาธสำหรับเก็บไฟล์
              $path = $file->storeAs(
                  'risk_assessments/'.$riskAssessment->id, 
                  $filename, 
                  'public'
              );
              
              // สร้างข้อมูลเอกสารแนบในฐานข้อมูล
              RiskAssessmentAttachment::create([
                  'risk_assessment_id' => $riskAssessment->id,
                  'file_name' => $file->getClientOriginalName(),
                  'file_path' => $path,
                  'file_type' => $file->getMimeType(),
                  'file_size' => $file->getSize(),
              ]);
              
              // บันทึกล็อกการอัพโหลดไฟล์
              Log::info('อัพโหลดไฟล์แนบสำหรับการประเมินความเสี่ยง', [
                  'risk_assessment_id' => $riskAssessment->id, 
                  'file_name' => $file->getClientOriginalName(),
                  'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
              ]);
          }
      }
  }

  /**
   * จัดการลบเอกสารแนบที่ไม่ต้องการ
   * 
   * @param \Illuminate\Http\Request $request คำขอพร้อมรายการ ID ที่ต้องการลบ
   * @param \App\Models\RiskAssessment $riskAssessment ข้อมูลการประเมินความเสี่ยง
   * @return void
   */
  protected function handleAttachmentsToDelete(Request $request, RiskAssessment $riskAssessment)
  {
      // ตรวจสอบว่ามีรายการเอกสารแนบที่ต้องการลบหรือไม่
      if ($request->has('attachments_to_delete') && is_array($request->attachments_to_delete)) {
          foreach ($request->attachments_to_delete as $attachmentId) {
              // ค้นหาเอกสารแนบจาก ID
              $attachment = RiskAssessmentAttachment::find($attachmentId);
              
              if ($attachment && $attachment->risk_assessment_id === $riskAssessment->id) {
                  // ลบไฟล์จากพื้นที่เก็บข้อมูล
                  if (Storage::disk('public')->exists($attachment->file_path)) {
                      Storage::disk('public')->delete($attachment->file_path);
                  }
                  
                  // ลบข้อมูลจากฐานข้อมูล
                  $attachment->delete();
                  
                  // บันทึกล็อกการลบไฟล์
                  Log::info('ลบไฟล์แนบสำหรับการประเมินความเสี่ยง', [
                      'attachment_id' => $attachmentId,
                      'risk_assessment_id' => $riskAssessment->id,
                      'file_name' => $attachment->file_name,
                      'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                  ]);
              }
          }
      }
  }

  /**
   * ดาวน์โหลดเอกสารแนบของการประเมินความเสี่ยง
   * 
   * @param \App\Models\RiskAssessment $riskAssessment ข้อมูลการประเมินความเสี่ยง
   * @param \App\Models\RiskAssessmentAttachment $attachment ข้อมูลเอกสารแนบ
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   */
  public function downloadAttachment(RiskAssessment $riskAssessment, $attachmentId)
    {
        try {
            $attachment = $riskAssessment->attachments()->findOrFail($attachmentId);
            if (!Storage::disk('public')->exists($attachment->file_path)) {
                Log::error('ไม่พบไฟล์เอกสารแนบในระบบ', [
                    'risk_assessment_id' => $riskAssessment->id,
                    'attachment_id' => $attachmentId,
                    'file_path' => $attachment->file_path,
                    'user' => Auth::check() ? Auth::user()->name : null
                ]);
                return response()->json([
                    'error' => 'ไม่พบไฟล์เอกสารแนบในระบบ'
                ], 404);
            }
            Log::info('ดาวน์โหลดเอกสารแนบการประเมินความเสี่ยง', [
                'risk_assessment_id' => $riskAssessment->id,
                'attachment_id' => $attachmentId,
                'file_name' => $attachment->file_name,
                'user' => Auth::check() ? Auth::user()->name : null,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            return Storage::disk('public')->download(
                $attachment->file_path, 
                $attachment->file_name
            );
        } catch (\Exception $e) {
            // บันทึกล็อกกรณีเกิดข้อผิดพลาด
            Log::error('เกิดข้อผิดพลาดในการดาวน์โหลดเอกสารแนบการประเมินความเสี่ยง', [
                'risk_assessment_id' => $riskAssessment->id,
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
   * แสดงเอกสารแนบในเบราว์เซอร์
   * 
   * @param \App\Models\RiskAssessment $riskAssessment ข้อมูลการประเมินความเสี่ยง
   * @param \App\Models\RiskAssessmentAttachment $attachment ข้อมูลเอกสารแนบ
   * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
   */
  public function viewAttachment(RiskAssessment $riskAssessment, $attachmentId)
  {
      $attachment = RiskAssessmentAttachment::where('id', $attachmentId)
            ->where('risk_assessment_id', $riskAssessment->id)
            ->firstOrFail();
        
        $path = $attachment->file_path;
        
        Log::info('ตรวจสอบไฟล์เอกสารแนบ', [
            'attachment_id' => $attachmentId,
            'risk_assessment_id' => $riskAssessment->id,
            'file_path' => $attachment->file_path,
            'file_name' => $attachment->file_name,
            'exists_in_storage' => Storage::disk('public')->exists($path),
            'real_path' => Storage::disk('public')->path($path),
            'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
        ]);
        
        // ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่โดยใช้ disk public โดยตรง
        if (!Storage::disk('public')->exists($path)) {
            Log::error('ไม่พบไฟล์เอกสารแนบ', [
                'path' => $path,
                'full_path' => Storage::disk('public')->path($path)
            ]);
            return response()->json(['error' => 'ไม่พบไฟล์เอกสารแนบในระบบ'], 404);
        }
        
        // ส่งไฟล์กลับไปแสดงในเบราว์เซอร์
        return response()->file(Storage::disk('public')->path($path));
  }
}