<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/react-test', function () {
    return view('react');
});
Route::get(
    '/api/super-admin/users',
    [SuperAdminController::class, 'index']
);