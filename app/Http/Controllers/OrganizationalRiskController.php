<?php
// ไฟล์: app\Http\Controllers\OrganizationalRiskController.php
// Controller สำหรับจัดการความเสี่ยงระดับองค์กรในระบบประเมินความเสี่ยง
// รองรับการจัดการเอกสารแนบแบบครบถ้วนและปลอดภัย

namespace App\Http\Controllers;

use App\Models\OrganizationalRisk;
use App\Models\OrganizationalRiskAttachment;
use App\Http\Requests\StoreOrganizationalRiskRequest;
use App\Http\Requests\UpdateOrganizationalRiskRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrganizationalRiskController extends Controller
{
    /**
     * แสดงรายการความเสี่ยงระดับองค์กรทั้งหมด
     */
    public function index()
    {
        try {
            // ดึงข้อมูลความเสี่ยงระดับองค์กรทั้งหมด พร้อมความสัมพันธ์และเอกสารแนบ
            $risks = OrganizationalRisk::with(['divisionRisks', 'attachments'])
                ->orderBy('risk_name')
                ->get();

            // สถิติความเสี่ยงระดับองค์กร
            $statistics = [
                'total_risks' => $risks->count(),
                'risks_with_divisions' => $risks->filter(function ($risk) {
                    return $risk->divisionRisks->count() > 0;
                })->count(),
                'risks_with_attachments' => $risks->filter(function ($risk) {
                    return $risk->attachments->count() > 0;
                })->count(),
            ];

            Log::info('เข้าถึงรายการความเสี่ยงระดับองค์กร', [
                'total_risks' => $statistics['total_risks'],
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

            return Inertia::render('organizational_risk/OrganizationalRisk', [
                'risks' => $risks,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการดึงรายการความเสี่ยงระดับองค์กร', [
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * แสดงหน้าสร้างความเสี่ยงใหม่
     */
    public function create()
    {
        try {
            Log::info('เข้าถึงหน้าสร้างความเสี่ยงองค์กรใหม่', [
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            return Inertia::render('organizational_risk/OrganizationalRiskForm');

        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการเข้าถึงหน้าสร้างความเสี่ยงองค์กร', [
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการโหลดหน้า: ' . $e->getMessage());
        }
    }

    /**
     * บันทึกข้อมูลความเสี่ยงระดับองค์กรใหม่ลงฐานข้อมูล
     */
    public function store(StoreOrganizationalRiskRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // สร้างข้อมูลความเสี่ยงใหม่
            $risk = OrganizationalRisk::create($request->validated());
            
            // จัดการเอกสารแนบ (ถ้ามี)
            $this->handleAttachments($request, $risk);
            
            DB::commit();
            
            // ดึงข้อมูลพร้อม relationships
            $risk->load('attachments', 'divisionRisks');
            
            Log::info('สร้างความเสี่ยงองค์กรใหม่เรียบร้อย', [
                'id' => $risk->id,
                'risk_name' => $risk->risk_name,
                'attachments_count' => $risk->attachments->count(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            return redirect()->back()
                ->with('success', 'เพิ่มความเสี่ยงองค์กรเรียบร้อยแล้ว')
                ->with('risk', $risk);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('เกิดข้อผิดพลาดในการสร้างความเสี่ยงองค์กร', [
                'error' => $e->getMessage(),
                'input_data' => $request->except(['attachments']),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * แสดงรายละเอียดเต็มของความเสี่ยงระดับองค์กร
     */
    public function show(OrganizationalRisk $organizationalRisk)
    {
        try {
            // โหลดข้อมูลความเสี่ยงพร้อมความสัมพันธ์และเอกสารแนบ
            $risk = OrganizationalRisk::with(['divisionRisks', 'attachments'])
                ->findOrFail($organizationalRisk->id);

            // เพิ่ม URL สำหรับเอกสารแนบ
            if ($risk->attachments) {
                $risk->attachments->transform(function ($attachment) {
                    $attachment->url = route('organizational-risks.attachments.view', [
                        'organizationalRisk' => $attachment->organizational_risk_id,
                        'attachmentId' => $attachment->id
                    ]);
                    return $attachment;
                });
            }
            
            Log::info('เข้าถึงรายละเอียดความเสี่ยงระดับองค์กร', [
                'risk_id' => $risk->id,
                'risk_name' => $risk->risk_name,
                'division_risks_count' => $risk->divisionRisks->count(),
                'attachments_count' => $risk->attachments->count(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

            return Inertia::render('organizational_risk/OrganizationalRiskShow', [
                'risk' => $risk
            ]);

        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการแสดงรายละเอียดความเสี่ยงระดับองค์กร', [
                'risk_id' => $organizationalRisk->id,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * แสดงฟอร์มแก้ไขข้อมูลความเสี่ยงระดับองค์กร
     */
    public function edit(OrganizationalRisk $organizationalRisk)
    {
        try {
            // โหลดข้อมูลพร้อมเอกสารแนบและความสัมพันธ์
            $risk = OrganizationalRisk::with(['attachments', 'divisionRisks'])
                ->findOrFail($organizationalRisk->id);

            // เพิ่ม URL สำหรับเอกสารแนบ
            if ($risk->attachments) {
                $risk->attachments->transform(function ($attachment) {
                    $attachment->url = route('organizational-risks.attachments.view', [
                        'organizationalRisk' => $attachment->organizational_risk_id,
                        'attachmentId' => $attachment->id
                    ]);
                    return $attachment;
                });
            }

            Log::info('เข้าถึงฟอร์มแก้ไขความเสี่ยงระดับองค์กร', [
                'risk_id' => $risk->id,
                'risk_name' => $risk->risk_name,
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

            return Inertia::render('organizational_risk/OrganizationalRiskForm', [
                'risk' => $risk
            ]);

        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการเข้าถึงฟอร์มแก้ไขความเสี่ยงระดับองค์กร', [
                'risk_id' => $organizationalRisk->id,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * อัปเดตข้อมูลความเสี่ยงระดับองค์กรที่มีอยู่
     */
    public function update(UpdateOrganizationalRiskRequest $request, OrganizationalRisk $organizationalRisk)
    {
        DB::beginTransaction();
        
        try {
            // อัปเดตข้อมูลพื้นฐาน
            $organizationalRisk->update($request->validated());
            
            // จัดการไฟล์แนบใหม่ (ถ้ามี)
            $this->handleAttachments($request, $organizationalRisk);
            
            // จัดการไฟล์ที่ต้องการลบ
            $this->handleAttachmentsToDelete($request, $organizationalRisk);
            
            DB::commit();
            
            // ดึงข้อมูลที่อัปเดตเรียบร้อยแล้วพร้อมเอกสารแนบ
            $updatedRisk = OrganizationalRisk::with(['attachments', 'divisionRisks'])
                ->find($organizationalRisk->id);
            
            Log::info('อัปเดตความเสี่ยงระดับองค์กรเรียบร้อย', [
                'risk_id' => $organizationalRisk->id,
                'risk_name' => $organizationalRisk->risk_name,
                'attachments_count' => $updatedRisk->attachments->count(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            return redirect()->back()->with([
                'success' => 'อัปเดตข้อมูลความเสี่ยงเรียบร้อยแล้ว',
                'updatedRisk' => $updatedRisk
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('เกิดข้อผิดพลาดในการอัปเดตความเสี่ยงระดับองค์กร', [
                'risk_id' => $organizationalRisk->id,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ลบข้อมูลความเสี่ยงระดับองค์กร (Soft Delete)
     */
    public function destroy(OrganizationalRisk $organizationalRisk)
    {
        DB::beginTransaction();
        
        try {
            $oldData = $organizationalRisk->toArray();
            
            // ตรวจสอบว่ามีความเสี่ยงระดับฝ่ายที่เชื่อมโยงหรือไม่
            $hasDivisionRisks = $organizationalRisk->divisionRisks()->exists();
            
            if ($hasDivisionRisks) {
                Log::warning('พยายามลบความเสี่ยงที่มีการเชื่อมโยงกับความเสี่ยงระดับฝ่าย', [
                    'risk_id' => $organizationalRisk->id,
                    'risk_name' => $organizationalRisk->risk_name,
                    'division_risks_count' => $organizationalRisk->divisionRisks()->count(),
                    'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ'
                ]);
                
                return redirect()->back()
                    ->with('error', 'ไม่สามารถลบความเสี่ยงนี้ได้เนื่องจากมีความเสี่ยงระดับฝ่ายที่เชื่อมโยงอยู่');
            }
            
            // ลบเอกสารแนบที่เกี่ยวข้อง
            foreach ($organizationalRisk->attachments as $attachment) {
                $this->deleteAttachmentFile($attachment);
                $attachment->delete();
            }
            
            // ลบข้อมูลความเสี่ยง
            $organizationalRisk->delete();
            
            DB::commit();
            
            Log::info('ลบความเสี่ยงระดับองค์กรเรียบร้อย', [
                'id' => $oldData['id'],
                'risk_name' => $oldData['risk_name'],
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            return redirect()->route('organizational-risks.index')
                ->with('success', 'ลบความเสี่ยงระดับองค์กรเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('เกิดข้อผิดพลาดในการลบความเสี่ยงระดับองค์กร', [
                'risk_id' => $organizationalRisk->id,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : 'ไม่ระบุ',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * จัดการเอกสารแนบหลายไฟล์สำหรับความเสี่ยงองค์กร
     */
    private function handleAttachments($request, $risk)
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        $filePath = 'organizational-risks/' . $risk->id;

        foreach ($request->file('attachments') as $file) {
            try {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs($filePath, $fileName, 'public');
                
                if ($path) {
                    OrganizationalRiskAttachment::create([
                        'organizational_risk_id' => $risk->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize()
                    ]);
                    
                    Log::info('อัปโหลดเอกสารแนบเรียบร้อย', [
                        'risk_id' => $risk->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('เกิดข้อผิดพลาดในการอัปโหลดเอกสารแนบ', [
                    'risk_id' => $risk->id,
                    'file_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * จัดการการลบเอกสารแนบ
     */
    private function handleAttachmentsToDelete($request, $organizationalRisk)
    {
        if (!$request->has('attachments_to_delete') || !is_array($request->attachments_to_delete)) {
            return;
        }

        foreach ($request->attachments_to_delete as $attachmentId) {
            try {
                $attachment = OrganizationalRiskAttachment::where('id', $attachmentId)
                    ->where('organizational_risk_id', $organizationalRisk->id)
                    ->first();
                
                if ($attachment) {
                    $this->deleteAttachmentFile($attachment);
                    $attachment->delete();
                    
                    Log::info('ลบเอกสารแนบเรียบร้อย', [
                        'risk_id' => $organizationalRisk->id,
                        'attachment_id' => $attachmentId,
                        'file_name' => $attachment->file_name
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('เกิดข้อผิดพลาดในการลบเอกสารแนบ', [
                    'risk_id' => $organizationalRisk->id,
                    'attachment_id' => $attachmentId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * ลบไฟล์เอกสารแนบจาก Storage อย่างปลอดภัย
     */
    private function deleteAttachmentFile($attachment)
    {
        if (!empty($attachment->file_path) && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }
    }

    /**
     * เพิ่มเอกสารแนบเดี่ยวสำหรับความเสี่ยงองค์กร (API endpoint)
     */
    public function storeAttachment(Request $request, OrganizationalRisk $organizationalRisk)
    {
        try {
            if (!$request->hasFile('attachment')) {
                return response()->json(['error' => 'ไม่พบไฟล์ที่อัปโหลด'], 400);
            }

            $request->validate([
                'attachment' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif|max:10240'
            ]);

            $file = $request->file('attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = 'organizational-risks/' . $organizationalRisk->id;
            $path = $file->storeAs($filePath, $fileName, 'public');
            
            $attachment = OrganizationalRiskAttachment::create([
                'organizational_risk_id' => $organizationalRisk->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize()
            ]);
            
            Log::info('เพิ่มเอกสารแนบสำหรับความเสี่ยงองค์กร', [
                'risk_id' => $organizationalRisk->id,
                'file_name' => $file->getClientOriginalName(),
                'user' => Auth::check() ? Auth::user()->name : null,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดเอกสารแนบเรียบร้อยแล้ว',
                'attachment' => $attachment
            ]);
            
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการอัปโหลดเอกสารแนบ', [
                'risk_id' => $organizationalRisk->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => Auth::check() ? Auth::user()->name : null,
            ]);
            
            return response()->json([
                'error' => 'เกิดข้อผิดพลาดในการอัปโหลดเอกสารแนบ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ลบเอกสารแนบของความเสี่ยงองค์กร
     */
    public function destroyAttachment(OrganizationalRisk $organizationalRisk, $attachmentId)
    {
        try {
            $attachment = $organizationalRisk->attachments()->findOrFail($attachmentId);
            
            $this->deleteAttachmentFile($attachment);
            $attachment->delete();
            
            Log::info('ลบเอกสารแนบสำหรับความเสี่ยงองค์กร', [
                'risk_id' => $organizationalRisk->id,
                'attachment_id' => $attachmentId,
                'file_name' => $attachment->file_name,
                'user' => Auth::check() ? Auth::user()->name : null,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'ลบเอกสารแนบเรียบร้อยแล้ว'
            ]);
            
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการลบเอกสารแนบ', [
                'risk_id' => $organizationalRisk->id,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : null,
            ]);
            
            return response()->json([
                'error' => 'เกิดข้อผิดพลาดในการลบเอกสารแนบ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดาวน์โหลดเอกสารแนบของความเสี่ยงองค์กร
     */
    public function downloadAttachment(OrganizationalRisk $organizationalRisk, $attachmentId)
    {
        try {
            $attachment = $organizationalRisk->attachments()->findOrFail($attachmentId);
            
            if (empty($attachment->file_path)) {
                Log::error('file_path ของเอกสารแนบเป็น null', [
                    'risk_id' => $organizationalRisk->id,
                    'attachment_id' => $attachmentId,
                    'user' => Auth::check() ? Auth::user()->name : null
                ]);
                
                return response()->json(['error' => 'ไม่พบ path ของไฟล์เอกสารแนบ'], 404);
            }
            
            if (!Storage::disk('public')->exists($attachment->file_path)) {
                Log::error('ไม่พบไฟล์เอกสารแนบในระบบ', [
                    'risk_id' => $organizationalRisk->id,
                    'attachment_id' => $attachmentId,
                    'file_path' => $attachment->file_path,
                    'user' => Auth::check() ? Auth::user()->name : null
                ]);
                
                return response()->json(['error' => 'ไม่พบไฟล์เอกสารแนบในระบบ'], 404);
            }
            
            Log::info('ดาวน์โหลดเอกสารแนบ', [
                'risk_id' => $organizationalRisk->id,
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
     */
    public function viewAttachment(OrganizationalRisk $organizationalRisk, $attachmentId)
    {
        try {
            $attachment = OrganizationalRiskAttachment::where('id', $attachmentId)
                ->where('organizational_risk_id', $organizationalRisk->id)
                ->firstOrFail();

            if (empty($attachment->file_path)) {
                Log::error('file_path ของเอกสารแนบเป็น null', [
                    'risk_id' => $organizationalRisk->id,
                    'attachment_id' => $attachmentId,
                    'user' => Auth::check() ? Auth::user()->name : null
                ]);
                
                return response()->json(['error' => 'ไม่พบ path ของไฟล์เอกสารแนบ'], 404);
            }

            Log::info('ตรวจสอบไฟล์เอกสารแนบ', [
                'file_path' => $attachment->file_path,
                'exists' => Storage::disk('public')->exists($attachment->file_path),
                'real_path' => Storage::disk('public')->path($attachment->file_path)
            ]);

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return response()->json(['error' => 'ไม่พบไฟล์เอกสารแนบในระบบ'], 404);
            }

            return response()->file(Storage::disk('public')->path($attachment->file_path));
            
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการแสดงเอกสารแนบ', [
                'risk_id' => $organizationalRisk->id,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
                'user' => Auth::check() ? Auth::user()->name : null
            ]);
            
            return response()->json([
                'error' => 'เกิดข้อผิดพลาดในการแสดงเอกสารแนบ: ' . $e->getMessage()
            ], 500);
        }
    }
}
