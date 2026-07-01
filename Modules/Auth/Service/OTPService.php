<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class OTPService
{
    // مدة صلاحية الكود بالدقائق
    protected $expiry = 5;

    // Generate OTP
    public function generateOTP(User $user)
    {
        $otp = rand(100000, 999999); // 6 digits
        // خزنه في cache مع user phone
        Cache::put('otp_'.$user->phone, $otp, now()->addMinutes($this->expiry));

        return $otp;
    }

    // Validate OTP
    public function validateOTP(User $user, $otp)
    {
        $cachedOtp = Cache::get('otp_'.$user->phone);

        if ($cachedOtp && $cachedOtp == $otp) {
            Cache::forget('otp_'.$user->phone); // استخدم مرة واحدة
            return true;
        }

        return false;
    }
}