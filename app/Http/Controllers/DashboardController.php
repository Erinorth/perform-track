<?php
/* app\Http\Controllers\DashboardController.php */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\RiskAssessment;
use App\Models\DivisionRisk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Log เมื่อเข้าถึงหน้า dashboard
        Log::info('User accessed dashboard', [
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);

        // เตรียมข้อมูลสำหรับ Dashboard
        $trendData = $this->prepareTrendData();
        $riskSummary = $this->prepareRiskSummary();
        $heatmapData = $this->prepareHeatmapData();
        $recentIncidents = $this->countRecentIncidents();

        // ส่งข้อมูลไปยังหน้า Dashboard ผ่าน Inertia
        return Inertia::render('Dashboard', [
            'trendData' => $trendData,
            'riskSummary' => $riskSummary,
            'heatmapData' => $heatmapData,
            'recentIncidents' => $recentIncidents
        ]);
    }

    /**
     * สร้างข้อมูลแนวโน้มความเสี่ยงรายสัปดาห์ย้อนหลัง 4 สัปดาห์
     * 
     * @return array ข้อมูลแนวโน้มความเสี่ยงแยกตามระดับ (สูง/กลาง/ต่ำ) และวันที่
     */
    private function prepareTrendData()
    {
        // ดึงข้อมูลย้อนหลัง 30 วัน จัดกลุ่มเป็นสัปดาห์
        $startDate = Carbon::now()->subDays(30);
        
        $assessments = RiskAssessment::where('assessment_date', '>=', $startDate)
            ->get();
        
        // จัดกลุ่มข้อมูลตามสัปดาห์
        $groupedData = [];
        $weeks = collect([0, 1, 2, 3])->map(function ($weeksAgo) {
            $weekStart = Carbon::now()->subWeeks($weeksAgo)->startOfWeek();
            $key = $weekStart->format('d M Y');
            return [
                'date' => $key,
                'start' => $weekStart,
                'end' => Carbon::now()->subWeeks($weeksAgo)->endOfWeek()
            ];
        });
        
        // สร้างโครงสร้างข้อมูล (ปรับเป็น 3 ระดับ)
        foreach ($weeks as $week) {
            $weekData = $assessments->filter(function ($assessment) use ($week) {
                $assessmentDate = Carbon::parse($assessment->assessment_date);
                return $assessmentDate->between($week['start'], $week['end']);
            });
            
            $groupedData[] = [
                'date' => $week['date'],
                'high' => $weekData->filter(function ($a) {
                    return $a->risk_score >= 9 && $a->risk_score <= 16;
                })->count(),
                'medium' => $weekData->filter(function ($a) {
                    return $a->risk_score >= 4 && $a->risk_score <= 8;
                })->count(),
                'low' => $weekData->filter(function ($a) {
                    return $a->risk_score >= 1 && $a->risk_score <= 3;
                })->count()
            ];
        }
        
        // เรียงตามวันที่ (จากเก่าไปใหม่)
        return array_reverse($groupedData);
    }

    /**
     * นับจำนวนความเสี่ยงแยกตามระดับ (ปรับเป็น 3 ระดับ)
     * 
     * @return array ข้อมูลสรุปจำนวนความเสี่ยงแต่ละระดับ (สูง/กลาง/ต่ำ)
     */
    private function prepareRiskSummary()
    {
        // ใช้ข้อมูลจากการประเมินล่าสุดของแต่ละความเสี่ยง
        $latestAssessments = DB::table('risk_assessments as ra')
            ->select('division_risk_id', DB::raw('MAX(assessment_date) as latest_date'))
            ->groupBy('division_risk_id');
            
        $riskCounts = DB::table('risk_assessments as ra')
            ->joinSub($latestAssessments, 'latest', function($join) {
                $join->on('ra.division_risk_id', '=', 'latest.division_risk_id')
                    ->on('ra.assessment_date', '=', 'latest.latest_date');
            })
            ->select(
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
     * @return array ข้อมูลจำนวนความเสี่ยงในแต่ละช่อง (likelihood x impact)
     */
    private function prepareHeatmapData()
    {
        // ใช้ข้อมูลจากการประเมินล่าสุดของแต่ละความเสี่ยง
        $latestAssessments = DB::table('risk_assessments as ra')
            ->select('division_risk_id', DB::raw('MAX(assessment_date) as latest_date'))
            ->groupBy('division_risk_id');
            
        $heatmapData = DB::table('risk_assessments as ra')
            ->joinSub($latestAssessments, 'latest', function($join) {
                $join->on('ra.division_risk_id', '=', 'latest.division_risk_id')
                    ->on('ra.assessment_date', '=', 'latest.latest_date');
            })
            ->select(
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
     * นับจำนวนเหตุการณ์ล่าสุด (ในที่นี้คือการประเมินที่เพิ่มในรอบ 7 วัน)
     * 
     * @return int จำนวนการประเมินที่เพิ่มล่าสุด
     */
    private function countRecentIncidents()
    {
        return RiskAssessment::where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
    }
}
