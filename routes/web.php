<?php

use App\Http\Controllers\DepartmentRiskController;
use App\Http\Controllers\OrganizationalRiskController;
use App\Http\Controllers\RiskAssessmentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('loginEGAT');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // Organizational Risk Routes
    Route::prefix('organizational-risks')->group(function () {
        Route::get('/', [OrganizationalRiskController::class, 'index'])->name('organizational-risks.index');
        Route::get('/create', [OrganizationalRiskController::class, 'create'])->name('organizational-risks.create');
        Route::post('/', [OrganizationalRiskController::class, 'store'])->name('organizational-risks.store');
        Route::get('/{organizationalRisk}/edit', [OrganizationalRiskController::class, 'edit'])->name('organizational-risks.edit');
        Route::put('/{organizationalRisk}', [OrganizationalRiskController::class, 'update'])->name('organizational-risks.update');
        Route::delete('/bulk-destroy', [OrganizationalRiskController::class, 'bulkDestroy'])->name('organizational-risks.bulk-destroy');
        Route::delete('/{organizationalRisk}', [OrganizationalRiskController::class, 'destroy'])->name('organizational-risks.destroy');
    });

    // Department Risk Routes
    Route::prefix('department-risks')->group(function () {
        Route::get('/', [DepartmentRiskController::class, 'index'])->name('department-risks.index');
        Route::get('/create', [DepartmentRiskController::class, 'create'])->name('department-risks.create');
        Route::post('/', [DepartmentRiskController::class, 'store'])->name('department-risks.store');
        Route::get('/{departmentRisk}/edit', [DepartmentRiskController::class, 'edit'])->name('department-risks.edit');
        Route::put('/{departmentRisk}', [DepartmentRiskController::class, 'update'])->name('department-risks.update');
        Route::delete('/{departmentRisk}', [DepartmentRiskController::class, 'destroy'])->name('department-risks.destroy');
        
        // Criteria Management
        Route::get('/{departmentRisk}/criteria', [DepartmentRiskController::class, 'manageCriteria'])->name('department-risks.criteria');
        Route::post('/{departmentRisk}/likelihood-criteria', [DepartmentRiskController::class, 'storeLikelihoodCriteria']);
        Route::post('/{departmentRisk}/impact-criteria', [DepartmentRiskController::class, 'storeImpactCriteria']);
    });

    // Risk Assessment Routes
    Route::prefix('risk-assessments')->group(function () {
        Route::get('/', [RiskAssessmentController::class, 'index'])->name('risk-assessments.index');
        Route::get('/create', [RiskAssessmentController::class, 'create'])->name('risk-assessments.create');
        Route::post('/', [RiskAssessmentController::class, 'store'])->name('risk-assessments.store');
        Route::get('/{riskAssessment}/edit', [RiskAssessmentController::class, 'edit'])->name('risk-assessments.edit');
        Route::put('/{riskAssessment}', [RiskAssessmentController::class, 'update'])->name('risk-assessments.update');
        Route::delete('/{riskAssessment}', [RiskAssessmentController::class, 'destroy'])->name('risk-assessments.destroy');
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/executive-summary', [ReportController::class, 'executiveSummary'])->name('reports.executive-summary');
        Route::get('/trend-analysis', [ReportController::class, 'trendAnalysis'])->name('reports.trend-analysis');
        Route::get('/comparison', [ReportController::class, 'comparison'])->name('reports.comparison');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('reports.export');
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
