<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $roles = explode('|', $role);
        $user = $request->user();

        // لو المستخدم مش مسجل دخول
        if (!$user) {
            abort(403, 'غير مصرح لك بدخول هذه الصفحة.');
        }

        // إذا كان المستخدم مديراً أو أدمن ومجموعة الصلاحيات تتضمن إدارة
        if ($user->isManager() && (in_array('admin', $roles, true) || in_array('supervisor', $roles, true) || in_array('manager', $roles, true))) {
            return $next($request);
        }

        // لو الصلاحية مش مطابقة للصلاحية المطلوبة
        if (!in_array($user->role, $roles, true)) {
            abort(403, 'غير مصرح لك بدخول هذه الصفحة.');
        }

        return $next($request);
    }
}
