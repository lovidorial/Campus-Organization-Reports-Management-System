<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\ActivityRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminGpoaController;
use App\Http\Controllers\AdminWorkflowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpoaController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\WorkflowDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/activities', [ActivityController::class, 'publicActivities'])->name('public.activities');

require __DIR__ . '/auth.php';

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // GPOA Management
    Route::get('/gpoa', [GpoaController::class, 'index'])->name('gpoa.index');
    Route::get('/gpoa/create', [GpoaController::class, 'create'])->name('gpoa.create');
    Route::post('/gpoa/store', [GpoaController::class, 'store'])->name('gpoa.store');
    Route::get('/gpoa/{gpoa}', [GpoaController::class, 'show'])->name('gpoa.show');
    Route::get('/gpoa/{gpoa}/edit', [GpoaController::class, 'edit'])->name('gpoa.edit');
    Route::put('/gpoa/{gpoa}', [GpoaController::class, 'update'])->name('gpoa.update');

    // Workflow Documents
    Route::get('/workflow/communication-letter', [WorkflowDocumentController::class, 'communicationLetter'])->name('workflow.communication-letter');
    Route::post('/workflow/communication-letter', [WorkflowDocumentController::class, 'storeCommunicationLetter'])->name('workflow.communication-letter.store');
    Route::get('/workflow/summary-report', [WorkflowDocumentController::class, 'summaryReport'])->name('workflow.summary-report');
    Route::post('/workflow/summary-report', [WorkflowDocumentController::class, 'storeSummaryReport'])->name('workflow.summary-report.store');
    Route::get('/notifications', [WorkflowDocumentController::class, 'notifications'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [WorkflowDocumentController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [WorkflowDocumentController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // Activity Requests (requires approved GPOA)
    Route::middleware([\App\Http\Middleware\EnsureApprovedGpoa::class])->group(function () {
        Route::get('/activity-requests', [ActivityRequestController::class, 'index'])->name('activity-requests.index');
        Route::get('/activity-requests/create', [ActivityRequestController::class, 'create'])->name('activity-requests.create');
        Route::post('/activity-requests', [ActivityRequestController::class, 'store'])->name('activity-requests.store');
        Route::get('/activity-requests/{activityRequest}/report', [ActivityReportController::class, 'create'])->name('activity-reports.create');
        Route::post('/activity-requests/{activityRequest}/report', [ActivityReportController::class, 'store'])->name('activity-reports.store');
    });

    // Legacy routes redirect
    Route::get('/submit-activity', fn () => redirect()->route('activity-requests.create'))->name('user.submit');
    Route::get('/my-activities', fn () => redirect()->route('activity-requests.index'))->name('user.activities');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware([\App\Http\Middleware\AdminMiddlerware::class])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/activities', [AdminController::class, 'monitor'])->name('activities');
        Route::get('/approve/{id}', [AdminController::class, 'approve'])->name('approve');
        Route::post('/reject/{id}', [AdminController::class, 'reject'])->name('reject');
        Route::post('/monitoring/{id}/record', [AdminController::class, 'recordMonitoring'])->name('monitoring.record');
        Route::get('/activities/export/{format}', [AdminController::class, 'exportActivities'])->name('activities.export');
        Route::get('/file/view/{activityId}/{fileType}', [AdminController::class, 'viewFile'])->name('file.view');
        Route::get('/file/download/{activityId}/{fileType}', [AdminController::class, 'downloadFile'])->name('file.download');

        Route::get('/gpoa', [AdminGpoaController::class, 'index'])->name('gpoa.index');
        Route::get('/gpoa/{gpoa}', [AdminGpoaController::class, 'show'])->name('gpoa.show');
        Route::post('/gpoa/{gpoa}/approve', [AdminGpoaController::class, 'approve'])->name('gpoa.approve');
        Route::post('/gpoa/{gpoa}/reject', [AdminGpoaController::class, 'reject'])->name('gpoa.reject');
        Route::get('/gpoa/{gpoa}/document', [AdminController::class, 'viewGpoaDocument'])->name('gpoa.document');

        Route::get('/workflows', [AdminWorkflowController::class, 'index'])->name('workflows.index');
        Route::get('/workflows/export', [AdminWorkflowController::class, 'export'])->name('workflows.export');
        Route::get('/workflows/{workflow}', [AdminWorkflowController::class, 'show'])->name('workflows.show');
        Route::post('/workflows/{workflow}/reopen', [AdminWorkflowController::class, 'reopen'])->name('workflows.reopen');
        Route::post('/workflow-submissions/{submission}/approve', [AdminWorkflowController::class, 'approveSubmission'])->name('workflows.submissions.approve');
        Route::post('/workflow-submissions/{submission}/reject', [AdminWorkflowController::class, 'rejectSubmission'])->name('workflows.submissions.reject');
        Route::get('/workflow-submissions/{submission}/document', [AdminWorkflowController::class, 'viewDocument'])->name('workflows.submissions.document');

        Route::get('/users', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [\App\Http\Controllers\AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [\App\Http\Controllers\AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::resource('/organizations', OrganizationController::class)->names([
            'index'   => 'organizations.index',
            'create'  => 'organizations.create',
            'store'   => 'organizations.store',
            'show'    => 'organizations.show',
            'edit'    => 'organizations.edit',
            'update'  => 'organizations.update',
            'destroy' => 'organizations.destroy',
        ]);
    });
});
