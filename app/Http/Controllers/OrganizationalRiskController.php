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
        
        return redirect()->back()->with('success', 'อัปเดตความเสี่ยงระดับสายงานเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrganizationalRisk $organizationalRisk)
    {
        $oldData = $organizationalRisk->toArray();
        $organizationalRisk->delete();
        
        return redirect()->back()->with('success', 'ลบความเสี่ยงระดับสายงานเรียบร้อยแล้ว');
    }
}