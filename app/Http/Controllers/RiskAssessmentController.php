<?php

namespace App\Http\Controllers;

use App\Models\RiskAssessment;
use App\Models\DivisionRisk;
use App\Models\LikelihoodCriterion;
use App\Models\ImpactCriterion;
use App\Http\Requests\StoreRiskAssessmentRequest;
use App\Http\Requests\UpdateRiskAssessmentRequest;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class RiskAssessmentController extends Controller
{
    /**
     * แสดงรายการการประเมินความเสี่ยงทั้งหมด
     */
    public function index()
    {
        // ดึงข้อมูลการประเมินความเสี่ยงพร้อมความสัมพันธ์กับแผนกและองค์กร
        $riskAssessments = RiskAssessment::with(['divisionRisk', 'divisionRisk.organizationalRisk'])
            ->orderBy('assessment_date', 'desc')
            ->paginate(10);
        
        Log::info('Risk assessment page accessed', ['user_id' => Auth::id()]);
        
        // ส่งข้อมูลไปยัง Vue ผ่าน Inertia
        return Inertia::render('risk_assessment/Index', [
            'riskAssessments' => $riskAssessments
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
        
        return Inertia::render('risk_assessment/Create', [
            'divisionRisks' => $divisionRisks,
            'likelihoodCriteria' => $likelihoodCriteria,
            'impactCriteria' => $impactCriteria
        ]);
    }

    /**
     * บันทึกการประเมินความเสี่ยงใหม่
     */
    public function store(StoreRiskAssessmentRequest $request)
    {
        try {
            // สร้างการประเมินความเสี่ยงใหม่
            $riskAssessment = RiskAssessment::create($request->validated());
            
            // บันทึก log
            Log::info('Risk assessment created', [
                'id' => $riskAssessment->id, 
                'user_id' => Auth::id(),
                'division_risk_id' => $request->division_risk_id
            ]);
            
            return redirect()->route('risk-assessments.index')
                ->with('message', 'สร้างการประเมินความเสี่ยงสำเร็จ');
        } catch (\Exception $e) {
            Log::error('Failed to create risk assessment', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการสร้างการประเมินความเสี่ยง');
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
            'divisionRisk.impactCriteria'
        ]);
        
        return Inertia::render('risk_assessment/Show', [
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
        
        return Inertia::render('risk_assessment/Edit', [
            'riskAssessment' => $riskAssessment,
            'divisionRisks' => $divisionRisks,
            'likelihoodCriteria' => $likelihoodCriteria,
            'impactCriteria' => $impactCriteria
        ]);
    }

    /**
     * อัพเดทการประเมินความเสี่ยงที่มีอยู่
     */
    public function update(UpdateRiskAssessmentRequest $request, RiskAssessment $riskAssessment)
    {
        try {
            // อัพเดทข้อมูล
            $riskAssessment->update($request->validated());
            
            // บันทึก log
            Log::info('Risk assessment updated', [
                'id' => $riskAssessment->id, 
                'user_id' => Auth::id(),
                'changes' => $riskAssessment->getChanges()
            ]);
            
            return redirect()->route('risk-assessments.index')
                ->with('message', 'อัพเดทการประเมินความเสี่ยงสำเร็จ');
        } catch (\Exception $e) {
            Log::error('Failed to update risk assessment', [
                'id' => $riskAssessment->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการอัพเดทการประเมินความเสี่ยง');
        }
    }

    /**
     * ลบการประเมินความเสี่ยง
     */
    public function destroy(RiskAssessment $riskAssessment)
    {
        try {
            $id = $riskAssessment->id;
            $divisionRiskId = $riskAssessment->division_risk_id;
            
            // ลบข้อมูล
            $riskAssessment->delete();
            
            // บันทึก log
            Log::info('Risk assessment deleted', [
                'id' => $id, 
                'division_risk_id' => $divisionRiskId,
                'user_id' => Auth::id()
            ]);
            
            return redirect()->route('risk-assessments.index')
                ->with('message', 'ลบการประเมินความเสี่ยงสำเร็จ');
        } catch (\Exception $e) {
            Log::error('Failed to delete risk assessment', [
                'id' => $riskAssessment->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการลบการประเมินความเสี่ยง');
        }
    }
}