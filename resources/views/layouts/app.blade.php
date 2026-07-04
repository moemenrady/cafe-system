<!DOCTYPE html>
<html id="htmlRoot" lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Cafe - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    colors: {
                        sidebar: '#1F2937',
                        /* --dark-parts */
                        cafePrimary: '#7DD3FC',
                        /* --primary */
                        cafeSecondary: '#BAE6FD',
                        /* --secondary */
                        bodyBg: '#F8FCFF' /* --bg-color */
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --primary: #7DD3FC;
            --dark-parts: #1F2937;
            --secondary: #BAE6FD;
            --bg-color: #F8FCFF;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        body.ar {
            font-family: Cairo, sans-serif;
        }

        body.en {
            font-family: Inter, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
        }

        .animate-fade-in {
            animation: fadeIn 0.35s ease-out forwards;
        }

        .animate-slide-in {
            animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.97) translateY(4px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        ::-webkit-scrollbar {
            height: 5px;
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary);
            border-radius: 10px;
        }

        .active-tab {
            background-color: rgba(125, 211, 252, 0.15) !important;
            color: #7DD3FC !important;
            border-left: 3px solid #7DD3FC;
        }

        html[dir="rtl"] .active-tab {
            border-left: none;
            border-right: 3px solid #7DD3FC;
        }

        .dropdown-enter {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
        }

        .dropdown-enter-active {
            opacity: 1;
            transform: scale(1) translateY(0);
            transition: all 0.2s ease-out;
        }
    </style>
</head>

