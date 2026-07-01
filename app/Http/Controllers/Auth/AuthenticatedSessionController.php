<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Shift;
use App\Providers\RouteServiceProvider;


class AuthenticatedSessionController extends Controller
{
  /**
   * Display the login view.
   */
  public function create(): View
  {
    return view('auth.login');
  }

  /**
   * Handle an incoming authentication request.
   */
  public function store(LoginRequest $request)
  {

    $request->authenticate();

    $request->session()->regenerate();

    $user = Auth::user();
    if (!$user->hasVerifiedEmail()) {
      Auth::logout();
      return back()->withErrors(['email' => 'من فضلك فعّل بريدك الإلكتروني أولًا.']);
    }
   



    return redirect()->intended(route('dashboard'))
    ;

  }


  /**
   * Destroy an authenticated session.
   */
  public function destroy(Request $request): RedirectResponse
  {


    // لو مسجل دخول بالفعل -> اعمل تسجيل خروج عادي
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/'); // أو صفحة login حسب ما تحب
  }

}
