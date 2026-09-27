<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * GET /settings – صفحة الإعدادات
     * تمرر قائمة المستخدمين للأدمن والمشرف فقط
     */
    public function index(Request $request)
    {
        $users = null;

        if ($request->user() && $request->user()->isManager()) {
            $users = User::orderBy('role')->orderBy('name')->get();
        }

        return view('settings.index', compact('users'));
    }
}
