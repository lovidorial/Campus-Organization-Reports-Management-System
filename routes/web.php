<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpoaActivityController;
use App\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

// Welcome page
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Public Routes
Route::get('/activities', [ActivityController::class, 'publicActivities'])->name('public.activities');

// Auth Routes
require __DIR__ . '/auth.php';

// Authenticated User Routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Activity Management
    Route::get('/submit-activity', [ActivityController::class, 'create'])->name('user.submit');
    Route::post('/store-activity', [ActivityController::class, 'store'])->name('user.store');
    Route::get('/my-activities', [ActivityController::class, 'index'])->name('user.activities');

    // GPOA Activity Submission
    Route::get('/gpoa/create', [GpoaActivityController::class, 'create'])->name('gpoa.create');
    Route::post('/gpoa/store', [GpoaActivityController::class, 'store'])->name('gpoa.store');

    // Profile management
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Only Routes
    Route::middleware([\App\Http\Middleware\AdminMiddlerware::class])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/activities', [AdminController::class, 'monitor'])->name('activities');
        Route::get('/approve/{id}', [AdminController::class, 'approve'])->name('approve');
        Route::post('/reject/{id}', [AdminController::class, 'reject'])->name('reject');
        Route::get('/activities/export/{format}', [AdminController::class, 'exportActivities'])->name('activities.export');
        Route::get('/file/view/{activityId}/{fileType}', [AdminController::class, 'viewFile'])->name('file.view');
        Route::get('/file/download/{activityId}/{fileType}', [AdminController::class, 'downloadFile'])->name('file.download');

        // User management
        Route::get('/users', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [\App\Http\Controllers\AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [\App\Http\Controllers\AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'destroy'])->name('users.destroy');

        // Organization management
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
