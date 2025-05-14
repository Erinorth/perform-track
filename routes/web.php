<?php
/**
 * ไฟล์: routes/web.php
 * คำอธิบาย: ไฟล์กำหนดเส้นทาง (routes) สำหรับการเข้าถึงหน้าต่างๆ ในระบบประเมินความเสี่ยง
 * เทคโนโลยี: Laravel 12 + Inertia.js + Vue 3
 * 
 * ไฟล์นี้กำหนดเส้นทางทั้งหมดในระบบ แบ่งเป็นหมวดหมู่ดังนี้:
 * - เส้นทางหลัก (หน้าแรกและแดชบอร์ด)
 * - เส้นทางสำหรับจัดการความเสี่ยงระดับองค์กร
 * - เส้นทางสำหรับจัดการความเสี่ยงระดับฝ่าย
 * - เส้นทางสำหรับการประเมินความเสี่ยง
 * - เส้นทางสำหรับรายงาน
 */

// นำเข้า Controllers ที่เกี่ยวข้องและคลาสพื้นฐานที่จำเป็น
use App\Http\Controllers\DivisionRiskController;
use App\Http\Controllers\OrganizationalRiskController;
use App\Http\Controllers\RiskAssessmentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * เส้นทางหลักของระบบ (หน้าแรก)
 * 
 * ถ้าผู้ใช้เข้าสู่ระบบแล้ว จะนำไปยังหน้าแดชบอร์ด
 * ถ้ายังไม่ได้เข้าสู่ระบบ จะนำไปยังหน้าล็อกอิน EGAT
 * เป็นจุดเริ่มต้นของการเข้าถึงระบบทั้งหมด
 */
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('loginEGAT');
})->name('home');

/**
 * เส้นทางแดชบอร์ด
 * 
 * แสดงหน้าแดชบอร์ดที่รวมข้อมูลและกราฟสรุปภาพรวมความเสี่ยงทั้งหมด
 * ต้องผ่านการตรวจสอบการเข้าสู่ระบบและการยืนยันบัญชีแล้ว
 */
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/**
 * กลุ่มเส้นทางที่ต้องผ่านการรับรองตัวตน
 * 
 * ทุกเส้นทางภายในกลุ่มนี้จะถูกป้องกันด้วย middleware 'auth'
 * ซึ่งตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบแล้ว ถ้ายังไม่ได้เข้าสู่ระบบ
 * จะถูกเปลี่ยนเส้นทางไปยังหน้าล็อกอิน
 */
