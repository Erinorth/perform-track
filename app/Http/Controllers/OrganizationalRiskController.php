<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalRisk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrganizationalRiskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $risks = OrganizationalRisk::orderBy('year', 'desc')
            ->orderBy('risk_name')
            ->get();

        return Inertia::render('organizational_risk/OrganizationalRisk', [
            'risks' => $risks
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'risk_name' => 'required|string|max:255',
            'description' => 'required|string',
            'year' => 'required|integer',
            'active' => 'boolean',
        ]);

        $risk = OrganizationalRisk::create($validated);
        
        // บันทึกประวัติการเปลี่ยนแปลง
        $this->logRiskHistory($risk, 'create', null, $validated);

        return redirect()->back()->with('success', 'เพิ่มความเสี่ยงระดับสายงานเรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(OrganizationalRisk $organizationalRisk)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrganizationalRisk $organizationalRisk)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrganizationalRisk $organizationalRisk)
    {
        $validated = $request->validate([
            'risk_name' => 'required|string|max:255',
            'description' => 'required|string',
            'year' => 'required|integer',
            'active' => 'boolean',
        ]);

        $oldData = $organizationalRisk->toArray();
        $organizationalRisk->update($validated);
        
        // บันทึกประวัติการเปลี่ยนแปลง
        $this->logRiskHistory($organizationalRisk, 'update', $oldData, $validated);

        return redirect()->back()->with('success', 'อัปเดตความเสี่ยงระดับสายงานเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrganizationalRisk $organizationalRisk)
    {
        $oldData = $organizationalRisk->toArray();
        $organizationalRisk->delete();
        
        // บันทึกประวัติการเปลี่ยนแปลง
        $this->logRiskHistory($organizationalRisk, 'delete', $oldData, null);

        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับสายงานเรียบร้อยแล้ว');
    }

    public function history($id)
    {
        $history = DB::table('risk_history')
            ->where('risk_id', $id)
            ->where('risk_type', 'organizational')
            ->join('users', 'risk_history.user_id', '=', 'users.id')
            ->select('risk_history.*', DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }

    public function yearlyComparison($id)
    {
        $risk = OrganizationalRisk::findOrFail($id);
        
        // ค้นหาความเสี่ยงที่มีชื่อเดียวกันในปีอื่นๆ
        $yearlyData = OrganizationalRisk::where('risk_name', 'like', '%' . $risk->risk_name . '%')
            ->orWhere(function($query) use ($risk) {
                $query->where('id', $risk->id);
            })
            ->orderBy('year', 'desc')
            ->get();

        return response()->json($yearlyData);
    }

    private function logRiskHistory($risk, $actionType, $oldData, $newData)
    {
        $changes = [];
        
        if ($actionType === 'update') {
            foreach ($newData as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] !== $value) {
                    $changes[$key] = [
                        'from' => $oldData[$key],
                        'to' => $value
                    ];
                }
            }
        } elseif ($actionType === 'create') {
            foreach ($newData as $key => $value) {
                $changes[$key] = [
                    'from' => null,
                    'to' => $value
                ];
            }
        } elseif ($actionType === 'delete') {
            foreach ($oldData as $key => $value) {
                if (in_array($key, ['risk_name', 'description', 'year', 'active'])) {
                    $changes[$key] = [
                        'from' => $value,
                        'to' => null
                    ];
                }
            }
        }

        DB::table('risk_history')->insert([
            'risk_id' => $risk->id,
            'risk_type' => 'organizational',
            'user_id' => auth()->id(),
            'action_type' => $actionType,
            'changes' => json_encode($changes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
