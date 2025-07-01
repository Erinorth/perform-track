<?php

namespace App\Providers;

use App\Models\DivisionRisk;
use App\Models\ImpactCriterion;
use App\Models\LikelihoodCriterion;
use App\Models\OrganizationalRisk;
use App\Models\RiskAssessment;
use App\Models\RiskControl;
use App\Models\User;
use App\Policies\DashboardPolicy;
use App\Policies\DivisionRiskPolicy;
use App\Policies\ImpactCriterionPolicy;
use App\Policies\LikelihoodCriterionPolicy;
use App\Policies\OrganizationalRiskPolicy;
use App\Policies\RiskAssessmentPolicy;
use App\Policies\RiskControlPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * กำหนด Policy mappings สำหรับแอปพลิเคชัน
     */
    protected $policies = [
        User::class => UserPolicy::class,
        OrganizationalRisk::class => OrganizationalRiskPolicy::class,
        DivisionRisk::class => DivisionRiskPolicy::class,
        RiskAssessment::class => RiskAssessmentPolicy::class,
        RiskControl::class => RiskControlPolicy::class,
        LikelihoodCriterion::class => LikelihoodCriterionPolicy::class,
        ImpactCriterion::class => ImpactCriterionPolicy::class,
    ];

    /**
     * Register สิทธิ์และ gate ต่างๆ
     */
    public function boot(): void
    {
        Log::info('กำลังโหลด AuthServiceProvider');

        // Register policies
        $this->registerPolicies();

        // === Dashboard Gates ===
        Gate::define('dashboard.view', function (User $user) {
            return $user->hasPermissionTo('dashboard.view');
        });

        Gate::define('dashboard.viewAll', function (User $user) {
            return $user->hasPermissionTo('dashboard.view_all');
        });

        Gate::define('dashboard.viewDivision', function (User $user) {
            return $user->hasPermissionTo('dashboard.view_division');
        });

        Gate::define('dashboard.viewDepartment', function (User $user) {
            return $user->hasPermissionTo('dashboard.view_department');
        });

        Gate::define('dashboard.export', function (User $user) {
            return $user->hasPermissionTo('dashboard.export');
        });

        // === User Management Gates ===
        Gate::define('user.viewAny', function (User $user) {
            return $user->hasPermissionTo('user.view');
        });

        Gate::define('user.create', function (User $user) {
            return $user->hasPermissionTo('user.create');
        });

        Gate::define('user.update', function (User $user) {
            return $user->hasPermissionTo('user.update');
        });

        Gate::define('user.delete', function (User $user) {
            return $user->hasPermissionTo('user.delete');
        });

        Gate::define('user.managePermissions', function (User $user) {
            return $user->hasPermissionTo('user.manage_permissions');
        });

        Gate::define('user.manageRoles', function (User $user) {
            return $user->hasPermissionTo('user.manage_roles');
        });

        // === Settings Gates ===
        Gate::define('settings.view', function (User $user) {
            return $user->hasPermissionTo('settings.view');
        });

        Gate::define('settings.update', function (User $user) {
            return $user->hasPermissionTo('settings.update');
        });

        Gate::define('settings.system', function (User $user) {
            return $user->hasPermissionTo('settings.system');
        });

        Gate::define('settings.backup', function (User $user) {
            return $user->hasPermissionTo('settings.backup');
        });

        Gate::define('settings.restore', function (User $user) {
            return $user->hasPermissionTo('settings.restore');
        });

        // === Report Gates ===
        Gate::define('report.view', function (User $user) {
            return $user->hasPermissionTo('report.view');
        });

        Gate::define('report.create', function (User $user) {
            return $user->hasPermissionTo('report.create');
        });

        Gate::define('report.export', function (User $user) {
            return $user->hasPermissionTo('report.export');
        });

        Gate::define('report.schedule', function (User $user) {
            return $user->hasPermissionTo('report.schedule');
        });

        // === Attachment Gates ===
        Gate::define('attachment.view', function (User $user) {
            return $user->hasPermissionTo('attachment.view');
        });

        Gate::define('attachment.upload', function (User $user) {
            return $user->hasPermissionTo('attachment.upload');
        });

        Gate::define('attachment.download', function (User $user) {
            return $user->hasPermissionTo('attachment.download');
        });

        Gate::define('attachment.delete', function (User $user) {
            return $user->hasPermissionTo('attachment.delete');
        });

        // === Super Admin Gate ===
        Gate::before(function (User $user, string $ability) {
            // Super Admin มีสิทธิ์ทั้งหมด
            if ($user->hasRole('super_admin')) {
                Log::info("Super Admin bypass gate: {$ability} for User ID: {$user->id}");
                return true;
            }
        });

        Log::info('โหลด AuthServiceProvider เสร็จสิ้น');
    }
}
