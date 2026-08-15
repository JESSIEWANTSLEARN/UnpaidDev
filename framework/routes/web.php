<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SignupVerificationController;

use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\StoreProductController;


// ==========================================
// REACT PUBLIC PAGES
// ==========================================

Route::view('/', 'react');

Route::view('/login', 'react');

Route::view('/signup', 'react');

Route::view('/signup-verify', 'react');

Route::view('/login-otp', 'react');


// ==========================================
// AUTHENTICATION
// ==========================================

// LOGIN
Route::post('/login', [
    LoginController::class,
    'login'
]);

// LOGIN OTP VERIFY
Route::post('/login/verify-otp', [
    LoginOtpController::class,
    'verify'
]);

// LOGIN OTP RESEND
Route::post('/login/resend-otp', [
    LoginOtpController::class,
    'resend'
]);

// REGISTER
Route::post('/register', [
    RegisterController::class,
    'register'
]);

// SIGNUP OTP VERIFY
Route::post('/signup/verify-otp', [
    SignupVerificationController::class,
    'verify'
]);

// SIGNUP OTP RESEND
Route::post('/signup/resend-otp', [
    SignupVerificationController::class,
    'resend'
]);


// ==========================================
// STORE / PUBLIC API
// ==========================================

Route::get('/api/store/products', [
    StoreProductController::class,
    'index'
]);


// ==========================================
// SUPER ADMIN
// DO NOT MODIFY ITS LOGIC
// ==========================================

Route::get('/api/super-admin/users', [
    SuperAdminController::class,
    'index'
]);