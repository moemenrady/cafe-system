<!DOCTYPE html>
<html id="htmlRoot" lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagmedix - @yield('title')</title>
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
                        sidebar: '#111827',
                        activeGold: '#8A704C',
                        bodyBg: '#F3F4F6'
                    }
                }
            }
        }
    </script>

    <style>
        body.ar {
            font-family: Cairo, sans-serif;
        }

        body.en {
            font-family: Inter, sans-serif;
        }

        ::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .active-tab {
            background-color: #8A704C !important;
            color: white !important;
        }

        /* تأثيرات القائمة المنسدلة */
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

<body class="bg-bodyBg font-sans text-gray-800 flex h-screen overflow-hidden">




    <div id="test" class="test"></div>



    <!-- Mobile Overlay -->
    <div id="mobileOverlay"
        class="fixed inset-0 bg-black/50 z-20 hidden md:hidden transition-opacity opacity-0 duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-30 w-64 bg-sidebar text-gray-400 flex flex-col transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out h-full border-r border-gray-800 shadow-2xl md:shadow-none">

        <!-- Sidebar Header (Logo & Close Btn) -->
        <div class="flex items-center justify-between p-6">
            <div class="text-white font-bold text-xl tracking-wider flex items-center gap-3">
                <i class="fa-solid fa-layer-group text-activeGold"></i>
                <span data-ar="تاج ميديكس" data-en="TAGMEDIX">تاج ميديكس</span>
            </div>
            <!-- Close button for mobile -->
            <button id="closeSidebarBtn" class="md:hidden text-gray-400 hover:text-white transition">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 space-y-1 text-sm font-medium pb-4">
            <a
                href=""class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('dashboard') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-chart-line w-5"></i>
                <span data-ar="لوحة التحكم" data-en="Dashboard">لوحة التحكم</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('inventory.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-boxes-stacked w-5"></i>
                <span data-ar="المخزون" data-en="Inventory">المخزون</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('orders.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-clipboard-list w-5"></i>
                <span data-ar="الطلبات" data-en="Orders">الطلبات</span>
            </a>

            <a href=""
                class="flex items-center justify-between px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('production.*') ? 'active-tab' : '' }}">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-industry w-5"></i>
                    <span data-ar="مسار عمل الطلبات" data-en="Orders Work Flow">مسار عمل الطلبات</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('payments.*') ? 'active-tab' : '' }}">
                <i class="fa-regular fa-credit-card w-5"></i>
                <span data-ar="المدفوعات" data-en="Payments">المدفوعات</span>
            </a>

            <a href=""
                class="testt">
                <i class="fa-solid fa-file-invoice-dollar w-5"></i>
                <span data-ar="الرواتب" data-en="Payroll">الرواتب</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('suppliers.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-truck-field w-5"></i>
                <span data-ar="الموردون" data-en="Suppliers">الموردون</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('returns.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-rotate-left w-5"></i>
                <span data-ar="المرتجعات" data-en="Returns">المرتجعات</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('finished-goods.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-box-open w-5"></i>
                <span data-ar="المنتجات النهائية" data-en="Finished Goods">المنتجات النهائية</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('reports.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-chart-bar w-5"></i>
                <span data-ar="التقارير" data-en="Reports">التقارير</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('users.*') ? 'active-tab' : '' }}">
                <i class="fa-regular fa-user w-5"></i>
                <span data-ar="المستخدمون" data-en="Users">المستخدمون</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('employees.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-users w-5"></i>
                <span data-ar="الموظفون" data-en="Employees">الموظفون</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('settings.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-gear w-5"></i>
                <span data-ar="الإعدادات" data-en="Settings">الإعدادات</span>
            </a>

            <a href=""
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:text-white transition {{ request()->routeIs('order_classifications.*') ? 'active-tab' : '' }}">
                <i class="fa-solid fa-tags w-5"></i>
                <span data-ar="تصنيفات الطلبات" data-en="Order Classifications">تصنيفات الطلبات</span>
            </a>
        </nav>

        <!-- Profile & Logout Section -->
        <div class="p-4 mt-auto border-t border-gray-800 relative">

            <!-- User Info Trigger -->
            <div id="profileDropdownBtn"
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-800 cursor-pointer transition select-none">
                <div
                    class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white text-xs shrink-0">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="flex-1 overflow-hidden">
                    <p class="text-white text-sm font-medium truncate" data-ar="المدير" data-en="Admin">المدير</p>
                    <p class="text-[11px] text-gray-500 truncate" data-ar="مدير النظام" data-en="Super Admin">مدير
                        النظام</p>
                </div>
                <i class="fa-solid fa-chevron-up text-gray-500 text-xs transition-transform duration-200"
                    id="profileChevron"></i>
            </div>

            <!-- Dropdown Menu -->
            <div id="profileDropdown"
                class="absolute bottom-[80px] left-4 right-4 bg-gray-800 border border-gray-700 rounded-lg shadow-xl overflow-hidden hidden dropdown-enter">
                <div class="p-2 space-y-1">
                    <a href=""
                        class="flex items-center gap-3 px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-700 rounded-md transition">
                        <i class="fa-solid fa-user-pen w-4 text-center"></i>
                        <span data-ar="تعديل الملف الشخصي" data-en="Edit Profile">تعديل الملف الشخصي</span>
                    </a>

                    <div class="h-px bg-gray-700 my-1"></div>

                    <!-- Laravel Logout Form -->
                    <form method="POST" action="{{ route('logout') ?? '#' }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-gray-700 rounded-md transition text-left rtl:text-right">
                            <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center"></i>
                            <span data-ar="تسجيل الخروج" data-en="Logout">تسجيل الخروج</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden w-full">

        <!-- Header -->
        <header class="bg-white border-b border-gray-200 p-4 flex justify-between items-center z-10 shrink-0">
            <div class="flex items-center gap-4">
                <!-- Hamburger Menu Button (Mobile/Tablet only) -->
                <button id="openSidebarBtn"
                    class="md:hidden text-gray-600 hover:text-gray-900 focus:outline-none p-1">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <div>
                    <h1 class="text-xl font-bold text-gray-800" data-ar="لوحة التحكم" data-en="Dashboard">لوحة التحكم
                    </h1>
                    <p class="text-sm text-gray-500 hidden sm:block" data-ar="مرحباً بعودتك" data-en="Welcome back">
                        مرحباً بعودتك</p>
                </div>
            </div>

            <div class="flex items-center gap-2 md:gap-4">
                <button
                    class="flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-1.5 md:px-4 md:py-2 text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    <span class="hidden sm:inline" data-ar="اليوم" data-en="Today">اليوم</span>
                    <i class="fa-regular fa-calendar sm:mx-1"></i>
                </button>
                <button id="languageBtn"
                    class="border rounded-lg px-4 py-2 text-sm transition hover:bg-gray-50 font-medium">
                    🇪🇬 العربية
                </button>
                <button class="relative p-2 text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-regular fa-bell text-lg md:text-xl"></i>
                    <span
                        class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>
            </div>
        </header>

        <!-- Dynamic Content Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 relative">
            @yield('content')
        </div>

    </main>

    <!-- Scripts section -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Elements
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebarBtn');
            const closeBtn = document.getElementById('closeSidebarBtn');
            const overlay = document.getElementById('mobileOverlay');

            // Profile Dropdown Elements
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const profileChevron = document.getElementById('profileChevron');

            // Toggle Sidebar Function
            function toggleSidebar() {
                const isOpen = !sidebar.classList.contains('-translate-x-full');

                if (isOpen) {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.remove('opacity-100');
                    overlay.classList.add('opacity-0');
                    setTimeout(() => overlay.classList.add('hidden'), 300); // Wait for transition
                } else {
                    overlay.classList.remove('hidden');
                    // Small delay to allow display:block to apply before transition
                    setTimeout(() => {
                        sidebar.classList.remove('-translate-x-full');
                        overlay.classList.remove('opacity-0');
                        overlay.classList.add('opacity-100');
                    }, 10);
                }
            }

            // Sidebar Event Listeners
            openBtn.addEventListener('click', toggleSidebar);
            closeBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);

            // Toggle Profile Dropdown
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = profileDropdown.classList.contains('hidden');

                if (isHidden) {
                    profileDropdown.classList.remove('hidden');
                    // Trigger animation
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

            // Close dropdown when clicking anywhere outside
            document.addEventListener('click', function(e) {
                if (!profileDropdown.contains(e.target) && !profileBtn.contains(e.target) && !
                    profileDropdown.classList.contains('hidden')) {
                    profileDropdown.classList.add('dropdown-enter');
                    profileDropdown.classList.remove('dropdown-enter-active');
                    profileChevron.classList.remove('rotate-180');
                    setTimeout(() => profileDropdown.classList.add('hidden'), 200);
                }
            });
        });

        // Language Switcher Logic
        const html = document.getElementById("htmlRoot");
        const btn = document.getElementById("languageBtn");

        function applyLanguage(lang) {
            localStorage.setItem("lang", lang);
            html.lang = lang;
            html.dir = lang === "ar" ? "rtl" : "ltr";

            document.body.classList.remove("ar", "en");
            document.body.classList.add(lang);

            // Update all texts
            document.querySelectorAll("[data-ar]").forEach(el => {
                el.innerHTML = lang === "ar" ? el.dataset.ar : el.dataset.en;
            });

            // Update button text
            btn.innerHTML = lang === "ar" ? "🇪🇬 العربية" : "🇺🇸 English";
        }

        const saved = localStorage.getItem("lang") || "ar";
        applyLanguage(saved);

        btn.onclick = () => {
            const current = localStorage.getItem("lang");
            applyLanguage(current === "ar" ? "en" : "ar");
        }
    </script>

    @stack('scripts')
</body>

</html>
