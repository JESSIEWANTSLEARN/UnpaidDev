<?php
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

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