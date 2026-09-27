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

    {{-- قسم 3: إدارة المستخدمين – أدمن ومشرف فقط --}}
    @if(auth()->user()->isManager() && $users !== null)
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 space-y-4">

        {{-- رأس القسم + زر الإضافة --}}
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-black text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-users-gear text-sky-500"></i>
                    <span>إدارة المستخدمين</span>
                    <span class="bg-sky-100 text-sky-700 text-[10px] font-black px-2 py-0.5 rounded-lg">{{ $users->count() }} مستخدم</span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">إضافة وتعديل وحذف حسابات المستخدمين وتفعيل/تعطيل الوصول</p>
            </div>
            <button type="button" onclick="openAddUserModal()"
                class="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition active:scale-95">
                <i class="fa-solid fa-plus"></i>
                إضافة مستخدم
            </button>
        </div>

        {{-- جدول المستخدمين --}}
        <div class="overflow-x-auto rounded-2xl border border-gray-100">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                        <th class="p-3">الاسم</th>
                        <th class="p-3 hidden sm:table-cell">البريد</th>
                        <th class="p-3 text-center">الدور</th>
                        <th class="p-3 text-center">الحالة</th>
                        <th class="p-3 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($users as $u)
                    <tr class="hover:bg-gray-50/50 transition" id="user-row-{{ $u->id }}">
                        <td class="p-3">
                            <div class="font-bold text-gray-800 flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-black text-[11px] shrink-0">
                                    {{ mb_substr($u->name, 0, 1) }}
                                </div>
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span class="text-[9px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-black">أنت</span>
                                @endif
                            </div>
                        </td>
                        <td class="p-3 hidden sm:table-cell text-gray-500 font-mono">{{ $u->email }}</td>
                        <td class="p-3 text-center">
                            @php
                                $roleColors = [
                                    'admin'      => 'bg-red-100 text-red-700',
                                    'supervisor' => 'bg-amber-100 text-amber-700',
                                    'cashier'    => 'bg-blue-100 text-blue-700',
                                    'barista'    => 'bg-purple-100 text-purple-700',
                                ];
                                $roleLabels = [
                                    'admin'      => 'مدير',
                                    'supervisor' => 'مشرف',
                                    'cashier'    => 'كاشير',
                                    'barista'    => 'باريستا',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded-lg font-bold text-[10px] {{ $roleColors[$u->role] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $roleLabels[$u->role] ?? $u->role }}
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            <button type="button"
                                onclick="toggleUserStatus({{ $u->id }}, this)"
                                class="px-2.5 py-1 rounded-lg font-bold text-[10px] transition {{ ($u->is_active ?? true) ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                data-active="{{ ($u->is_active ?? true) ? '1' : '0' }}"
                                {{ $u->id === auth()->id() ? 'disabled' : '' }}>
                                {{ ($u->is_active ?? true) ? 'مفعّل' : 'معطّل' }}
                            </button>
                        </td>
                        <td class="p-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button"
                                    onclick="openEditUserModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->email }}', '{{ $u->role }}')"
                                    class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition"
                                    title="تعديل">
                                    <i class="fa-solid fa-pen text-[10px]"></i>
                                </button>
                                @if($u->id !== auth()->id())
                                <button type="button"
                                    onclick="deleteUser({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                    class="w-7 h-7 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 flex items-center justify-center transition"
                                    title="حذف">
                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- ==========================================
     مودال إضافة مستخدم جديد
     ========================================== --}}
<div id="addUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" dir="rtl">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddUserModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md z-10 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-black text-gray-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-sky-500"></i>
                إضافة مستخدم جديد
            </h3>
            <button onclick="closeAddUserModal()" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addUserForm" class="p-5 space-y-3">
            @csrf
            <div id="addUserError" class="hidden bg-red-50 border border-red-200 text-red-700 text-xs font-bold p-3 rounded-xl"></div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-200" placeholder="محمد أحمد">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني <span class="text-red-500">*</span></label>
                <input type="email" name="email" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-200 font-mono" placeholder="user@example.com">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">الدور <span class="text-red-500">*</span></label>
                <select name="role" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-200">
                    <option value="cashier">كاشير</option>
                    <option value="barista">باريستا</option>
                    <option value="supervisor">مشرف</option>
                    @if(auth()->user()->role === 'admin')
                    <option value="admin">مدير</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">كلمة السر <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="8" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-200" placeholder="8 أحرف على الأقل">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">تأكيد كلمة السر <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required minlength="8" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-200">
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" id="addUserSubmitBtn"
                    class="flex-1 bg-sky-500 hover:bg-sky-600 text-white font-black py-2.5 rounded-xl text-sm transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    إضافة
                </button>
                <button type="button" onclick="closeAddUserModal()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================
     مودال تعديل مستخدم
     ========================================== --}}
