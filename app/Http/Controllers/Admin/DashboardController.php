<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view($user->isSuperAdmin() ? 'admin.super-admin.dashboard' : 'admin.dashboard-regional', [
            'user' => $user,
        ]);
    }
}