<body class="bg-bodyBg text-gray-800 flex h-screen overflow-hidden">

    <div id="mobileOverlay"
        class="fixed inset-0 bg-black/40 z-20 hidden md:hidden transition-opacity opacity-0 duration-300"></div>

    <aside id="sidebar"
        class="fixed inset-y-0 right-0 md:left-auto z-30 w-64 bg-sidebar text-gray-400 flex flex-col transform translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out h-full border-l md:border-l-0 border-gray-800 shadow-2xl md:shadow-none">

        <div class="flex items-center justify-between p-6 border-b border-gray-800">
            <a href="{{ route('dashboard') }}"
                class="text-white font-bold text-lg tracking-wide flex items-center gap-3">
                <div
                    class="w-8 h-8 rounded-xl bg-cafePrimary flex items-center justify-center text-sidebar animate-pulse">
                    <i class="fa-solid fa-mug-hot text-sm"></i>
                </div>
                <span data-ar="نظام الكافيه" data-en="Cafe System">نظام الكافيه</span>
            </a>
            <button id="closeSidebarBtn" class="md:hidden text-gray-400 hover:text-white transition">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1.5 text-xs font-medium">

            <a href="{{ route('inventory.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('inventory*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-boxes-stacked text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="المخزن" data-en="Inventory">المخزن</span>
            </a>

            <a href="{{ route('categories.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('categories*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-layer-group text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="الأقسام" data-en="Categories">الأقسام</span>
            </a>

            <a href="{{ route('menu.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('menu*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-utensils text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="المينيو" data-en="Menu">المينيو</span>
            </a>

            <a href="{{ route('pos.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('pos*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-cash-register text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="البيع" data-en="POS / Sales">البيع</span>
            </a>

            <a href="{{ route('sales-invoices.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('sales-invoices*') ? 'active-tab' : '' }}">
                <i
                    class="fa-solid fa-file-invoice-dollar text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="فواتير المبيعات" data-en="Sales Invoices">فواتير المبيعات</span>
            </a>

            <a href="{{ route('expenses.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('expenses*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-wallet text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="المصروفات" data-en="Expenses">المصروفات</span>
            </a>

            <a href="{{ route('customers.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('customers*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-users text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="العملاء" data-en="Customers">العملاء</span>
            </a>

            <a href="{{ route('withdrawals.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('withdrawals*') ? 'active-tab' : '' }}">
                <i
                    class="fa-solid fa-hand-holding-dollar text-cafePrimary group-hover:scale-110 transition-transform"></i>
                <span data-ar="مسحوبات الموظفين" data-en="Staff Withdrawals">مسحوبات الموظفين</span>
            </a>

            <!-- 📜 قسم الوصفات: يظهر للأدمن، المشرف، والباريستا فقط ويختفي عن الكاشير والعميل -->
            @if (auth()->user() && in_array(auth()->user()->role, ['admin', 'supervisor', 'barista']))
                <a href="{{ route('recipes.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('recipes*') ? 'active-tab' : '' }}">
                    <i class="fa-solid fa-receipt text-cafePrimary group-hover:scale-110 transition-transform"></i>
                    <span data-ar="الوصفات" data-en="Recipes">الوصفات</span>
                </a>
            @endif

            <!-- ⏰ قسم الشيفتات: يظهر للمشرف والأدمن لمتابعة الحسابات -->
            @if (auth()->user() && in_array(auth()->user()->role, ['admin', 'supervisor']))
                <a href="{{ route('shifts.index') }}"
                    class="flex items-center gap-3 px-4 py-3 bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 rounded-xl hover:bg-yellow-500/20 transition-all duration-200 mt-4 {{ request()->is('shifts*') ? 'active-tab' : '' }}">
                    <i class="fa-solid fa-clock-history"></i>
                    <span data-ar="الشيفتات" data-en="Shifts">الشيفتات</span>
                </a>
            @endif

            <!-- 🔒 لوحة التحكم الخاصة بالمالك فقط (Admin Panel) -->
            @if (auth()->user() && auth()->user()->role === 'admin')
                <div class="pt-4 border-t border-gray-800/60 mt-4 space-y-1.5">
                    <p class="px-4 pb-1 text-[10px] font-bold text-gray-500 uppercase tracking-wider"
                        data-ar="لوحة الإدارة" data-en="Admin Panel">لوحة الإدارة</p>

                    <a href="{{ route('admin.movements') }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('admin/movements*') ? 'active-tab' : '' }}">
                        <i
                            class="fa-solid fa-shield-halved text-yellow-400 group-hover:scale-110 transition-transform"></i>
                        <span data-ar="حركات المشرفين" data-en="Supervisor Movements">حركات المشرفين</span>
                    </a>

                    <a href="{{ route('employees.index') }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('employees*') ? 'active-tab' : '' }}">
                        <i
                            class="fa-solid fa-users-gear text-cafePrimary group-hover:scale-110 transition-transform"></i>
                        <span data-ar="إدارة الموظفين" data-en="Staff Management">إدارة الموظفين</span>
                    </a>

                    <a href="{{ route('management.index') }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('management*') ? 'active-tab' : '' }}">
                        <i
                            class="fa-solid fa-folder-tree text-cafePrimary group-hover:scale-110 transition-transform"></i>
                        <span data-ar="الإدارة" data-en="Management">الإدارة</span>
                    </a>

                    <a href="{{ route('purchase-invoices.index') }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('purchase-invoices*') ? 'active-tab' : '' }}">
                        <i
                            class="fa-solid fa-file-invoice text-cafePrimary group-hover:scale-110 transition-transform"></i>
                        <span data-ar="فواتير الشراء" data-en="Purchase Invoices">فواتير الشراء</span>
                    </a>

                    <a href="{{ route('settings.index') }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl hover:text-white hover:bg-gray-800 transition-all duration-200 group {{ request()->is('settings*') ? 'active-tab' : '' }}">
                        <i class="fa-solid fa-sliders text-cafePrimary group-hover:scale-110 transition-transform"></i>
                        <span data-ar="الاعدادات" data-en="Settings">الاعدادات</span>
                    </a>
                </div>
            @endif
        </nav>

        <div class="p-4 mt-auto border-t border-gray-800 relative">
            <div id="profileDropdownBtn"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-800 cursor-pointer transition select-none">
                <div
                    class="w-8 h-8 rounded-lg bg-gray-700 flex items-center justify-center text-cafePrimary text-xs shrink-0">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="flex-1 overflow-hidden">
                    <p class="text-white text-xs font-semibold truncate">{{ auth()->user()->name ?? 'مستخدم' }}</p>
                    <p class="text-[10px] text-gray-500 truncate" data-ar="صلاحية الحساب" data-en="Account Role">
                        صلاحية الحساب</p>
                </div>
                <i class="fa-solid fa-chevron-up text-gray-500 text-[10px] transition-transform duration-200"
                    id="profileChevron"></i>
            </div>

            <div id="profileDropdown"
                class="absolute bottom-[75px] left-4 right-4 bg-gray-800 border border-gray-700 rounded-xl shadow-xl overflow-hidden hidden dropdown-enter">
                <div class="p-1.5 space-y-0.5">
                    <form method="POST" action="{{ route('logout') ?? '#' }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2 text-xs text-red-400 hover:text-red-300 hover:bg-gray-700 rounded-lg transition text-right rtl:text-right">
                            <i class="fa-solid fa-arrow-right-from-bracket text-center"></i>
                            <span data-ar="تسجيل الخروج" data-en="Logout">تسجيل الخروج</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-hidden w-full">

        <header
            class="bg-white border-b border-gray-100 p-4 flex justify-between items-center z-10 shrink-0 shadow-sm">
            <div class="flex items-center gap-4">
                <button id="openSidebarBtn" class="md:hidden text-gray-600 hover:text-gray-900 p-1">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="text-md font-bold text-gray-800">@yield('page_title', 'لوحة التحكم')</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button id="languageBtn"
                    class="border border-gray-200 rounded-xl px-4 py-2 text-xs transition hover:bg-gray-50 font-semibold shadow-sm flex items-center gap-2">
                    🇪🇬 العربية
                </button>
                <button class="relative p-2 text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 relative animate-fade-in">
            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebarBtn');
            const closeBtn = document.getElementById('closeSidebarBtn');
            const overlay = document.getElementById('mobileOverlay');
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const profileChevron = document.getElementById('profileChevron');
            const html = document.getElementById("htmlRoot");
            const btn = document.getElementById("languageBtn");

            function toggleSidebar() {
                const isRtl = html.dir === "rtl";
                const isOpen = isRtl ? !sidebar.classList.contains('translate-x-full') : !sidebar.classList
                    .contains('-translate-x-full');

                if (isOpen) {
                    sidebar.classList.add(isRtl ? 'translate-x-full' : '-translate-x-full');
                    overlay.classList.remove('opacity-100');
                    setTimeout(() => overlay.classList.add('hidden'), 300);
                } else {
                    overlay.classList.remove('hidden');
                    setTimeout(() => {
                        sidebar.classList.remove(isRtl ? 'translate-x-full' : '-translate-x-full');
                        overlay.classList.add('opacity-100');
                    }, 10);
                }
            }

            if (openBtn) openBtn.addEventListener('click', toggleSidebar);
            if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if (overlay) overlay.addEventListener('click', toggleSidebar);

            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = profileDropdown.classList.contains('hidden');
                if (isHidden) {
                    profileDropdown.classList.remove('hidden');
                    setTimeout(() => {
                        profileDropdown.classList.remove('dropdown-enter');
                        profileDropdown.classList.add('dropdown-enter-active');
                        profileChevron.classList.add('rotate-180');
                    }, 10);
                } else {
                    profileDropdown.classList.add('dropdown-enter');
                    profileDropdown.classList.remove('dropdown-enter-active');
                    profileChevron.classList.remove('rotate-180');
                    setTimeout(() => profileDropdown.classList.add('hidden'), 200);
                }
            });

            document.addEventListener('click', function() {
                if (!profileDropdown.classList.contains('hidden')) {
                    profileDropdown.classList.add('dropdown-enter');
                    profileDropdown.classList.remove('dropdown-enter-active');
                    profileChevron.classList.remove('rotate-180');
                    setTimeout(() => profileDropdown.classList.add('hidden'), 200);
                }
            });

            function applyLanguage(lang) {
                localStorage.setItem("lang", lang);
                html.lang = lang;
                html.dir = lang === "ar" ? "rtl" : "ltr";
                document.body.classList.remove("ar", "en");
                document.body.classList.add(lang);

                if (lang === 'en') {
                    sidebar.classList.remove('right-0', 'translate-x-full');
                    sidebar.classList.add('left-0', '-translate-x-full');
                } else {
                    sidebar.classList.remove('left-0', '-translate-x-full');
                    sidebar.classList.add('right-0', 'translate-x-full');
                }

                document.querySelectorAll("[data-ar]").forEach(el => {
                    el.innerHTML = lang === "ar" ? el.dataset.ar : el.dataset.en;
                });
                btn.innerHTML = lang === "ar" ? "🇪🇬 العربية" : "🇺🇸 English";
            }

            const saved = localStorage.getItem("lang") || "ar";
            applyLanguage(saved);

            btn.onclick = () => {
                const current = localStorage.getItem("lang");
                applyLanguage(current === "ar" ? "en" : "ar");
            }
        });
    </script>
    @stack('scripts')
</body>

</html>