Route::middleware('auth')->group(function () {

    /**
     * กลุ่มเส้นทางสำหรับจัดการความเสี่ยงระดับองค์กร
     * 
     * ประกอบด้วยเส้นทางสำหรับการดำเนินการ CRUD (Create, Read, Update, Delete)
     * และการจัดการเอกสารแนบที่เกี่ยวข้องกับความเสี่ยงระดับองค์กร
     */
    Route::prefix('organizational-risks')->group(function () {
        // แสดงรายการความเสี่ยงระดับองค์กรทั้งหมด
        Route::get('/', [OrganizationalRiskController::class, 'index'])
            ->name('organizational-risks.index');
        
        // เพิ่มความเสี่ยงระดับองค์กรใหม่
        Route::post('/', [OrganizationalRiskController::class, 'store'])
            ->name('organizational-risks.store');
        
        // อัปเดตข้อมูลความเสี่ยงระดับองค์กร
        Route::put('/{organizationalRisk}', [OrganizationalRiskController::class, 'update'])
            ->name('organizational-risks.update');
        
        // ลบความเสี่ยงระดับองค์กรหลายรายการพร้อมกัน
        Route::delete('/bulk-destroy', [OrganizationalRiskController::class, 'bulkDestroy'])
            ->name('organizational-risks.bulk-destroy');
        
        // ลบความเสี่ยงระดับองค์กรรายการเดียว
        Route::delete('/{organizationalRisk}', [OrganizationalRiskController::class, 'destroy'])
            ->name('organizational-risks.destroy');

        // เพิ่มเอกสารแนบสำหรับความเสี่ยงระดับองค์กร
        Route::post('/{organizationalRisk}/attachments', [OrganizationalRiskController::class, 'storeAttachment'])
            ->name('organizational-risks.attachments.store');
        
        // ลบเอกสารแนบของความเสี่ยงระดับองค์กร
        Route::delete('/{organizationalRisk}/attachments/{attachmentId}', [OrganizationalRiskController::class, 'destroyAttachment'])
            ->name('organizational-risks.attachments.destroy');

        // ดาวน์โหลดเอกสารแนบของความเสี่ยงระดับองค์กร
        Route::get('/{organizationalRisk}/attachments/{attachmentId}/download', [OrganizationalRiskController::class, 'downloadAttachment'])
            ->name('organizational-risks.attachments.download');

        // แสดงเอกสารแนบของความเสี่ยงระดับองค์กรในเบราว์เซอร์
        Route::get('/{organizationalRisk}/attachments/{attachmentId}/view', [OrganizationalRiskController::class, 'viewAttachment'])
            ->name('organizational-risks.attachments.view');
    });

    /**
     * กลุ่มเส้นทางสำหรับจัดการความเสี่ยงระดับฝ่าย
     * 
     * ประกอบด้วยเส้นทางสำหรับการดำเนินการ CRUD และการจัดการเอกสารแนบ
     * รวมถึงการจัดการเกณฑ์การประเมินความเสี่ยงสำหรับแต่ละความเสี่ยงระดับฝ่าย
     */
    Route::prefix('division-risks')->group(function () {
        // แสดงรายการความเสี่ยงระดับฝ่ายทั้งหมด
        Route::get('/', [DivisionRiskController::class, 'index'])
            ->name('division-risks.index');
        
        // บันทึกความเสี่ยงระดับฝ่ายใหม่
        Route::post('/', [DivisionRiskController::class, 'store'])
            ->name('division-risks.store');
        
        // อัปเดตข้อมูลความเสี่ยงระดับฝ่าย
        Route::put('/{divisionRisk}', [DivisionRiskController::class, 'update'])
            ->name('division-risks.update');
        
        // ลบความเสี่ยงระดับฝ่ายหลายรายการพร้อมกัน
        Route::delete('/bulk-destroy', [DivisionRiskController::class, 'bulkDestroy'])
            ->name('division-risks.bulk-destroy');
        
        // ลบความเสี่ยงระดับฝ่าย
        Route::delete('/{divisionRisk}', [DivisionRiskController::class, 'destroy'])
            ->name('division-risks.destroy');

        // เพิ่มเอกสารแนบสำหรับความเสี่ยงระดับฝ่าย
        Route::post('/{divisionRisk}/attachments', [DivisionRiskController::class, 'storeAttachment'])
            ->name('division-risks.attachments.store');
        
        // ลบเอกสารแนบของความเสี่ยงระดับฝ่าย
        Route::delete('/{divisionRisk}/attachments/{attachmentId}', [DivisionRiskController::class, 'destroyAttachment'])
            ->name('division-risks.attachments.destroy');

        // ดาวน์โหลดเอกสารแนบของความเสี่ยงระดับฝ่าย
        Route::get('/{divisionRisk}/attachments/{attachmentId}/download', [DivisionRiskController::class, 'downloadAttachment'])
            ->name('division-risks.attachments.download');

        // แสดงเอกสารแนบของความเสี่ยงระดับฝ่ายในเบราว์เซอร์
        Route::get('/{divisionRisk}/attachments/{attachmentId}/view', [DivisionRiskController::class, 'viewAttachment'])
            ->name('division-risks.attachments.view');
        
        // เส้นทางสำหรับจัดการเกณฑ์การประเมินความเสี่ยง (ส่วนเพิ่มเติมเฉพาะของความเสี่ยงระดับฝ่าย)
        // แสดงหน้าจัดการเกณฑ์การประเมินความเสี่ยง
        Route::get('/{divisionRisk}/criteria', [DivisionRiskController::class, 'manageCriteria'])
            ->name('division-risks.criteria');
        
        // บันทึกเกณฑ์โอกาสเกิดความเสี่ยง
        Route::post('/{divisionRisk}/likelihood-criteria', [DivisionRiskController::class, 'storeLikelihoodCriteria'])
            ->name('division-risks.likelihood-criteria.store');
        
        // บันทึกเกณฑ์ผลกระทบของความเสี่ยง
        Route::post('/{divisionRisk}/impact-criteria', [DivisionRiskController::class, 'storeImpactCriteria'])
            ->name('division-risks.impact-criteria.store');
    });

    /**
     * กลุ่มเส้นทางสำหรับการประเมินความเสี่ยง
     * 
     * ประกอบด้วยเส้นทางสำหรับการบันทึกและจัดการการประเมินความเสี่ยง
     * รวมถึงการสร้าง แก้ไข ดู และลบการประเมินความเสี่ยง
     */
    Route::prefix('risk-assessments')->group(function () {
        // แสดงรายการการประเมินความเสี่ยงทั้งหมด
        Route::get('/', [RiskAssessmentController::class, 'index'])
            ->name('risk-assessments.index');
        
        // แสดงหน้าสร้างการประเมินความเสี่ยงใหม่
        Route::get('/create', [RiskAssessmentController::class, 'create'])
            ->name('risk-assessments.create');

        Route::get('/{riskAssessment}', [RiskAssessmentController::class, 'show'])
            ->name('risk-assessments.show');
        
        // บันทึกการประเมินความเสี่ยงใหม่
        Route::post('/', [RiskAssessmentController::class, 'store'])
            ->name('risk-assessments.store');
        
        // แสดงหน้าแก้ไขการประเมินความเสี่ยง
        Route::get('/{riskAssessment}/edit', [RiskAssessmentController::class, 'edit'])
            ->name('risk-assessments.edit');
        
        // อัปเดตข้อมูลการประเมินความเสี่ยง
        Route::put('/{riskAssessment}', [RiskAssessmentController::class, 'update'])
            ->name('risk-assessments.update');
        
        // ลบการประเมินความเสี่ยง
        Route::delete('/{riskAssessment}', [RiskAssessmentController::class, 'destroy'])
            ->name('risk-assessments.destroy');
    });

    /**
     * กลุ่มเส้นทางสำหรับรายงาน
     * 
     * ประกอบด้วยเส้นทางสำหรับการเข้าถึงรายงานต่างๆ ในระบบ
     * รวมถึงการส่งออกรายงานในรูปแบบต่างๆ (PDF, Excel)
     */
    Route::prefix('reports')->group(function () {
        // รายงานสรุปสำหรับผู้บริหาร
        Route::get('/executive-summary', [ReportController::class, 'executiveSummary'])
            ->name('reports.executive-summary');
        
        // รายงานวิเคราะห์แนวโน้มความเสี่ยง
        Route::get('/trend-analysis', [ReportController::class, 'trendAnalysis'])
            ->name('reports.trend-analysis');
        
        // รายงานเปรียบเทียบความเสี่ยงระหว่างช่วงเวลา
        Route::get('/comparison', [ReportController::class, 'comparison'])
            ->name('reports.comparison');
        
        // ส่งออกรายงานตามประเภทที่กำหนด (PDF, Excel)
        Route::get('/export/{type}', [ReportController::class, 'export'])
            ->name('reports.export');
    });

});

/**
 * นำเข้าไฟล์เส้นทางเพิ่มเติม
 * 
 * - settings.php: เส้นทางสำหรับการตั้งค่าระบบ
 * - auth.php: เส้นทางสำหรับระบบการยืนยันตัวตนและการจัดการบัญชีผู้ใช้
 */
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
