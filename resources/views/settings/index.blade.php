@extends('layouts.app')

@section('page_title', 'الإعدادات وتخصيص الحساب')

@section('content')
<div class="container mx-auto space-y-6 pb-16 max-w-4xl" dir="rtl">

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- رأس الصفحة --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-black text-gray-800">إعدادات الحساب وتخصيص النظام</h2>
                <p class="text-xs text-gray-400 mt-0.5">إدارة بيانات حسابك وتخصيص ترتيب القائمة الجانبية حسب رغبتك</p>
            </div>
        </div>
        <span class="px-3 py-1 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold font-mono">
            {{ auth()->user()->role }}
        </span>
    </div>

    {{-- قسم 1: تخصيص وترتيب عناصر القائمة الجانبية (Sidebar Reordering) --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-black text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-arrows-up-down text-sky-500"></i>
                    <span>تخصيص وترتيب القائمة الجانبية</span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">رتب عناصر القائمة بسهولة عبر أزرار الصعود والهبوط وسيتم حفظ الترتيب في حسابك مباشرة</p>
            </div>
            <button type="button" onclick="resetSidebarOrderSettings()" class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold transition">
                <i class="fa-solid fa-rotate-left ml-1"></i>
                استعادة الترتيب الافتراضي
            </button>
        </div>

        @php
            $orderedItems = auth()->user()->getOrderedSidebarItems();
        @endphp

        <div id="settingsSidebarList" class="space-y-2">
            @foreach($orderedItems as $key => $item)
                <div data-key="{{ $key }}" class="settings-sidebar-item flex items-center justify-between p-3.5 rounded-2xl border border-gray-100 bg-gray-50/60 hover:bg-gray-100/80 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm {{ $item['is_hero'] ?? false ? 'bg-sky-500 text-white' : 'bg-white text-gray-700 border border-gray-200 shadow-2xs' }}">
                            <i class="{{ $item['icon'] }}"></i>
                        </div>
                        <div>
                            <span class="font-bold text-gray-800 text-xs sm:text-sm block">{{ $item['title'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold">{{ $item['group_label'] ?? '' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="moveItemUp(this)" class="w-8 h-8 rounded-xl bg-white hover:bg-sky-50 text-gray-600 hover:text-sky-600 border border-gray-200 flex items-center justify-center transition" title="تحريك لأعلى">
                            <i class="fa-solid fa-arrow-up text-xs"></i>
                        </button>
                        <button type="button" onclick="moveItemDown(this)" class="w-8 h-8 rounded-xl bg-white hover:bg-sky-50 text-gray-600 hover:text-sky-600 border border-gray-200 flex items-center justify-center transition" title="تحريك لأسفل">
                            <i class="fa-solid fa-arrow-down text-xs"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-3 flex justify-end">
            <button type="button" onclick="saveSettingsSidebarOrder()" id="saveOrderBtn" class="px-6 py-3 rounded-2xl bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition active:scale-95">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>حفظ الترتيب الحالي في الحساب</span>
            </button>
        </div>
    </div>

    {{-- قسم 2: بيانات الحساب الشخصي --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 space-y-4">
        <h3 class="text-sm sm:text-base font-black text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
            <i class="fa-solid fa-user-gear text-sky-500"></i>
            <span>بيانات الحساب الحالي</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">الاسم الكامل</span>
                <strong class="text-sm text-gray-800">{{ auth()->user()->name }}</strong>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">البريد الإلكتروني</span>
                <strong class="text-sm text-gray-800 font-mono">{{ auth()->user()->email }}</strong>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">الصلاحية / الدور</span>
                <strong class="text-sm text-sky-700 font-bold">{{ auth()->user()->role }}</strong>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">صلاحية بدء الشيفت</span>
                <strong class="text-sm {{ auth()->user()->canStartShift() ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ auth()->user()->canStartShift() ? 'مفعلة (مسموح)' : 'معطلة (ممنوع)' }}
                </strong>
            </div>
        </div>
    </div>

    {{-- قسم 3: إدارة المستخدمين (CRUD كامل) – خاص بمدير النظام (Admin) فقط --}}
    @if(auth()->user()->isAdmin() && $users !== null)
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 space-y-5">

        {{-- رأس القسم + زر الإضافة --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-black text-gray-800">إدارة المستخدمين والصلاحيات</h3>
                    <span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-0.5 rounded-lg">خاص بالمدير (Admin)</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">إضافة وتعديل وحذف حسابات الموظفين وتخصيص صلاحيات فتح الورديات وحالة الحسابات</p>
            </div>
            <button type="button" onclick="openAddUserModal()"
                class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm shadow-sky-200 transition active:scale-95">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>إضافة مستخدم جديد</span>
            </button>
        </div>

        {{-- إحصائيات سريعة للمستخدمين --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-sm shrink-0">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <span class="text-[11px] text-gray-400 block font-bold">إجمالي المستخدمين</span>
                    <strong class="text-base text-gray-800 font-black">{{ $userStats['total'] ?? $users->count() }}</strong>
                </div>
            </div>

            <div class="bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <span class="text-[11px] text-gray-400 block font-bold">الحسابات المفعلة</span>
                    <strong class="text-base text-emerald-600 font-black">{{ $userStats['active'] ?? $users->where('is_active', true)->count() }}</strong>
                </div>
            </div>

            <div class="bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-sm shrink-0">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <span class="text-[11px] text-gray-400 block font-bold">المدراء والمشرفين</span>
                    <strong class="text-base text-amber-600 font-black">{{ ($userStats['admins'] ?? 0) + ($userStats['supervisors'] ?? 0) }}</strong>
                </div>
            </div>

            <div class="bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-sm shrink-0">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <div>
                    <span class="text-[11px] text-gray-400 block font-bold">الكاشير والباريستا</span>
                    <strong class="text-base text-purple-600 font-black">{{ ($userStats['cashiers'] ?? 0) + ($userStats['baristas'] ?? 0) }}</strong>
                </div>
            </div>
        </div>

        {{-- شريط البحث والفلترة السريعة --}}
        <div class="bg-gray-50/60 p-3 rounded-2xl border border-gray-100 flex flex-wrap items-center gap-2 text-xs">
            <div class="relative flex-1 min-w-[180px]">
                <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-gray-400 text-xs"></i>
                <input type="text" id="userSearchInput" oninput="filterUsersTable()"
                    class="w-full bg-white border border-gray-200 rounded-xl pr-8 pl-3 py-1.5 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    placeholder="بحث سريع بالاسم أو البريد...">
            </div>

            <select id="userRoleFilter" onchange="filterUsersTable()"
                class="bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-sky-200">
                <option value="all">كل الأدوار</option>
                <option value="admin">مدير (Admin)</option>
                <option value="supervisor">مشرف (Supervisor)</option>
                <option value="cashier">كاشير (Cashier)</option>
                <option value="barista">باريستا (Barista)</option>
                <option value="client">عميل (Client)</option>
            </select>

            <select id="userStatusFilter" onchange="filterUsersTable()"
                class="bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-sky-200">
                <option value="all">كل الحالات</option>
                <option value="active">مفعّل فقط</option>
                <option value="inactive">معطّل فقط</option>
            </select>

            <select id="userShiftFilter" onchange="filterUsersTable()"
                class="bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-sky-200">
                <option value="all">صلاحية الشيفت: الكل</option>
                <option value="allowed">مسموح له فتح شيفت</option>
                <option value="forbidden">ممنوع من فتح شيفت</option>
            </select>
        </div>

        {{-- جدول المستخدمين الشامل --}}
        <div class="overflow-x-auto rounded-2xl border border-gray-100">
            <table class="w-full text-right text-xs" id="usersTable">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                        <th class="p-3">المستخدم</th>
                        <th class="p-3 hidden sm:table-cell">البريد الإلكتروني</th>
                        <th class="p-3 text-center">الدور / الصلاحية</th>
                        <th class="p-3 text-center">فتح الشيفت</th>
                        <th class="p-3 text-center">حالة الحساب</th>
                        <th class="p-3 text-center hidden md:table-cell">النشاط (ورديات / طلبات)</th>
                        <th class="p-3 text-center hidden lg:table-cell">تاريخ التسجيل</th>
                        <th class="p-3 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($users as $u)
                    <tr class="hover:bg-gray-50/60 transition user-row" 
                        id="user-row-{{ $u->id }}"
                        data-name="{{ mb_strtolower($u->name) }}"
                        data-email="{{ strtolower($u->email) }}"
                        data-role="{{ $u->role }}"
                        data-active="{{ ($u->is_active ?? true) ? '1' : '0' }}"
                        data-shift="{{ ($u->can_start_shift ?? true) ? '1' : '0' }}">
                        
                        {{-- المستخدم --}}
                        <td class="p-3">
                            <div class="font-bold text-gray-800 flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-sky-100 to-sky-200 text-sky-700 flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                    {{ mb_substr($u->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="user-display-name">{{ $u->name }}</span>
                                        @if($u->id === auth()->id())
                                            <span class="text-[9px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-md font-black">أنت</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 sm:hidden block font-mono">{{ $u->email }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- البريد الإلكتروني --}}
                        <td class="p-3 hidden sm:table-cell text-gray-600 font-mono text-[11px] user-display-email">
                            {{ $u->email }}
                        </td>

                        {{-- الدور / الصلاحية --}}
                        <td class="p-3 text-center">
                            @php
                                $roleBadges = [
                                    'admin'      => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'مدير عام', 'icon' => 'fa-shield-halved'],
                                    'supervisor' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'مشرف', 'icon' => 'fa-user-tie'],
                                    'cashier'    => ['bg' => 'bg-sky-50 text-sky-700 border-sky-200', 'label' => 'كاشير', 'icon' => 'fa-cash-register'],
                                    'barista'    => ['bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'label' => 'باريستا', 'icon' => 'fa-mug-hot'],
                                    'client'     => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'عميل', 'icon' => 'fa-user'],
                                ];
                                $b = $roleBadges[$u->role] ?? ['bg' => 'bg-gray-100 text-gray-600 border-gray-200', 'label' => $u->role, 'icon' => 'fa-user'];
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-black text-[10px] border {{ $b['bg'] }}">
                                <i class="fa-solid {{ $b['icon'] }} text-[9px]"></i>
                                <span>{{ $b['label'] }}</span>
                            </span>
                        </td>

                        {{-- صلاحية بدء الشيفت --}}
                        <td class="p-3 text-center">
                            <button type="button"
                                onclick="toggleUserShiftPermission({{ $u->id }}, this)"
                                class="shift-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold text-[10px] transition border {{ ($u->can_start_shift ?? true) ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200' }}"
                                title="انقر للتبديل السريع لصلاحية فتح الشيفت"
                                data-shift="{{ ($u->can_start_shift ?? true) ? '1' : '0' }}">
                                <i class="fa-solid {{ ($u->can_start_shift ?? true) ? 'fa-clock-rotate-left text-emerald-600' : 'fa-ban text-gray-400' }}"></i>
                                <span>{{ ($u->can_start_shift ?? true) ? 'مسموح' : 'ممنوع' }}</span>
                            </button>
                        </td>

                        {{-- حالة الحساب --}}
                        <td class="p-3 text-center">
                            <button type="button"
                                onclick="toggleUserStatus({{ $u->id }}, this)"
                                class="status-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold text-[10px] transition border {{ ($u->is_active ?? true) ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}"
                                data-active="{{ ($u->is_active ?? true) ? '1' : '0' }}"
                                {{ $u->id === auth()->id() ? 'disabled title="لا يمكنك تعطيل حسابك الحالي"' : 'title="انقر لتفعيل أو تعطيل الحساب"' }}>
                                <i class="fa-solid {{ ($u->is_active ?? true) ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600' }}"></i>
                                <span>{{ ($u->is_active ?? true) ? 'مفعّل' : 'معطّل' }}</span>
                            </button>
                        </td>

                        {{-- النشاط --}}
                        <td class="p-3 text-center hidden md:table-cell text-gray-500 font-mono text-[11px]">
                            <span class="text-sky-700 font-bold" title="عدد الورديات">{{ $u->shifts_count ?? 0 }} وردية</span>
                            <span class="text-gray-300 mx-1">|</span>
                            <span class="text-gray-700 font-bold" title="عدد الطلبات المنشأة">{{ $u->created_orders_count ?? 0 }} طلب</span>
                        </td>

                        {{-- تاريخ التسجيل --}}
                        <td class="p-3 text-center hidden lg:table-cell text-gray-400 font-mono text-[11px]">
                            {{ $u->created_at ? $u->created_at->format('Y-m-d') : '—' }}
                        </td>

                        {{-- الإجراءات --}}
                        <td class="p-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                {{-- زر عرض التفاصيل --}}
                                <button type="button"
                                    onclick="openViewUserModal({{ $u->id }}, this)"
                                    class="w-7 h-7 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 flex items-center justify-center transition"
                                    title="عرض كافة التفاصيل">
                                    <i class="fa-solid fa-eye text-[11px]"></i>
                                </button>

                                {{-- زر التعديل --}}
                                <button type="button"
                                    onclick="openEditUserModal({{ $u->id }}, this)"
                                    class="w-7 h-7 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition"
                                    title="تعديل بيانات المستخدم">
                                    <i class="fa-solid fa-pen text-[11px]"></i>
                                </button>

                                {{-- زر الحذف --}}
                                @if($u->id !== auth()->id())
                                <button type="button"
                                    onclick="deleteUser({{ $u->id }}, '{{ addslashes($u->name) }}', this)"
                                    class="w-7 h-7 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100 flex items-center justify-center transition"
                                    title="حذف المستخدم">
                                    <i class="fa-solid fa-trash text-[11px]"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- رسالة عند عدم وجود نتائج للبحث --}}
        <div id="noUsersFound" class="hidden py-8 text-center text-gray-400 text-xs font-bold">
            <i class="fa-solid fa-users-slash text-2xl text-gray-300 block mb-2"></i>
            لا توجد حسابات تطابق معايير البحث والفلترة المحددة.
        </div>
    </div>
    @endif


</div>

@push('modals')
{{-- ==========================================
     مودال إضافة مستخدم جديد (بجميع البيانات)
     ========================================== --}}
<div id="addUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" dir="rtl">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddUserModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg z-10 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h3 class="font-black text-gray-800 text-sm">إضافة مستخدم جديد</h3>
                    <p class="text-[10px] text-gray-400">إنشاء حساب جديد وتعيين الصلاحيات الخاصة به</p>
                </div>
            </div>
            <button onclick="closeAddUserModal()" class="w-8 h-8 rounded-xl bg-white hover:bg-gray-200 text-gray-500 flex items-center justify-center text-sm transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addUserForm" class="p-6 space-y-4">
            @csrf
            <div id="addUserError" class="hidden bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold p-3 rounded-2xl"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- الاسم الكامل --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-sky-200" placeholder="مثال: أحمد عبد الله">
                </div>

                {{-- البريد الإلكتروني --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-sky-200 font-mono" placeholder="user@cafe.com">
                </div>

                {{-- الدور / الصلاحية --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">الدور / الصلاحية في النظام <span class="text-rose-500">*</span></label>
                    <select name="role" id="addRoleSelect" required onchange="handleRoleChangeAdd(this.value)"
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 font-bold focus:outline-none focus:ring-2 focus:ring-sky-200">
                        <option value="cashier">كاشير (نقطة البيع + الفواتير)</option>
                        <option value="barista">باريستا (الطلبات والمطبخ)</option>
                        <option value="supervisor">مشرف (إدارة العمليات والمصروفات)</option>
                        <option value="admin">مدير عام (صلاحية كاملة للنظام)</option>
                        <option value="client">عميل (عرض المينيو وحسابه فقط)</option>
                    </select>
                </div>

                {{-- كلمة السر --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">كلمة السر <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required minlength="8" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-sky-200" placeholder="8 أحرف كحد أدنى">
                </div>

                {{-- تأكيد كلمة السر --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">تأكيد كلمة السر <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" required minlength="8" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-sky-200" placeholder="أعد إدخال كلمة السر">
                </div>
            </div>

            {{-- خيارات وتخصيصات متقدمة (صلاحية الشيفت وحالة الحساب) --}}
            <div class="pt-2 border-t border-gray-100 space-y-3">
                {{-- تفعيل بدء الشيفت --}}
                <label class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100 cursor-pointer hover:bg-gray-100/60 transition">
                    <div>
                        <span class="text-xs font-black text-gray-800 block">صلاحية فتح وبدء الشيفت (الوردية)</span>
                        <span class="text-[10px] text-gray-400 block">السماح للمستخدم باستلام درج النقدية وبدء وردية عمل جديدة</span>
                    </div>
                    <input type="checkbox" name="can_start_shift" id="addCanStartShift" value="1" checked
                        class="w-5 h-5 rounded-lg text-sky-600 focus:ring-sky-400 border-gray-300">
                </label>

                {{-- حالة الحساب (مفعّل فورياً) --}}
                <label class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100 cursor-pointer hover:bg-gray-100/60 transition">
                    <div>
                        <span class="text-xs font-black text-gray-800 block">تفعيل الحساب فورياً</span>
                        <span class="text-[10px] text-gray-400 block">إذا كان معطلاً لن يتمكن المستخدم من تسجيل الدخول للنظام</span>
                    </div>
                    <input type="checkbox" name="is_active" value="1" checked
                        class="w-5 h-5 rounded-lg text-emerald-600 focus:ring-emerald-400 border-gray-300">
                </label>
            </div>

            <div class="flex gap-2 pt-3">
                <button type="submit" id="addUserSubmitBtn"
                    class="flex-1 bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white font-black py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-sm shadow-sky-200">
                    <i class="fa-solid fa-check"></i>
                    <span>حفظ وإنشاء المستخدم</span>
                </button>
                <button type="button" onclick="closeAddUserModal()"
                    class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs sm:text-sm transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================
     مودال تعديل مستخدم (بجميع البيانات)
     ========================================== --}}
<div id="editUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" dir="rtl">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditUserModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg z-10 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <h3 class="font-black text-gray-800 text-sm">تعديل بيانات المستخدم</h3>
                    <p class="text-[10px] text-gray-400" id="editUserSubtitle">تحديث الحساب والصلاحيات</p>
                </div>
            </div>
            <button onclick="closeEditUserModal()" class="w-8 h-8 rounded-xl bg-white hover:bg-gray-200 text-gray-500 flex items-center justify-center text-sm transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- مؤشر تحميل بيانات التعديل --}}
        <div id="editUserLoading" class="hidden p-10 flex flex-col items-center justify-center gap-3">
            <i class="fa-solid fa-circle-notch fa-spin text-amber-500 text-3xl"></i>
            <span class="text-xs font-bold text-gray-500">جاري جلب بيانات المستخدم...</span>
        </div>

        <form id="editUserForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" id="editUserId" name="user_id">
            <div id="editUserError" class="hidden bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold p-3 rounded-2xl"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- الاسم الكامل --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                    <input type="text" id="editUserName" name="name" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-200">
                </div>

                {{-- البريد الإلكتروني --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                    <input type="email" id="editUserEmail" name="email" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-200 font-mono">
                </div>

                {{-- الدور / الصلاحية --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">الدور / الصلاحية <span class="text-rose-500">*</span></label>
                    <select id="editUserRole" name="role" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 font-bold focus:outline-none focus:ring-2 focus:ring-amber-200">
                        <option value="cashier">كاشير (نقطة البيع + الفواتير)</option>
                        <option value="barista">باريستا (الطلبات والمطبخ)</option>
                        <option value="supervisor">مشرف (إدارة العمليات والمصروفات)</option>
                        <option value="admin">مدير عام (صلاحية كاملة للنظام)</option>
                        <option value="client">عميل (عرض المينيو وحسابه فقط)</option>
                    </select>
                </div>

                {{-- كلمة السر الجديدة --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">كلمة السر الجديدة <span class="text-gray-400 font-normal">(اختياري)</span></label>
                    <input type="password" name="password" minlength="8" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-200" placeholder="اتركها فارغة للتخطي">
                </div>

                {{-- تأكيد كلمة السر --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">تأكيد كلمة السر</label>
                    <input type="password" name="password_confirmation" minlength="8" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-200" placeholder="أعد كلمة السر الجديدة">
                </div>
            </div>

            {{-- خيارات الصلاحية المتقدمة وحالة الحساب --}}
            <div class="pt-2 border-t border-gray-100 space-y-3">
                {{-- صلاحية بدء الشيفت --}}
                <label class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100 cursor-pointer hover:bg-gray-100/60 transition">
                    <div>
                        <span class="text-xs font-black text-gray-800 block">صلاحية فتح وبدء الشيفت (الوردية)</span>
                        <span class="text-[10px] text-gray-400 block">السماح للمستخدم باستلام درج النقدية وبدء وردية عمل جديدة</span>
                    </div>
                    <input type="checkbox" name="can_start_shift" id="editCanStartShift" value="1"
                        class="w-5 h-5 rounded-lg text-sky-600 focus:ring-sky-400 border-gray-300">
                </label>

                {{-- حالة الحساب --}}
                <label class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100 cursor-pointer hover:bg-gray-100/60 transition" id="editIsActiveContainer">
                    <div>
                        <span class="text-xs font-black text-gray-800 block">حالة الحساب (مفعّل / معطّل)</span>
                        <span class="text-[10px] text-gray-400 block">الحساب المعطل يُمنع فورياً من تسجيل الدخول للنظام</span>
                    </div>
                    <input type="checkbox" name="is_active" id="editIsActive" value="1"
                        class="w-5 h-5 rounded-lg text-emerald-600 focus:ring-emerald-400 border-gray-300">
                </label>
            </div>

            <div class="flex gap-2 pt-3">
                <button type="submit" id="editUserSubmitBtn"
                    class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-black py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-sm shadow-amber-200">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>حفظ التعديلات</span>
                </button>
                <button type="button" onclick="closeEditUserModal()"
                    class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs sm:text-sm transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================
     مودال عرض تفاصيل المستخدم بالكامل (View Details)
     ========================================== --}}
<div id="viewUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" dir="rtl">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeViewUserModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md z-10 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <h3 class="font-black text-gray-800 text-sm">تفاصيل حساب المستخدم</h3>
            </div>
            <button onclick="closeViewUserModal()" class="w-8 h-8 rounded-xl bg-white hover:bg-gray-200 text-gray-500 flex items-center justify-center text-sm transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- مؤشر تحميل تفاصيل الحساب --}}
        <div id="viewUserLoading" class="hidden p-10 flex flex-col items-center justify-center gap-3">
            <i class="fa-solid fa-circle-notch fa-spin text-sky-500 text-3xl"></i>
            <span class="text-xs font-bold text-gray-500">جاري جلب تفاصيل الحساب...</span>
        </div>

        <div class="p-6 space-y-4" id="viewUserContent">
            {{-- بطاقة رأسية للمستخدم --}}
            <div class="flex items-center gap-3.5 bg-sky-50/50 p-4 rounded-2xl border border-sky-100">
                <div id="viewUserAvatar" class="w-12 h-12 rounded-2xl bg-sky-500 text-white flex items-center justify-center font-black text-lg shadow-sm">
                    ?
                </div>
                <div>
                    <h4 id="viewUserName" class="text-sm font-black text-gray-800">—</h4>
                    <span id="viewUserEmail" class="text-xs text-gray-500 font-mono block">—</span>
                </div>
            </div>

            {{-- تفاصيل الحساب في شبكة --}}
            <div class="grid grid-cols-2 gap-2.5 text-xs">
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px] mb-0.5">الدور في النظام</span>
                    <strong id="viewUserRole" class="text-gray-800 font-bold">—</strong>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px] mb-0.5">حالة الحساب</span>
                    <strong id="viewUserStatus" class="font-bold">—</strong>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px] mb-0.5">صلاحية فتح الشيفت</span>
                    <strong id="viewUserShift" class="font-bold">—</strong>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px] mb-0.5">تأكيد البريد</span>
                    <strong id="viewUserVerified" class="text-gray-800 font-bold">—</strong>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px] mb-0.5">إجمالي الورديات</span>
                    <strong id="viewUserShiftsCount" class="text-sky-700 font-black">—</strong>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px] mb-0.5">إجمالي الطلبات المنشأة</span>
                    <strong id="viewUserOrdersCount" class="text-emerald-700 font-black">—</strong>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 col-span-2">
                    <span class="text-gray-400 block text-[10px] mb-0.5">تاريخ وتوقيت التسجيل</span>
                    <strong id="viewUserCreatedAt" class="text-gray-700 font-mono text-[11px]">—</strong>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" onclick="closeViewUserModal()"
                    class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition">
                    إغلاق
                </button>
            </div>
        </div>
    </div>
</div>
@endpush


@push('scripts')
<script>
    function moveItemUp(btn) {
        const item = btn.closest('.settings-sidebar-item');
        const prev = item.previousElementSibling;
        if (prev) {
            item.parentNode.insertBefore(item, prev);
        }
    }

    function moveItemDown(btn) {
        const item = btn.closest('.settings-sidebar-item');
        const next = item.nextElementSibling;
        if (next) {
            item.parentNode.insertBefore(next, item);
        }
    }

    async function saveSettingsSidebarOrder() {
        const btn = document.getElementById('saveOrderBtn');
        const items = document.querySelectorAll('#settingsSidebarList .settings-sidebar-item');
        const order = Array.from(items).map(el => el.dataset.key);

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> <span>جاري الحفظ...</span>';

        try {
            const res = await fetch('{{ route('user.sidebar_order.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ order: order })
            });
            const data = await res.json();
            if (data.success) {
                alert('تم حفظ ترتيب القائمة بنجاح! سيتم تحديث الصفحة لتطبيق الترتيب الجديد.');
                window.location.reload();
            } else {
                alert('حدث خطأ أثناء الحفظ.');
            }
        } catch (e) {
            alert('حدث خطأ أثناء الحفظ.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>حفظ الترتيب الحالي في الحساب</span>';
        }
    }

    async function resetSidebarOrderSettings() {
        if (!confirm('هل تريد استعادة الترتيب الافتراضي للقائمة؟')) return;

        try {
            const res = await fetch('{{ route('user.sidebar_order.reset') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (e) {
            alert('حدث خطأ أثناء الاستعادة.');
        }
    }

    // ==========================================
    // 👥 إدارة المستخدمين – دوال JavaScript (Admin Only)
    // ==========================================

    const CSRF = '{{ csrf_token() }}';
    const USERS_API_URL = '{{ url('settings/users') }}';

    // ضمان وجود المودالات مباشرة في body لمنع أي مشاكل في Stacking Context أو Scroll
    document.addEventListener('DOMContentLoaded', function() {
        ['addUserModal', 'editUserModal', 'viewUserModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });

        // إغلاق النوافذ عند الضغط على زر Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddUserModal();
                closeEditUserModal();
                closeViewUserModal();
            }
        });
    });

    // ---- فلترة وبحث جدول المستخدمين لحظياً ----
    function filterUsersTable() {
        const query   = (document.getElementById('userSearchInput')?.value || '').trim().toLowerCase();
        const role    = document.getElementById('userRoleFilter')?.value || 'all';
        const status  = document.getElementById('userStatusFilter')?.value || 'all';
        const shift   = document.getElementById('userShiftFilter')?.value || 'all';

        const rows = document.querySelectorAll('#usersTable tbody tr.user-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name      = row.dataset.name || '';
            const email     = row.dataset.email || '';
            const userRole  = row.dataset.role || '';
            const isActive  = row.dataset.active || '1';
            const canShift  = row.dataset.shift || '1';

            const matchesSearch = !query || name.includes(query) || email.includes(query);
            const matchesRole   = (role === 'all') || (userRole === role);
            const matchesStatus = (status === 'all') || (status === 'active' && isActive === '1') || (status === 'inactive' && isActive === '0');
            const matchesShift  = (shift === 'all') || (shift === 'allowed' && canShift === '1') || (shift === 'forbidden' && canShift === '0');

            if (matchesSearch && matchesRole && matchesStatus && matchesShift) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const noFoundEl = document.getElementById('noUsersFound');
        if (noFoundEl) {
            noFoundEl.classList.toggle('hidden', visibleCount > 0);
        }
    }

    // ---- تغيير الدور في مودال الإضافة لتعديل صلاحية الشيفت تلقائياً ----
    function handleRoleChangeAdd(role) {
        const shiftCheckbox = document.getElementById('addCanStartShift');
        if (shiftCheckbox) {
            shiftCheckbox.checked = ['admin', 'supervisor', 'cashier'].includes(role);
        }
    }

    // ---- مودال الإضافة ----
    function openAddUserModal() {
        const modal = document.getElementById('addUserModal');
        if (!modal) return;
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        modal.classList.remove('hidden');
        document.getElementById('addUserForm')?.reset();
        document.getElementById('addUserError')?.classList.add('hidden');
        handleRoleChangeAdd('cashier');
    }
    function closeAddUserModal() {
        document.getElementById('addUserModal')?.classList.add('hidden');
    }

    // ---- مودال التعديل ----
    async function openEditUserModal(id, btn = null) {
        const modal = document.getElementById('editUserModal');
        if (!modal) return;
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        const editForm = document.getElementById('editUserForm');
        const loadingEl = document.getElementById('editUserLoading');
        const errBox = document.getElementById('editUserError');

        // إظهار المودال وحالة التحميل فوراً لاستجابة لحظية
        modal.classList.remove('hidden');
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (editForm) editForm.classList.add('hidden');
        if (errBox) errBox.classList.add('hidden');
        document.getElementById('editUserId').value = id;

        let originalHtml = '';
        if (btn) {
            originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i>';
        }

        try {
            const res = await fetch(`${USERS_API_URL}/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (data.success && data.user) {
                const u = data.user;
                document.getElementById('editUserName').value  = u.name;
                document.getElementById('editUserEmail').value = u.email;
                document.getElementById('editUserRole').value  = u.role;
                document.getElementById('editCanStartShift').checked = Boolean(u.can_start_shift);
                document.getElementById('editIsActive').checked      = Boolean(u.is_active);

                // لو المستخدم هو نفسه الأدمن الحالي المسجل
                const isSelf = Boolean(u.is_current_user);
                const roleEl = document.getElementById('editUserRole');
                const activeEl = document.getElementById('editIsActive');
                if (isSelf) {
                    roleEl.disabled = true;
                    activeEl.disabled = true;
                    document.getElementById('editUserSubtitle').textContent = 'تعديل حسابك الشخصي (لا يمكنك تجريد صلاحية المدير عن نفسك)';
                } else {
                    roleEl.disabled = false;
                    activeEl.disabled = false;
                    document.getElementById('editUserSubtitle').textContent = `تحديث بيانات المستخدم (${u.name})`;
                }

                if (loadingEl) loadingEl.classList.add('hidden');
                if (editForm) editForm.classList.remove('hidden');
            } else {
                closeEditUserModal();
                alert(data.message || 'فشل في جلب بيانات المستخدم.');
            }
        } catch {
            closeEditUserModal();
            alert('حدث خطأ في الاتصال بالسيرفر.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal')?.classList.add('hidden');
    }

    // ---- مودال عرض تفاصيل المستخدم بالكامل ----
    async function openViewUserModal(id, btn = null) {
        const modal = document.getElementById('viewUserModal');
        if (!modal) return;
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        const contentEl = document.getElementById('viewUserContent');
        const loadingEl = document.getElementById('viewUserLoading');

        modal.classList.remove('hidden');
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (contentEl) contentEl.classList.add('hidden');

        let originalHtml = '';
        if (btn) {
            originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i>';
        }

        try {
            const res = await fetch(`${USERS_API_URL}/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (data.success && data.user) {
                const u = data.user;
                document.getElementById('viewUserAvatar').textContent = (u.name || '?').charAt(0);
                document.getElementById('viewUserName').textContent   = u.name + (u.is_current_user ? ' (أنت)' : '');
                document.getElementById('viewUserEmail').textContent  = u.email;

                const roleNames = {
                    'admin': 'مدير عام',
                    'supervisor': 'مشرف',
                    'cashier': 'كاشير',
                    'barista': 'باريستا',
                    'client': 'عميل'
                };
                document.getElementById('viewUserRole').textContent        = roleNames[u.role] || u.role;
                document.getElementById('viewUserStatus').textContent      = u.is_active ? 'مفعّل (نشط)' : 'معطّل (محظور)';
                document.getElementById('viewUserStatus').className        = u.is_active ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold';
                document.getElementById('viewUserShift').textContent       = u.can_start_shift ? 'مسموح له بفتح الشيفت' : 'ممنوع من فتح الشيفت';
                document.getElementById('viewUserShift').className        = u.can_start_shift ? 'text-emerald-600 font-bold' : 'text-gray-500 font-bold';
                document.getElementById('viewUserVerified').textContent    = u.email_verified_at ? 'مؤكد (' + u.email_verified_at + ')' : 'غير مؤكد';
                document.getElementById('viewUserShiftsCount').textContent = (u.shifts_count || 0) + ' وردية';
                document.getElementById('viewUserOrdersCount').textContent = (u.orders_count || 0) + ' طلب';
                document.getElementById('viewUserCreatedAt').textContent   = u.created_at || '—';

                if (loadingEl) loadingEl.classList.add('hidden');
                if (contentEl) contentEl.classList.remove('hidden');
            } else {
                closeViewUserModal();
                alert(data.message || 'فشل في جلب تفاصيل المستخدم.');
            }
        } catch {
            closeViewUserModal();
            alert('حدث خطأ في الاتصال.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    }
    function closeViewUserModal() {
        document.getElementById('viewUserModal')?.classList.add('hidden');
    }

    // ---- إرسال فورم إضافة مستخدم ----
    document.getElementById('addUserForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('addUserSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...';

        const formData = new FormData(this);

        try {
            const res = await fetch('{{ route("users.store") }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: formData,
            });
            const data = await res.json();
            if (data.success || res.ok) {
                closeAddUserModal();
                window.location.reload();
            } else {
                const errBox = document.getElementById('addUserError');
                errBox.textContent = data.message || Object.values(data.errors || {}).flat().join(' | ');
                errBox.classList.remove('hidden');
            }
        } catch (ex) {
            document.getElementById('addUserError').textContent = 'حدث خطأ أثناء حفظ المستخدم.';
            document.getElementById('addUserError').classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>حفظ وإنشاء المستخدم</span>';
        }
    });

    // ---- إرسال فورم تعديل مستخدم ----
    document.getElementById('editUserForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn    = document.getElementById('editUserSubmitBtn');
        const userId = document.getElementById('editUserId').value;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ التحديث...';

        const formData = new FormData(this);

        // عند تعطيل الحقول لحساب الأدمن الحالي، يتم تضمين قيمها يدوياً حتى لا تفشل الفاليديشن بالباك إند
        const roleEl = document.getElementById('editUserRole');
        const activeEl = document.getElementById('editIsActive');
        if (roleEl && roleEl.disabled) formData.append('role', roleEl.value);
        if (activeEl && activeEl.disabled) formData.append('is_active', activeEl.checked ? '1' : '0');

        // ضمان إرسال checkboxes عند إلغاء التحديد
        if (!formData.has('can_start_shift')) formData.append('can_start_shift', '0');
        if (!formData.has('is_active')) formData.append('is_active', '0');

        try {
            const res = await fetch(`${USERS_API_URL}/${userId}?_method=PUT`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: formData,
            });
            const data = await res.json();
            if (data.success || res.ok) {
                closeEditUserModal();
                window.location.reload();
            } else {
                const errBox = document.getElementById('editUserError');
                errBox.textContent = data.message || Object.values(data.errors || {}).flat().join(' | ');
                errBox.classList.remove('hidden');
            }
        } catch (ex) {
            document.getElementById('editUserError').textContent = 'حدث خطأ أثناء تعديل المستخدم.';
            document.getElementById('editUserError').classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>حفظ التعديلات</span>';
        }
    });

    // ---- حذف مستخدم ----
    async function deleteUser(id, name, btn = null) {
        if (!confirm(`هل أنت متأكد من حذف المستخدم "${name}" نهائياً من النظام؟`)) return;

        let originalHtml = '';
        if (btn) {
            originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i>';
        }

        try {
            const res  = await fetch(`${USERS_API_URL}/${id}`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
                body: JSON.stringify({ _method: 'DELETE' }),
            });
            const data = await res.json();
            if (data.success) {
                const row = document.getElementById(`user-row-${id}`);
                row?.remove();
                alert(data.message);
                filterUsersTable();
            } else {
                alert(data.message || 'حدث خطأ أثناء محاولة الحذف.');
            }
        } catch {
            alert('حدث خطأ في الاتصال.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    }

    // ---- تفعيل / تعطيل مستخدم بنقرة زر ----
    async function toggleUserStatus(id, btn) {
        try {
            const res  = await fetch(`${USERS_API_URL}/${id}/toggle-status`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            const data = await res.json();
            if (data.success) {
                const isActive = data.is_active;
                btn.dataset.active = isActive ? '1' : '0';
                btn.className = `status-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold text-[10px] transition border ${isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'}`;
                btn.innerHTML = `<i class="fa-solid ${isActive ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600'}"></i> <span>${isActive ? 'مفعّل' : 'معطّل'}</span>`;
                
                const row = document.getElementById(`user-row-${id}`);
                if (row) row.dataset.active = isActive ? '1' : '0';
            } else {
                alert(data.message);
            }
        } catch {
            alert('حدث خطأ في الاتصال.');
        }
    }

    // ---- تفعيل / تعطيل صلاحية فتح الشيفت بنقرة زر ----
    async function toggleUserShiftPermission(id, btn) {
        try {
            const res  = await fetch(`${USERS_API_URL}/${id}/toggle-shift`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            const data = await res.json();
            if (data.success) {
                const canShift = data.can_start_shift;
                btn.dataset.shift = canShift ? '1' : '0';
                btn.className = `shift-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold text-[10px] transition border ${canShift ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200'}`;
                btn.innerHTML = `<i class="fa-solid ${canShift ? 'fa-clock-rotate-left text-emerald-600' : 'fa-ban text-gray-400'}"></i> <span>${canShift ? 'مسموح' : 'ممنوع'}</span>`;

                const row = document.getElementById(`user-row-${id}`);
                if (row) row.dataset.shift = canShift ? '1' : '0';
            } else {
                alert(data.message);
            }
        } catch {
            alert('حدث خطأ في الاتصال.');
        }
    }

</script>
@endpush
@endsection
