<?php



use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SignupVerificationController;

use App\Http\Controllers\StoreProductController;
use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\SessionSecurityController;

use App\Http\Controllers\PresenceController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdminCatalogController;
use App\Http\Controllers\SuperAdminBackupController;

// React public pages
Route::view('/', 'react');
Route::view('/login', 'react');
Route::view('/signup', 'react');
Route::view('/signup-verify', 'react');
Route::view('/login-otp', 'react');

// React role pages
Route::view('/user', 'react');
Route::view('/super-admin', 'react');

// Authentication
Route::post('/login', [LoginController::class, 'login'])
    ->middleware(\App\Http\Middleware\ThrottleLoginAttempts::class);
Route::post('/login/verify-otp', [LoginOtpController::class, 'verify']);
Route::post('/login/resend-otp', [LoginOtpController::class, 'resend']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/signup/verify-otp', [SignupVerificationController::class, 'verify']);
Route::post('/signup/resend-otp', [SignupVerificationController::class, 'resend']);
Route::post('/logout', [LogoutController::class, 'logout']);

Route::get('/api/session/status', [SessionSecurityController::class, 'status']);
Route::post('/api/session/activity', [SessionSecurityController::class, 'activity']);
Route::post('/api/session/forget-device', [SessionSecurityController::class, 'forgetDevice']);

// Store / public API
Route::get('/api/store/products', [StoreProductController::class, 'index']);

// System User API
Route::get('/api/user/me', [SystemUserController::class, 'me']);
Route::get('/api/user/orders', [SystemUserController::class, 'orders']);
Route::post('/api/user/orders', [SystemUserController::class, 'placeOrder']);
Route::put('/api/user/profile', [SystemUserController::class, 'updateProfile']);
Route::put('/api/user/password', [SystemUserController::class, 'updatePassword']);


// Shared online/offline presence
Route::post('/api/presence/heartbeat', [PresenceController::class, 'heartbeat']);
Route::post('/api/presence/offline', [PresenceController::class, 'offline']);

// Super Admin dashboard + report
Route::get('/api/super-admin/dashboard-data', [SuperAdminDashboardController::class, 'index']);
Route::get('/api/super-admin/audit-logs', [SuperAdminDashboardController::class, 'auditLogs']);
Route::get('/api/super-admin/user-presence', [PresenceController::class, 'superAdminIndex']);
Route::get('/api/super-admin/export-report', [SuperAdminDashboardController::class, 'exportReport']);

// Super Admin account, company, users, notifications
Route::put('/api/super-admin/profile', [SuperAdminController::class, 'updateProfile']);
Route::put('/api/super-admin/password', [SuperAdminController::class, 'updatePassword']);
Route::post('/api/super-admin/company-information', [SuperAdminController::class, 'updateCompanyInformation']);
Route::post('/api/super-admin/users', [SuperAdminController::class, 'storeUser']);
Route::put('/api/super-admin/users/{userId}', [SuperAdminController::class, 'updateUser'])->whereNumber('userId');
Route::put('/api/super-admin/notifications/{notificationId}', [SuperAdminController::class, 'updateNotification'])->whereNumber('notificationId');

// Super Admin catalog / inventory / purchasing creation
Route::post('/api/super-admin/products', [SuperAdminCatalogController::class, 'storeProduct']);
Route::post('/api/super-admin/categories', [SuperAdminCatalogController::class, 'storeCategory']);
Route::post('/api/super-admin/stock-in', [SuperAdminCatalogController::class, 'stockIn']);
Route::post('/api/super-admin/suppliers', [SuperAdminCatalogController::class, 'storeSupplier']);
Route::post('/api/super-admin/purchase-orders', [SuperAdminCatalogController::class, 'storePurchaseOrder']);

// Super Admin backup / restore
Route::post('/api/super-admin/backups', [SuperAdminBackupController::class, 'createBackup']);
Route::get('/api/super-admin/backups/{filename}/download', [SuperAdminBackupController::class, 'downloadBackup'])
    ->where('filename', '[A-Za-z0-9._-]+');
Route::post('/api/super-admin/backups/{filename}/restore', [SuperAdminBackupController::class, 'restoreBackup'])
    ->where('filename', '[A-Za-z0-9._-]+');
