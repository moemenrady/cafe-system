<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{



    /**
     * ==========================================================================
     * Display Users Page
     * ==========================================================================
     *
     * Method:
     * GET /users
     *
     * Description:
     * يرجع صفحة إدارة المستخدمين مع جميع المستخدمين.
     *
     * Query Parameters (اختياري):
     * search => البحث بالاسم أو الإيميل
     *
     * Example:
     * GET /users
     *
     * GET /users?search=Ahmed
     */
    public function index(Request $request)
    {
        $users = User::query()

            ->when($request->search, function ($query) use ($request) {

                $query->where(function ($q) use ($request) {

                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })

            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.users.index', compact('users'));
    }

    /**
     * ==========================================================================
     * Store New User
     * ==========================================================================
     *
     * Method:
     * POST /users
     *
     * Body (JSON / FormData)
     *
     * {
     *   "name":"Ahmed Mohamed",
     *   "email":"ahmed@gmail.com",
     *   "password":"12345678",
     *   "password_confirmation":"12345678",
     *   "role":"supervisor"
     * }
     *
     * Available Roles:
     * admin
     * supervisor
     *
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            'name' => ['required', 'string', 'max:255'],

            'email' => ['required', 'email', 'max:255', 'unique:users,email'],

            'password' => ['required', 'confirmed', 'min:8'],

            'role' => ['required', Rule::in(['admin', 'supervisor'])],

        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return back()->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    /**
     * ==========================================================================
     * Update Existing User
     * ==========================================================================
     *
     * Method:
     * PUT /users/{user}
     *
     * Example:
     * PUT /users/5
     *
     * Body:
     *
     * {
     *   "name":"Ahmed Ali",
     *   "email":"ahmed@gmail.com",
     *   "role":"admin",
     *
     *   // Optional
     *   "password":"12345678",
     *   "password_confirmation":"12345678"
     * }
     *
     * Notes:
     * - password اختياري.
     * - لو متبعتش هيظل كما هو.
     *
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([

            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],

            'role' => ['required', Rule::in(['admin', 'supervisor'])],

            'password' => ['nullable', 'confirmed', 'min:8'],

        ]);

        if (!empty($data['password'])) {

            $data['password'] = Hash::make($data['password']);
        } else {

            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    /**
     * ==========================================================================
     * Delete User
     * ==========================================================================
     *
     * Method:
     * DELETE /users/{user}
     *
     * Example:
     *
     * DELETE /users/5
     *
     * Notes:
     *
     * - لا يوجد Body.
     * - يمنع حذف الحساب الحالي.
     *
     */
    public function destroy(User $user)
    {
        if (auth()->id() == $user->id) {

            return back()->withErrors([
                'error' => 'لا يمكن حذف المستخدم الحالي لأنه الحساب المستخدم لتسجيل الدخول.'
            ]);
        }

        $user->delete();

        return back()->with('success', 'تم حذف المستخدم بنجاح.');
    }
}