<div id="editUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" dir="rtl">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditUserModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md z-10 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-black text-gray-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-amber-500"></i>
                تعديل بيانات المستخدم
            </h3>
            <button onclick="closeEditUserModal()" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editUserForm" class="p-5 space-y-3">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" id="editUserId" name="user_id">
            <div id="editUserError" class="hidden bg-red-50 border border-red-200 text-red-700 text-xs font-bold p-3 rounded-xl"></div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل <span class="text-red-500">*</span></label>
                <input type="text" id="editUserName" name="name" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني <span class="text-red-500">*</span></label>
                <input type="email" id="editUserEmail" name="email" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">الدور <span class="text-red-500">*</span></label>
                <select id="editUserRole" name="role" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="cashier">كاشير</option>
                    <option value="barista">باريستا</option>
                    <option value="supervisor">مشرف</option>
                    @if(auth()->user()->role === 'admin')
                    <option value="admin">مدير</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">كلمة السر الجديدة <span class="text-gray-400 text-[10px] font-normal">(اتركها فارغة لعدم التغيير)</span></label>
                <input type="password" name="password" minlength="8" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">تأكيد كلمة السر</label>
                <input type="password" name="password_confirmation" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200">
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" id="editUserSubmitBtn"
                    class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-black py-2.5 rounded-xl text-sm transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    حفظ التعديلات
                </button>
                <button type="button" onclick="closeEditUserModal()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

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
    // 👥 إدارة المستخدمين – دوال JavaScript
    // ==========================================

    const CSRF = '{{ csrf_token() }}';

    // ---- مودال الإضافة ----
    function openAddUserModal() {
        document.getElementById('addUserModal').classList.remove('hidden');
        document.getElementById('addUserForm').reset();
        document.getElementById('addUserError').classList.add('hidden');
    }
    function closeAddUserModal() {
        document.getElementById('addUserModal').classList.add('hidden');
    }

    // ---- مودال التعديل ----
    function openEditUserModal(id, name, email, role) {
        document.getElementById('editUserId').value    = id;
        document.getElementById('editUserName').value  = name;
        document.getElementById('editUserEmail').value = email;
        document.getElementById('editUserRole').value  = role;
        document.getElementById('editUserError').classList.add('hidden');
        document.getElementById('editUserModal').classList.remove('hidden');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    // ---- إضافة مستخدم ----
    document.getElementById('addUserForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('addUserSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...';

        const formData = new FormData(this);

        try {
            const res  = await fetch('{{ route("users.store") }}', {
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
            document.getElementById('addUserError').textContent = 'حدث خطأ في الاتصال.';
            document.getElementById('addUserError').classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> إضافة';
        }
    });

    // ---- تعديل مستخدم ----
    document.getElementById('editUserForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn    = document.getElementById('editUserSubmitBtn');
        const userId = document.getElementById('editUserId').value;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...';

        const formData = new FormData(this);

        try {
            const res  = await fetch(`/settings/users/${userId}?_method=PUT`, {
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
            document.getElementById('editUserError').textContent = 'حدث خطأ في الاتصال.';
            document.getElementById('editUserError').classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ التعديلات';
        }
    });

    // ---- حذف مستخدم ----
    async function deleteUser(id, name) {
        if (!confirm(`هل تريد حذف المستخدم "${name}"؟ هذا الإجراء لا يمكن التراجع عنه.`)) return;

        try {
            const res  = await fetch(`/settings/users/${id}`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
                body: JSON.stringify({ _method: 'DELETE' }),
            });
            const data = await res.json();
            if (data.success) {
                const row = document.getElementById(`user-row-${id}`);
                row?.remove();
            } else {
                alert(data.message || 'حدث خطأ أثناء الحذف.');
            }
        } catch {
            alert('حدث خطأ في الاتصال.');
        }
    }

    // ---- تفعيل / تعطيل مستخدم ----
    async function toggleUserStatus(id, btn) {
        try {
            const res  = await fetch(`/settings/users/${id}/toggle-status`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            const data = await res.json();
            if (data.success) {
                btn.dataset.active = data.is_active ? '1' : '0';
                btn.textContent    = data.is_active ? 'مفعّل' : 'معطّل';
                btn.className = `px-2.5 py-1 rounded-lg font-bold text-[10px] transition ${data.is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-red-100 text-red-700 hover:bg-red-200'}`;
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
