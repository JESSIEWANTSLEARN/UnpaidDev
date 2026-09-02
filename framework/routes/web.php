<?php



use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SignupVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;

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
Route::view('/forgot-password', 'react');

// React role pages
Route::view('/faq', 'react');
// Customer dashboard must never render for a logged-out visitor.
Route::get('/user', function () {
    if (
        session('logged_in') !== true ||
        session('role') !== 'System_User'
    ) {
        return redirect('/login');
    }

    return view('react');
});
// Super Admin dashboard is restricted to the Super Admin role.
Route::get('/super-admin', function () {
    if (
        session('logged_in') !== true ||
        session('role') !== 'super_admin'
    ) {
        return redirect('/login');
    }

    return view('react');
});
Route::get('/store-preview', function () {
    if (
        session('logged_in') !== true ||
        session('role') !== 'super_admin'
    ) {
        return redirect('/login');
    }

    return view('react');
});

// Authentication
Route::post('/login', [LoginController::class, 'login'])
    ->middleware(\App\Http\Middleware\ThrottleLoginAttempts::class);
Route::post('/login/verify-otp', [LoginOtpController::class, 'verify']);
Route::post('/login/resend-otp', [LoginOtpController::class, 'resend']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/signup/verify-otp', [SignupVerificationController::class, 'verify']);
Route::post('/signup/resend-otp', [SignupVerificationController::class, 'resend']);

Route::post('/forgot-password/request', [PasswordResetController::class, 'requestReset']);
Route::post('/forgot-password/verify-otp', [PasswordResetController::class, 'verifyOtp']);
Route::post('/forgot-password/resend-otp', [PasswordResetController::class, 'resend']);
Route::post('/forgot-password/reset', [PasswordResetController::class, 'resetPassword']);

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
Route::get('/api/user/notifications', [SystemUserController::class, 'notifications']);
Route::put('/api/user/notifications/read-all', [SystemUserController::class, 'readAllNotifications']);
Route::put('/api/user/notifications/{notificationId}/read', [SystemUserController::class, 'readNotification'])->whereNumber('notificationId');
Route::put('/api/user/profile', [SystemUserController::class, 'updateProfile']);
Route::put('/api/user/delivery-address', [SystemUserController::class, 'updateDeliveryAddress']);
Route::get('/api/user/profile-photo', [SystemUserController::class, 'profilePhoto']);
Route::post('/api/user/profile-photo', [SystemUserController::class, 'updateProfilePhoto']);
Route::delete('/api/user/profile-photo', [SystemUserController::class, 'deleteProfilePhoto']);
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
Route::get('/api/super-admin/users/{userId}/sessions', [SuperAdminController::class, 'userSessions'])->whereNumber('userId');
Route::delete('/api/super-admin/users/{userId}/sessions/{sessionId}', [SuperAdminController::class, 'revokeUserSession'])->whereNumber('userId');
Route::delete('/api/super-admin/users/{userId}/sessions', [SuperAdminController::class, 'revokeAllUserSessions'])->whereNumber('userId');
Route::delete('/api/super-admin/users/{userId}', [SuperAdminController::class, 'deleteUser'])->whereNumber('userId');
Route::put('/api/super-admin/notifications/{notificationId}', [SuperAdminController::class, 'updateNotification'])->whereNumber('notificationId');

// Super Admin catalog / inventory / purchasing creation
Route::post('/api/super-admin/products', [SuperAdminCatalogController::class, 'storeProduct']);
Route::put('/api/super-admin/products/{productId}', [SuperAdminCatalogController::class, 'updateProduct'])->whereNumber('productId');
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


// =========================================================
// PHASE 3 - PUBLIC / SUPER ADMIN WEBSITE CONTENT
// =========================================================
// Website Content management is restricted to Super Admin.
Route::get('/super-admin/content', function () {
    if (
        session('logged_in') !== true ||
        session('role') !== 'super_admin'
    ) {
        return redirect('/login');
    }

    return view('react');
});

Route::get(
    '/api/public/website-content',
    [\App\Http\Controllers\WebsiteContentController::class, 'publicIndex']
);
Route::get(
    '/api/public/team/{teamMemberId}/photo',
    [\App\Http\Controllers\WebsiteContentController::class, 'teamPhoto']
)->whereNumber('teamMemberId');

Route::get(
    '/api/super-admin/website-content',
    [\App\Http\Controllers\WebsiteContentController::class, 'adminIndex']
);
Route::put(
    '/api/super-admin/website-content/about',
    [\App\Http\Controllers\WebsiteContentController::class, 'updateAbout']
);
Route::post(
    '/api/super-admin/website-content/faqs',
    [\App\Http\Controllers\WebsiteContentController::class, 'createFaq']
);
Route::put(
    '/api/super-admin/website-content/faqs/{faqId}',
    [\App\Http\Controllers\WebsiteContentController::class, 'updateFaq']
)->whereNumber('faqId');
Route::delete(
    '/api/super-admin/website-content/faqs/{faqId}',
    [\App\Http\Controllers\WebsiteContentController::class, 'deleteFaq']
)->whereNumber('faqId');
Route::post(
    '/api/super-admin/website-content/team',
    [\App\Http\Controllers\WebsiteContentController::class, 'createTeamMember']
);
Route::post(
    '/api/super-admin/website-content/team/{teamMemberId}',
    [\App\Http\Controllers\WebsiteContentController::class, 'updateTeamMember']
)->whereNumber('teamMemberId');
Route::delete(
    '/api/super-admin/website-content/team/{teamMemberId}/photo',
    [\App\Http\Controllers\WebsiteContentController::class, 'deleteTeamPhoto']
)->whereNumber('teamMemberId');
Route::delete(
    '/api/super-admin/website-content/team/{teamMemberId}',
    [\App\Http\Controllers\WebsiteContentController::class, 'deleteTeamMember']
)->whereNumber('teamMemberId');
// =========================================================
// END PHASE 3 WEBSITE CONTENT
// =========================================================
