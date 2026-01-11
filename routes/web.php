<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminEventStaffController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffProfileController;
use App\Http\Middleware\AuthenticateStaff;
use App\Http\Middleware\ShareStaffData;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Home page with optional staff recognition
Route::middleware([ShareStaffData::class])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});

/*
|--------------------------------------------------------------------------
| Staff Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/staff/register', [StaffAuthController::class, 'register'])->name('staff.register');
Route::post('/staff/login', [StaffAuthController::class, 'requestLogin'])->name('staff.login.request');
Route::get('/staff/login/{token}', [StaffAuthController::class, 'loginWithToken'])->name('staff.login.token');
Route::post('/staff/logout', [StaffAuthController::class, 'logout'])->name('staff.logout');


/*
|--------------------------------------------------------------------------
| Staff Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware([AuthenticateStaff::class])->group(function () {
    // Staff profile
    Route::get('/staff', [StaffProfileController::class, 'show'])->name('staff.profile');
    Route::patch('/staff', [StaffProfileController::class, 'update'])->name('staff.profile.update');
    Route::post('/staff/photo', [StaffProfileController::class, 'uploadPhoto'])->name('staff.profile.photo');
    Route::delete('/staff/photo', [StaffProfileController::class, 'deletePhoto'])->name('staff.profile.photo.delete');
    Route::delete('/staff/data', [StaffProfileController::class, 'anonymize'])->name('staff.profile.anonymize');

    // Event registration
    Route::get('/event/{tagname}', [EventRegistrationController::class, 'show'])->name('event.show');
    Route::post('/event/{tagname}/register', [EventRegistrationController::class, 'register'])->name('event.register');
    Route::delete('/event/{tagname}/register', [EventRegistrationController::class, 'cancel'])->name('event.cancel');
    Route::patch('/event/{tagname}', [EventRegistrationController::class, 'update'])->name('event.update');
    Route::patch('/event/{tagname}/roles', [EventRegistrationController::class, 'updateRolePreferences'])->name('event.roles');
    Route::patch('/event/{tagname}/availability', [EventRegistrationController::class, 'updateAvailability'])->name('event.availability');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/preferences', [AdminDashboardController::class, 'updatePreferences'])->name('preferences.update');
    Route::post('/preferences/logo', [AdminDashboardController::class, 'uploadLogo'])->name('preferences.logo');

    // Events
    Route::post('/event', [AdminEventController::class, 'store'])->name('event.store');
    Route::get('/event/{tagname}', [AdminEventController::class, 'show'])->name('event.show');
    Route::patch('/event/{tagname}', [AdminEventController::class, 'update'])->name('event.update');
    Route::delete('/event/{tagname}', [AdminEventController::class, 'destroy'])->name('event.destroy');
    Route::post('/event/{tagname}/copy', [AdminEventController::class, 'copy'])->name('event.copy');
    Route::post('/event/{tagname}/logo', [AdminEventController::class, 'uploadLogo'])->name('event.logo');

    // Event roles
    Route::post('/event/{tagname}/role', [AdminEventController::class, 'addRole'])->name('event.role.add');
    Route::patch('/event/{tagname}/role/{roleId}', [AdminEventController::class, 'updateRole'])->name('event.role.update');
    Route::delete('/event/{tagname}/role/{roleId}', [AdminEventController::class, 'deleteRole'])->name('event.role.delete');

    // Event days
    Route::patch('/event/{tagname}/day/{dayId}', [AdminEventController::class, 'updateDaySchedule'])->name('event.day.update');

    // Event staff management
    Route::get('/event/{tagname}/staff', [AdminEventStaffController::class, 'index'])->name('event.staff');
    Route::post('/event/{tagname}/staff/{registrationId}/validate', [AdminEventStaffController::class, 'validate'])->name('event.staff.validate');
    Route::post('/event/{tagname}/staff/{registrationId}/role', [AdminEventStaffController::class, 'assignRole'])->name('event.staff.role');
    Route::post('/event/{tagname}/reminder', [AdminEventStaffController::class, 'sendReminder'])->name('event.reminder');
});

require __DIR__.'/settings.php';
