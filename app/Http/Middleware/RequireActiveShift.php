<?php

namespace App\Http\Middleware;

use App\Services\ShiftService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveShift
{
    public function __construct(
        protected ShiftService $shiftService
    ) {}

    /**
     * التحقق من وجود شيفت نشط ومفتوح للموظف قبل تنفيذ العمليات
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // إذا كان المستخدم غير مسجل دخول أو كان مديراً/مشرفاً يُسمح له بالمرور
        if (!$user || $user->isManager()) {
            return $next($request);
        }

        // فحص وجود شيفت نشط للموظف
        $activeShift = $this->shiftService->getActiveShift($user);

        if (!$activeShift) {
            $message = 'لا تتمكن من فعل هذه الخطوه دون بدء شيفت';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'  => false,
                    'message'  => $message,
                    'redirect' => route('shifts.my_shift'),
                ], 403);
            }

            return redirect()->route('shifts.my_shift')->with('error', $message);
        }

        return $next($request);
    }
}
