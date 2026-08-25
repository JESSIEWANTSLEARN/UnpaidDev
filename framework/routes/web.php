<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SignupVerificationController;

use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\StoreProductController;
use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\LogoutController;

use App\Http\Controllers\SuperAdminDashboardController;

// ==========================================
// REACT PUBLIC PAGES
// ==========================================

Route::view('/', 'react');
Route::view('/login', 'react');
Route::view('/signup', 'react');
Route::view('/signup-verify', 'react');
Route::view('/login-otp', 'react');


// ==========================================
// SYSTEM USER PAGE
// ==========================================

Route::view('/user', 'react');


// ==========================================
// AUTHENTICATION
// ==========================================

Route::post('/login', [
    LoginController::class,
    'login'
]);

Route::post('/login/verify-otp', [
    LoginOtpController::class,
    'verify'
]);

Route::post('/login/resend-otp', [
    LoginOtpController::class,
    'resend'
]);

Route::post('/register', [
    RegisterController::class,
    'register'
]);

Route::post('/signup/verify-otp', [
    SignupVerificationController::class,
    'verify'
]);

Route::post('/signup/resend-otp', [
    SignupVerificationController::class,
    'resend'
]);


// ==========================================
// LOGOUT
// ==========================================

Route::post('/logout', [
    LogoutController::class,
    'logout'
]);


// ==========================================
// STORE / PUBLIC API
// ==========================================

Route::get('/api/store/products', [
    StoreProductController::class,
    'index'
]);


// ==========================================
// SYSTEM USER API
// ==========================================

Route::get('/api/user/me', [
    SystemUserController::class,
    'me'
]);

Route::get('/api/user/orders', [
    SystemUserController::class,
    'orders'
]);

Route::post('/api/user/orders', [
    SystemUserController::class,
    'placeOrder'
]);

Route::put('/api/user/profile', [
    SystemUserController::class,
    'updateProfile'
]);

Route::put('/api/user/password', [
    SystemUserController::class,
    'updatePassword'
]);


// ==========================================
// SUPER ADMIN
// ==========================================

Route::get('/api/super-admin/users', [
    SuperAdminController::class,
    'index'
]);