<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\RiskAssessment;
use App\Models\DivisionRisk;
use App\Models\OrganizationalRisk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Log เมื่อเข้าถึงหน้า dashboard
        Log::info('User accessed dashboard', [
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);

        // รับพารามิเตอร์สำหรับกรองข้อมูล
        $timeRange = $request->input('timeRange', 'month');
        $riskType = $request->input('riskType', 'all');

        // เตรียมข้อมูลสำหรับ Dashboard
        $trendData = $this->prepareTrendData($timeRange, $riskType);
        $riskSummary = $this->prepareRiskSummary($riskType);
        $riskMatrixData = $this->prepareRiskMatrixData($riskType); // เปลี่ยนชื่อจาก heatmapData
        $recentIncidents = $this->countRecentIncidents();
        $riskTypeOptions = $this->getRiskTypeOptions();

        // ส่งข้อมูลไปยังหน้า Dashboard ผ่าน Inertia
        return Inertia::render('Dashboard', [
            'trendData' => $trendData,
            'riskSummary' => $riskSummary,
            'riskMatrixData' => $riskMatrixData, // เปลี่ยนชื่อ
            'recentIncidents' => $recentIncidents,
            'riskTypeOptions' => $riskTypeOptions
        ]);
    }

    /**
     * ดึงข้อมูลประเภทความเสี่ยงจากฐานข้อมูล
     * 
     * @return array ข้อมูลประเภทความเสี่ยงสำหรับใช้ในตัวกรอง
     */
    private function getRiskTypeOptions()
    {
        $organizationalRisks = OrganizationalRisk::select('id', 'risk_name')
            ->orderBy('risk_name')
            ->get()
            ->map(function($risk) {
                return [
                    'value' => $risk->id,
                    'label' => $risk->risk_name
                ];
            })
            ->toArray();
        
        // เพิ่มตัวเลือก "ทั้งหมด" ไว้อันดับแรก
        array_unshift($organizationalRisks, [
            'value' => 'all',
            'label' => 'ทั้งหมด'
        ]);
        
        return $organizationalRisks;
    }

    /**
     * สร้างข้อมูลแนวโน้มความเสี่ยงตามช่วงเวลาและประเภทความเสี่ยง
     * 
     * @param string $timeRange ช่วงเวลา (week, month, quarter, year)
     * @param string|int $riskType ประเภทความเสี่ยง (all หรือ id)
     * @return array ข้อมูลแนวโน้มความเสี่ยง
     */
    private function prepareTrendData($timeRange = 'month', $riskType = 'all')
    {
        // กำหนดช่วงเวลาตามพารามิเตอร์
        $startDate = match($timeRange) {
            'week' => Carbon::now()->subDays(7),
            'quarter' => Carbon::now()->subMonths(3),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30), // month
        };
        
        // สร้าง query builder
        $query = RiskAssessment::where('created_at', '>=', $startDate); // แก้ assessment_date -> created_at
        
        // กรองตามประเภทความเสี่ยง (ถ้าไม่ใช่ 'all')
        if ($riskType !== 'all') {
            $query->whereHas('divisionRisk', function($q) use ($riskType) {
                $q->where('organizational_risk_id', $riskType);
            });
        }
        
        // ดึงข้อมูล
        $assessments = $query->get();
        
        // จัดกลุ่มข้อมูลตามสัปดาห์
        $periods = $this->getPeriods($timeRange);
        
        // สร้างโครงสร้างข้อมูล (ปรับเป็น 4 ระดับ)
        $groupedData = [];
        foreach ($periods as $period) {
            $periodData = $assessments->filter(function ($assessment) use ($period) {
                $assessmentDate = Carbon::parse($assessment->created_at); // แก้ assessment_date -> created_at
                return $assessmentDate->between($period['start'], $period['end']);
            });
            
            $groupedData[] = [
                'date' => $period['label'],
                'critical' => $periodData->filter(function ($a) {
                    return $a->likelihood_level * $a->impact_level >= 13 && $a->likelihood_level * $a->impact_level <= 16;
                })->count(),
                'high' => $periodData->filter(function ($a) {
                    return $a->likelihood_level * $a->impact_level >= 9 && $a->likelihood_level * $a->impact_level <= 12;
                })->count(),
                'medium' => $periodData->filter(function ($a) {
                    return $a->likelihood_level * $a->impact_level >= 4 && $a->likelihood_level * $a->impact_level <= 8;
                })->count(),
                'low' => $periodData->filter(function ($a) {
                    return $a->likelihood_level * $a->impact_level >= 1 && $a->likelihood_level * $a->impact_level <= 3;
                })->count()
            ];
        }
        
        return $groupedData;
    }

    /**
     * กำหนดช่วงเวลาสำหรับการแบ่งข้อมูล
     */
    private function getPeriods($timeRange)
    {
        $periods = [];
        
        switch ($timeRange) {
            case 'week':
                // แบ่งเป็นรายวัน 7 วัน
                for ($i = 6; $i >= 0; $i--) {
                    $day = Carbon::now()->subDays($i);
                    $periods[] = [
                        'label' => $day->format('d M Y'),
                        'start' => $day->copy()->startOfDay(),
                        'end' => $day->copy()->endOfDay()
                    ];
                }
                break;
                
            case 'quarter':
                // แบ่งเป็นรายเดือน 3 เดือน
                for ($i = 2; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $periods[] = [
                        'label' => $month->format('M Y'),
                        'start' => $month->copy()->startOfMonth(),
                        'end' => $month->copy()->endOfMonth()
                    ];
                }
                break;
                
            case 'year':
                // แบ่งเป็นรายไตรมาส 4 ไตรมาส
                for ($i = 3; $i >= 0; $i--) {
                    $quarter = Carbon::now()->subQuarters($i);
                    $periods[] = [
                        'label' => 'Q' . ceil($quarter->month / 3) . ' ' . $quarter->year,
                        'start' => $quarter->copy()->startOfQuarter(),
                        'end' => $quarter->copy()->endOfQuarter()
                    ];
                }
                break;
                
            default: // month
                // แบ่งเป็นรายสัปดาห์ 4 สัปดาห์
                for ($i = 3; $i >= 0; $i--) {
                    $week = Carbon::now()->subWeeks($i);
                    $periods[] = [
                        'label' => $week->startOfWeek()->format('d M Y'),
                        'start' => $week->copy()->startOfWeek(),
                        'end' => $week->copy()->endOfWeek()
                    ];
                }
        }
        
        return $periods;
    }

    /**
     * นับจำนวนความเสี่ยงแยกตามระดับ
     * 
     * @param string|int $riskType ประเภทความเสี่ยง (all หรือ id)
     * @return array ข้อมูลสรุปจำนวนความเสี่ยงแต่ละระดับ
     */
    private function prepareRiskSummary($riskType = 'all')
    {
        // ใช้ข้อมูลจากการประเมินล่าสุดของแต่ละความเสี่ยง
        $latestAssessments = DB::table('risk_assessments as ra')
            ->select('division_risk_id', DB::raw('MAX(created_at) as latest_date')) // แก้ assessment_date -> created_at
            ->groupBy('division_risk_id');
            
        $query = DB::table('risk_assessments as ra')
            ->joinSub($latestAssessments, 'latest', function($join) {
                $join->on('ra.division_risk_id', '=', 'latest.division_risk_id')
                    ->on('ra.created_at', '=', 'latest.latest_date'); // แก้ assessment_date -> created_at
            })
            ->join('division_risks as dr', 'ra.division_risk_id', '=', 'dr.id');
        
        // กรองตามประเภทความเสี่ยง
        if ($riskType !== 'all') {
            $query->where('dr.organizational_risk_id', $riskType);
        }
            
        $riskCounts = $query->select(
                DB::raw('COUNT(CASE WHEN likelihood_level * impact_level >= 13 AND likelihood_level * impact_level <= 16 THEN 1 END) as critical'),
                DB::raw('COUNT(CASE WHEN likelihood_level * impact_level >= 9 AND likelihood_level * impact_level <= 12 THEN 1 END) as high'),
                DB::raw('COUNT(CASE WHEN likelihood_level * impact_level >= 4 AND likelihood_level * impact_level <= 8 THEN 1 END) as medium'),
                DB::raw('COUNT(CASE WHEN likelihood_level * impact_level >= 1 AND likelihood_level * impact_level <= 3 THEN 1 END) as low')
            )
            ->first();
            
        return [
            'critical' => $riskCounts->critical ?? 0,
            'high' => $riskCounts->high ?? 0,
            'medium' => $riskCounts->medium ?? 0,
            'low' => $riskCounts->low ?? 0
        ];
    }

    /**
     * สร้างข้อมูลสำหรับ Risk Matrix Bubble Chart
     * 
     * @param string|int $riskType ประเภทความเสี่ยง (all หรือ id)
     * @return array ข้อมูลสำหรับ Risk Matrix พร้อมรายละเอียดความเสี่ยง
     */
    private function prepareRiskMatrixData($riskType = 'all')
    {
        // ใช้ข้อมูลจากการประเมินล่าสุดของแต่ละความเสี่ยง
        $latestAssessments = DB::table('risk_assessments as ra')
            ->select('division_risk_id', DB::raw('MAX(created_at) as latest_date')) // แก้ assessment_date -> created_at
            ->groupBy('division_risk_id');
            
        $query = DB::table('risk_assessments as ra')
            ->joinSub($latestAssessments, 'latest', function($join) {
                $join->on('ra.division_risk_id', '=', 'latest.division_risk_id')
                    ->on('ra.created_at', '=', 'latest.latest_date'); // แก้ assessment_date -> created_at
            })
            ->join('division_risks as dr', 'ra.division_risk_id', '=', 'dr.id')
            ->join('organizational_risks as org', 'dr.organizational_risk_id', '=', 'org.id');
            
        // กรองตามประเภทความเสี่ยง
        if ($riskType !== 'all') {
            $query->where('dr.organizational_risk_id', $riskType);
        }
            
        // ดึงข้อมูลการประเมินพร้อมรายละเอียดความเสี่ยง
        $assessments = $query->select(
                'ra.likelihood_level',
                'ra.impact_level',
                'ra.created_at as assessment_date', // เปลี่ยนชื่อ field ส่งออกให้ frontend เหมือนเดิม
                'ra.notes',
                'dr.id as division_risk_id',
                'dr.risk_name',
                'dr.description',
                'org.id as organizational_risk_id',
                'org.risk_name as org_risk_name'
            )
            ->get();
            
        // จัดกลุ่มข้อมูลตาม likelihood_level และ impact_level
        $matrixData = [];
        foreach ($assessments as $assessment) {
            $key = $assessment->likelihood_level . '_' . $assessment->impact_level;
            
            if (!isset($matrixData[$key])) {
                $matrixData[$key] = [
                    'likelihood_level' => $assessment->likelihood_level,
                    'impact_level' => $assessment->impact_level,
                    'risk_score' => $assessment->likelihood_level * $assessment->impact_level,
                    'risks' => []
                ];
            }
            
            // เพิ่มข้อมูลความเสี่ยงเข้าไปในกลุ่ม
            $matrixData[$key]['risks'][] = [
                'id' => $assessment->division_risk_id,
                'risk_name' => $assessment->risk_name,
                'description' => $assessment->description,
                'division_risk_id' => $assessment->division_risk_id,
                'organizational_risk_id' => $assessment->organizational_risk_id,
                'org_risk_name' => $assessment->org_risk_name,
                'assessment_date' => $assessment->assessment_date,
                'notes' => $assessment->notes
            ];
        }
        
        // แปลงเป็น array และเรียงลำดับ
        $result = array_values($matrixData);
        
        // Log สำหรับ debugging
        Log::info('Risk Matrix Data prepared', [
            'total_groups' => count($result),
            'total_risks' => $assessments->count(),
            'risk_type_filter' => $riskType
        ]);
        
        return $result;
    }

    /**
     * นับจำนวนเหตุการณ์ล่าสุด
     * 
     * @return int จำนวนการประเมินที่เพิ่มล่าสุด
     */
    private function countRecentIncidents()
    {
        return RiskAssessment::where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
    }
}
