<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\StoreProductController;

// ==========================================
// REACT PUBLIC PAGES
// ==========================================

// Homepage -> LandingPage.jsx
Route::view('/', 'react');

// Login -> LogIn.jsx
Route::view('/login', 'react');

// Signup -> Signup.jsx
Route::view('/signup', 'react');

// Signup OTP verification -> SignupVerify.jsx
Route::view('/signup-verify', 'react');

// Login OTP -> LoginOtp.jsx
Route::view('/login-otp', 'react');


// ==========================================
// AUTHENTICATION
// ==========================================

Route::post(
    '/register',
    [RegisterController::class, 'register']
);


// ==========================================
// SUPER ADMIN
// DO NOT MODIFY ITS LOGIC
// ==========================================

Route::get(
    '/api/super-admin/users',
    [SuperAdminController::class, 'index']
);

Route::get(
    '/api/store/products',
    [StoreProductController::class, 'index']
);