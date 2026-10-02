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
  public function create(Request $request)
  {
    if (Auth::check()) {
      $user = Auth::user();
      $targetRoute = $user->isManager() ? 'dashboard' : 'pos.index';
      return redirect()->route($targetRoute);
    }

    return response()
      ->view('auth.login')
      ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
      ->header('Pragma', 'no-cache')
      ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
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

    $targetRoute = $user->isManager() ? 'dashboard' : 'pos.index';
    return redirect()->route($targetRoute);
  }

  /**
   * Destroy an authenticated session.
   */
  public function destroy(Request $request): RedirectResponse
  {
    $user = Auth::user();
    if ($user) {
      $user->setRememberToken(null);
      $user->save();
    }

    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    if (\Illuminate\Support\Facades\Cookie::has(Auth::getRecallerName())) {
      \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget(Auth::getRecallerName()));
    }

    return redirect()->route('login');
  }

}
