<?php

namespace App\Http\Controllers;

use App\Models\WBOUser;

class SuperAdminController extends Controller
{
    public function index()
    {
        $users = WBOUser::orderBy(
            'user_id',
            'desc'
        )->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
}