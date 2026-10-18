<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserGuideController extends Controller
{
    /**
     * عرض دليل الاستخدام التفاعلي الشامل لنظام UNO Cafe
     * بنظام السؤال والجواب المفصل والخطوات العملية
     */
    public function index(Request $request)
    {
        return view('user_guide.index');
    }
}
