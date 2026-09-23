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
        // لو المستخدم مش مسجل دخول أو صلاحيته مش مطابقة للصلاحية المطلوبة
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            abort(403, 'غير مصرح لك بدخول هذه الصفحة.');
        }

        return $next($request);
    }
}
