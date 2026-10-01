<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityCalendarController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\ActivityRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDocumentDeadlineController;
use App\Http\Controllers\AdminGpoaController;
use App\Http\Controllers\AdminSummaryReportController;
use App\Http\Controllers\AdminWorkflowController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpoaController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PublicOrgChartController;
use App\Http\Controllers\WorkflowDocumentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    $organizations = \App\Models\Organization::where('is_active', true)->orderBy('name')->get();

    return view('welcome', compact('organizations'));
})->name('welcome');

Route::get('/storage/{path}', function (string $path) {
    $safePath = str_replace(['../', '..\\'], '', $path);

    abort_unless(Storage::disk('public')->exists($safePath), 404);

    return response()->file(Storage::disk('public')->path($safePath));
})->where('path', '.*');

Route::get('/activities', [ActivityController::class, 'publicActivities'])->name('public.activities');
Route::get('/org-chart', [PublicOrgChartController::class, 'index'])->name('public.orgchart');

require __DIR__ . '/auth.php';

Route::middleware(['auth', \App\Http\Middleware\EnforceOrganizationStorageLimit::class])->group(function () {

    Route::get('/terms', [\App\Http\Controllers\TermsController::class, 'show'])->name('terms.accept');
    Route::post('/terms/accept', [\App\Http\Controllers\TermsController::class, 'accept'])->name('terms.accept.store');

    Route::middleware(['terms.accepted'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/activities/calendar', [ActivityCalendarController::class, 'index'])->name('activities.calendar');
        Route::get('/faq', function (\Illuminate\Http\Request $request) {
            return view('faq.index', [
                'isAdmin' => $request->user()?->isAdmin() ?? false,
            ]);
        })->name('faq');
        Route::get('/my-backup', [\App\Http\Controllers\UserBackupController::class, 'index'])->name('my-backup.index');
        Route::post('/my-backup/export', [\App\Http\Controllers\UserBackupController::class, 'export'])->name('my-backup.export');
        Route::get('/my-backup/download/{filename}', [\App\Http\Controllers\UserBackupController::class, 'download'])->name('my-backup.download');

        // GPOA Management
        Route::get('/gpoa', [GpoaController::class, 'index'])->name('gpoa.index');
        Route::get('/gpoa/create', [GpoaController::class, 'create'])->name('gpoa.create');
        Route::post('/gpoa/import-preview', [GpoaController::class, 'importPreview'])->middleware('throttle:10,1')->name('gpoa.import-preview');
        Route::post('/gpoa/store', [GpoaController::class, 'store'])->name('gpoa.store');
        Route::get('/gpoa/{gpoa}', [GpoaController::class, 'show'])->name('gpoa.show');
        Route::get('/gpoa/{gpoa}/edit', [GpoaController::class, 'edit'])->name('gpoa.edit');
        Route::put('/gpoa/{gpoa}', [GpoaController::class, 'update'])->name('gpoa.update');
        // Notifications remain available; organization-level workflow documents/history are retired.
        Route::get('/notifications', [WorkflowDocumentController::class, 'notifications'])->name('notifications.index');
        Route::get('/notifications/unread-count', [WorkflowDocumentController::class, 'unreadNotificationCount'])->name('notifications.unread-count');
        Route::patch('/notifications/{notification}/read', [WorkflowDocumentController::class, 'markNotificationRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [WorkflowDocumentController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

        // Activity Requests
        Route::get('/activity-requests', [ActivityRequestController::class, 'index'])->name('activity-requests.index');
        Route::get('/activity-monitor', [ActivityRequestController::class, 'monitor'])->name('activity-monitor.index');
        Route::get('/activity-requests/statuses', [ActivityRequestController::class, 'statuses'])->name('activity-requests.statuses');
        Route::get('/activity-requests/create', [ActivityRequestController::class, 'create'])->name('activity-requests.create');
        Route::post('/activity-requests', [ActivityRequestController::class, 'store'])->name('activity-requests.store');
        Route::get('/activity-requests/{activityRequest}', [ActivityRequestController::class, 'show'])->name('activity-requests.show');
        Route::get('/activity-requests/{activityRequest}/pdf', [ActivityRequestController::class, 'downloadPdf'])->name('activity-requests.pdf');
        Route::get('/activity-requests/{activityRequest}/report', [ActivityReportController::class, 'create'])->name('activity-reports.create');
        Route::post('/activity-requests/{activityRequest}/communication-letter', [\App\Http\Controllers\ActivityDocumentController::class, 'storeCommunicationLetter'])->name('activity-requests.communication-letter.store');
        Route::get('/activity-requests/{activityRequest}/documents/{documentType}', [\App\Http\Controllers\ActivityDocumentController::class, 'show'])
            ->whereIn('documentType', ['communication-letter', 'narrative-report'])
            ->name('activity-requests.documents.show');
        Route::post('/activity-requests/{activityRequest}/report', [ActivityReportController::class, 'store'])->name('activity-reports.store');

        Route::get('/organization/officers', [\App\Http\Controllers\OfficerController::class, 'userIndex'])->name('organization.officers.index');
        Route::post('/organization/officers/{user}/archive', [\App\Http\Controllers\OfficerController::class, 'archive'])->name('organization.officers.archive');
        Route::post('/organization/officers/{user}/restore', [\App\Http\Controllers\OfficerController::class, 'restore'])->name('organization.officers.restore');
        Route::get('/organization/officers/history', [\App\Http\Controllers\OfficerController::class, 'userHistory'])->name('organization.officers.history');

        Route::get('/organization/members', [\App\Http\Controllers\OrganizationMemberController::class, 'index'])->name('organization.members.index');
        Route::post('/organization/members', [\App\Http\Controllers\OrganizationMemberController::class, 'store'])->name('organization.members.store');
        Route::patch('/organization/members/{member}', [\App\Http\Controllers\OrganizationMemberController::class, 'update'])->name('organization.members.update');
        Route::delete('/organization/members/{member}', [\App\Http\Controllers\OrganizationMemberController::class, 'destroy'])->name('organization.members.destroy');

        // Legacy routes redirect
        Route::get('/submit-activity', fn () => redirect()->route('activity-requests.create'))->name('user.submit');
        Route::get('/my-activities', fn () => redirect()->route('activity-requests.index'))->name('user.activities');

        Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::middleware([\App\Http\Middleware\AdminMiddlerware::class])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [AdminController::class, 'monitoringDashboard'])->name('dashboard');
        Route::get('/activities', [AdminController::class, 'monitor'])->name('activities');
        Route::get('/activities/statuses', [AdminController::class, 'activityStatuses'])->name('activities.statuses');
        Route::get('/summary-report', [AdminSummaryReportController::class, 'index'])->name('summary-report');
        Route::get('/summary-report/pdf', [AdminSummaryReportController::class, 'downloadPdf'])->name('summary-report.pdf');
        Route::get('/summary-report/download', [AdminSummaryReportController::class, 'download'])->name('summary-report.download');
        Route::post('/monitoring/{id}/record', [AdminController::class, 'recordMonitoring'])->name('monitoring.record');
        Route::post('/reports/{activityReport}/approve', [AdminController::class, 'approveReport'])->name('reports.approve');
        Route::post('/reports/{activityReport}/request-revision', [AdminController::class, 'requestReportRevision'])->name('reports.request-revision');
        Route::get('/reports/{activityReport}/evidence/{evidence}', [AdminController::class, 'viewReportEvidence'])->name('reports.evidence');
        Route::get('/activities/export/{format}', [AdminController::class, 'exportActivities'])->name('activities.export');
        Route::get('/file/view/{activityId}/{fileType}', [AdminController::class, 'viewFile'])->name('file.view');
        Route::get('/file/download/{activityId}/{fileType}', [AdminController::class, 'downloadFile'])->name('file.download');

        Route::get('/gpoa', [AdminGpoaController::class, 'index'])->name('gpoa.index');
        Route::get('/gpoa/{gpoa}', [AdminGpoaController::class, 'show'])->name('gpoa.show');
        Route::get('/gpoa/{gpoa}/document', [AdminController::class, 'viewGpoaDocument'])->name('gpoa.document');

        Route::get('/workflows', [AdminWorkflowController::class, 'index'])->name('workflows.index');
        Route::get('/workflows/export', [AdminWorkflowController::class, 'export'])->name('workflows.export');
        Route::get('/workflows/{workflow}', [AdminWorkflowController::class, 'show'])->name('workflows.show');
        Route::get('/workflow-submissions/{submission}/document', [AdminWorkflowController::class, 'viewDocument'])->name('workflows.submissions.document');

        Route::get('/users', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [\App\Http\Controllers\AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [\App\Http\Controllers\AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/officers', [\App\Http\Controllers\OfficerController::class, 'index'])->name('officers.index');
        Route::get('/officers/replacement/create', [\App\Http\Controllers\OfficerController::class, 'create'])->name('officers.replacement.create');
        Route::get('/officers/{officer}/replacement/create', [\App\Http\Controllers\OfficerController::class, 'create'])->name('officers.replacement.create.with-officer');
        Route::post('/officers/replacement', [\App\Http\Controllers\OfficerController::class, 'storeReplacement'])->name('officers.replacement.store');
        Route::get('/officers/replacement/success', [\App\Http\Controllers\OfficerController::class, 'replacementSuccess'])->name('officers.replacement.success');
        Route::post('/officers/{user}/archive', [\App\Http\Controllers\OfficerController::class, 'archive'])->name('officers.archive');
        Route::post('/officers/{user}/restore', [\App\Http\Controllers\OfficerController::class, 'restore'])->name('officers.restore');
        Route::get('/officers/history', [\App\Http\Controllers\OfficerController::class, 'history'])->name('officers.history');

        Route::resource('/organizations', OrganizationController::class)->names([
            'index'   => 'organizations.index',
            'create'  => 'organizations.create',
            'store'   => 'organizations.store',
            'show'    => 'organizations.show',
            'edit'    => 'organizations.edit',
            'update'  => 'organizations.update',
            'destroy' => 'organizations.destroy',
        ]);

        Route::get('/organization-classifications', [\App\Http\Controllers\AdminOrganizationClassificationController::class, 'index'])->name('organization-classifications.index');
        Route::get('/organization-classifications/create', [\App\Http\Controllers\AdminOrganizationClassificationController::class, 'create'])->name('organization-classifications.create');
        Route::post('/organization-classifications', [\App\Http\Controllers\AdminOrganizationClassificationController::class, 'store'])->name('organization-classifications.store');
        Route::get('/organization-classifications/{organizationClassification}/edit', [\App\Http\Controllers\AdminOrganizationClassificationController::class, 'edit'])->name('organization-classifications.edit');
        Route::patch('/organization-classifications/{organizationClassification}', [\App\Http\Controllers\AdminOrganizationClassificationController::class, 'update'])->name('organization-classifications.update');
        Route::delete('/organization-classifications/{organizationClassification}', [\App\Http\Controllers\AdminOrganizationClassificationController::class, 'destroy'])->name('organization-classifications.destroy');
        Route::get('/organization-classifications/classify', [\App\Http\Controllers\AdminOrganizationClassificationController::class, 'classify'])->name('organization-classifications.classify');

        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::get('/maintenance', [\App\Http\Controllers\AdminMaintenanceController::class, 'index'])->name('maintenance.index');
        Route::get('/document-deadlines', [AdminDocumentDeadlineController::class, 'index'])->name('document-deadlines.index');
        Route::put('/document-deadlines', [AdminDocumentDeadlineController::class, 'update'])->name('document-deadlines.update');
        Route::get('/activity-logs', [\App\Http\Controllers\AdminActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
        Route::put('/backups/schedule', [BackupController::class, 'updateSchedule'])->name('backups.schedule');
        Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');

        Route::post('/organizations/{organization}/deactivate', [OrganizationController::class, 'deactivate'])->name('organizations.deactivate');
        Route::post('/organizations/{organization}/reset-password', [OrganizationController::class, 'resetPassword'])->name('organizations.reset-password');
    });
});
