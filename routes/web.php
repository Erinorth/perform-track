<?php
/**
 * ไฟล์: routes/web.php
 * คำอธิบาย: ไฟล์กำหนดเส้นทาง (routes) สำหรับการเข้าถึงหน้าต่างๆ ในระบบประเมินความเสี่ยง
 * เทคโนโลยี: Laravel 12 + Inertia.js + Vue 3
 */

// นำเข้า Controllers ที่เกี่ยวข้องและคลาสพื้นฐานที่จำเป็น
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionRiskController;
use App\Http\Controllers\OrganizationalRiskController;
use App\Http\Controllers\RiskAssessmentController;
use App\Http\Controllers\RiskControlController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * เส้นทางหลักของระบบ (หน้าแรก)
 */
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('loginEGAT');
})->name('home');

/**
 * เส้นทางแดชบอร์ด
 */
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

/**
 * กลุ่มเส้นทางที่ต้องผ่านการรับรองตัวตน
 */
Route::middleware('auth')->group(function () {

    /**
     * จัดการความเสี่ยงระดับองค์กร - ใช้ resource routes
     * สร้างเส้นทาง index, create, store, show, edit, update, destroy อัตโนมัติ
     */
    Route::resource('organizational-risks', OrganizationalRiskController::class);
    
    // กลุ่มเส้นทางสำหรับจัดการไฟล์แนบของความเสี่ยงระดับองค์กร
    Route::prefix('organizational-risks/{organizationalRisk}/attachments')->name('organizational-risks.attachments.')->group(function () {
        Route::post('/', [OrganizationalRiskController::class, 'storeAttachment'])->name('store');
        Route::delete('/{attachmentId}', [OrganizationalRiskController::class, 'destroyAttachment'])->name('destroy');
        Route::get('/{attachmentId}/download', [OrganizationalRiskController::class, 'downloadAttachment'])->name('download');
        Route::get('/{attachmentId}/view', [OrganizationalRiskController::class, 'viewAttachment'])->name('view');
    });

    /**
     * จัดการความเสี่ยงระดับฝ่าย - ใช้ resource routes
     */
    Route::resource('division-risks', DivisionRiskController::class);
    
    // กลุ่มเส้นทางสำหรับจัดการไฟล์แนบของความเสี่ยงระดับฝ่าย
    Route::prefix('division-risks/{divisionRisk}/attachments')->name('division-risks.attachments.')->group(function () {
        Route::post('/', [DivisionRiskController::class, 'storeAttachment'])->name('store');
        Route::delete('/{attachmentId}', [DivisionRiskController::class, 'destroyAttachment'])->name('destroy');
        Route::get('/{attachmentId}/download', [DivisionRiskController::class, 'downloadAttachment'])->name('download');
        Route::get('/{attachmentId}/view', [DivisionRiskController::class, 'viewAttachment'])->name('view');
    });
    
    // กลุ่มเส้นทางสำหรับจัดการเกณฑ์การประเมินความเสี่ยงระดับฝ่าย
    Route::prefix('division-risks/{divisionRisk}')->name('division-risks.')->group(function () {
        Route::get('/criteria', [DivisionRiskController::class, 'manageCriteria'])->name('criteria');
        Route::post('/likelihood-criteria', [DivisionRiskController::class, 'storeLikelihoodCriteria'])->name('likelihood-criteria.store');
        Route::post('/impact-criteria', [DivisionRiskController::class, 'storeImpactCriteria'])->name('impact-criteria.store');
    });

    /**
     * จัดการการประเมินความเสี่ยง - ใช้ resource routes
     */
    Route::resource('risk-assessments', RiskAssessmentController::class);
    
    // กลุ่มเส้นทางสำหรับจัดการไฟล์แนบของการประเมินความเสี่ยง
    Route::prefix('risk-assessments/{riskAssessment}/attachments')->name('risk-assessments.attachments.')->group(function () {
        Route::post('/', [RiskAssessmentController::class, 'storeAttachment'])->name('store');
        Route::delete('/{attachmentId}', [RiskAssessmentController::class, 'destroyAttachment'])->name('destroy');
        Route::get('/{attachmentId}/download', [RiskAssessmentController::class, 'downloadAttachment'])->name('download');
        Route::get('/{attachmentId}/view', [RiskAssessmentController::class, 'viewAttachment'])->name('view');
    });

    /**
     * จัดการการควบคุมความเสี่ยง - ใช้ resource routes
     */
    Route::resource('risk-controls', RiskControlController::class);
    
    // กลุ่มเส้นทางสำหรับจัดการไฟล์แนบของการประเมินความเสี่ยง
    Route::prefix('risk-controls/{riskControl}/attachments')->name('risk-controls.attachments.')->group(function () {
        Route::post('/', [RiskControlController::class, 'storeAttachment'])->name('store');
        Route::delete('/{attachmentId}', [RiskControlController::class, 'destroyAttachment'])->name('destroy');
        Route::get('/{attachmentId}/download', [RiskControlController::class, 'downloadAttachment'])->name('download');
        Route::get('/{attachmentId}/view', [RiskControlController::class, 'viewAttachment'])->name('view');
    });
});

/**
 * นำเข้าไฟล์เส้นทางเพิ่มเติม
 */
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
