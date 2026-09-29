<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * GET /settings – صفحة الإعدادات
     * تمرر قائمة المستخدمين والإحصائيات للأدمن فقط
     */
    public function index(Request $request)
    {
        $users = null;
        $userStats = null;

        if ($request->user() && $request->user()->isAdmin()) {
            $users = User::withCount(['shifts', 'createdOrders'])
                ->orderBy('role')
                ->orderBy('name')
                ->get();

            $userStats = [
                'total'       => $users->count(),
                'active'      => $users->where('is_active', true)->count(),
                'inactive'    => $users->where('is_active', false)->count(),
                'admins'      => $users->where('role', 'admin')->count(),
                'supervisors' => $users->where('role', 'supervisor')->count(),
                'cashiers'    => $users->where('role', 'cashier')->count(),
                'baristas'    => $users->where('role', 'barista')->count(),
                'clients'     => $users->where('role', 'client')->count(),
            ];
        }

        return view('settings.index', compact('users', 'userStats'));
    }
}
