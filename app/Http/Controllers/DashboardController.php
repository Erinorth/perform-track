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
        $heatmapData = $this->prepareHeatmapData($riskType);
        $recentIncidents = $this->countRecentIncidents();
        $riskTypeOptions = $this->getRiskTypeOptions();

        // ส่งข้อมูลไปยังหน้า Dashboard ผ่าน Inertia
        return Inertia::render('Dashboard', [
            'trendData' => $trendData,
            'riskSummary' => $riskSummary,
            'heatmapData' => $heatmapData,
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
        $query = RiskAssessment::where('assessment_date', '>=', $startDate);
        
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
        
        // สร้างโครงสร้างข้อมูล (ปรับเป็น 3 ระดับ)
        $groupedData = [];
        foreach ($periods as $period) {
            $periodData = $assessments->filter(function ($assessment) use ($period) {
                $assessmentDate = Carbon::parse($assessment->assessment_date);
                return $assessmentDate->between($period['start'], $period['end']);
            });
            
            $groupedData[] = [
                'date' => $period['label'],
                'high' => $periodData->filter(function ($a) {
                    return $a->likelihood_level * $a->impact_level >= 9 && $a->likelihood_level * $a->impact_level <= 16;
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
            ->select('division_risk_id', DB::raw('MAX(assessment_date) as latest_date'))
            ->groupBy('division_risk_id');
            
        $query = DB::table('risk_assessments as ra')
            ->joinSub($latestAssessments, 'latest', function($join) {
                $join->on('ra.division_risk_id', '=', 'latest.division_risk_id')
                    ->on('ra.assessment_date', '=', 'latest.latest_date');
            })
            ->join('division_risks as dr', 'ra.division_risk_id', '=', 'dr.id');
        
        // กรองตามประเภทความเสี่ยง
        if ($riskType !== 'all') {
            $query->where('dr.organizational_risk_id', $riskType);
        }
            
        $riskCounts = $query->select(
                DB::raw('COUNT(CASE WHEN likelihood_level * impact_level >= 9 AND likelihood_level * impact_level <= 16 THEN 1 END) as high'),
                DB::raw('COUNT(CASE WHEN likelihood_level * impact_level >= 4 AND likelihood_level * impact_level <= 8 THEN 1 END) as medium'),
                DB::raw('COUNT(CASE WHEN likelihood_level * impact_level >= 1 AND likelihood_level * impact_level <= 3 THEN 1 END) as low')
            )
            ->first();
            
        return [
            'high' => $riskCounts->high ?? 0,
            'medium' => $riskCounts->medium ?? 0,
            'low' => $riskCounts->low ?? 0
        ];
    }

    /**
     * สร้างข้อมูลสำหรับ Heatmap
     * 
     * @param string|int $riskType ประเภทความเสี่ยง (all หรือ id)
     * @return array ข้อมูลจำนวนความเสี่ยงในแต่ละช่อง
     */
    private function prepareHeatmapData($riskType = 'all')
    {
        // ใช้ข้อมูลจากการประเมินล่าสุดของแต่ละความเสี่ยง
        $latestAssessments = DB::table('risk_assessments as ra')
            ->select('division_risk_id', DB::raw('MAX(assessment_date) as latest_date'))
            ->groupBy('division_risk_id');
            
        $query = DB::table('risk_assessments as ra')
            ->joinSub($latestAssessments, 'latest', function($join) {
                $join->on('ra.division_risk_id', '=', 'latest.division_risk_id')
                    ->on('ra.assessment_date', '=', 'latest.latest_date');
            })
            ->join('division_risks as dr', 'ra.division_risk_id', '=', 'dr.id');
            
        // กรองตามประเภทความเสี่ยง
        if ($riskType !== 'all') {
            $query->where('dr.organizational_risk_id', $riskType);
        }
            
        $heatmapData = $query->select(
                'ra.likelihood_level as likelihood',
                'ra.impact_level as impact',
                DB::raw('COUNT(*) as risks')
            )
            ->groupBy('likelihood', 'impact')
            ->get()
            ->map(function($item) {
                return [
                    'likelihood' => $item->likelihood,
                    'impact' => $item->impact,
                    'risks' => (int) $item->risks
                ];
            })
            ->toArray();
            
        return $heatmapData;
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
