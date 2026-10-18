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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                transform: none;
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
    @stack('styles')
</head>

<body class="bg-bodyBg text-gray-800 flex h-screen overflow-hidden">

    <div id="mobileOverlay"
        class="fixed inset-0 bg-black/40 z-20 hidden md:hidden transition-opacity opacity-0 duration-300"></div>

    <aside id="sidebar"
        class="fixed inset-y-0 right-0 md:left-auto z-30 w-64 bg-sidebar text-gray-400 flex flex-col transform translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out h-full border-l md:border-l-0 border-gray-800 shadow-2xl md:shadow-none">

        <div class="flex items-center justify-between p-6 border-b border-gray-800">
            <a href="{{ auth()->check() && auth()->user()->isManager() ? route('dashboard') : route('pos.index') }}"
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

        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-3.5 text-xs font-medium">
            @if (auth()->check())
                @php
                    $authUser = auth()->user();
                    $mainTabItem = $authUser->getMainTabItem();
                    $currentRoute = request()->route() ? request()->route()->getName() : '';
                    $isMainTabActive = false;
                    if ($currentRoute === $mainTabItem['route']) {
                        $isMainTabActive = true;
                    } elseif ($mainTabItem['key'] === 'pos') {
                        $isMainTabActive = request()->routeIs('pos.*') || request()->is('pos*');
                    } elseif ($mainTabItem['key'] === 'dashboard') {
                        $isMainTabActive = request()->routeIs('dashboard*') || (request()->path() === '/' && !request()->routeIs('pos.*'));
                    } elseif ($mainTabItem['key'] === 'tables') {
                        $isMainTabActive = request()->routeIs('tables.*') && !request()->routeIs('tables.busy_tables');
                    } elseif ($mainTabItem['key'] === 'busy_tables') {
                        $isMainTabActive = request()->routeIs('tables.busy_tables') || request()->is('tables/busy*');
                    } elseif ($mainTabItem['key'] === 'shifts_management') {
                        $isMainTabActive = (request()->routeIs('shifts.index', 'shifts.show') || request()->is('shifts')) && !request()->routeIs('shifts.my_shift');
                    } elseif ($mainTabItem['key'] === 'shifts') {
                        $isMainTabActive = request()->routeIs('shifts.my_shift') || request()->is('shifts/my-shift*');
                    } elseif ($mainTabItem['key'] === 'analytics_comprehensive') {
                        $isMainTabActive = request()->routeIs('analytics.*') || request()->is('analytics*');
                    } elseif ($mainTabItem['key'] === 'user_guide') {
                        $isMainTabActive = request()->routeIs('user_guide.*') || request()->is('user-guide*');
                    } else {
                        $routePrefix = explode('.', $mainTabItem['route'])[0];
                        $isMainTabActive = request()->routeIs($routePrefix . '.*') || (isset($mainTabItem['active_pattern']) && request()->is($mainTabItem['active_pattern']));
                    }

                    $orderedGroups = $authUser->getOrderedSidebarGroups();
                @endphp

                {{-- 1. زر التاب الأساسية الأكثر وضوحاً وتميزاً (Hero Main Tab Button) --}}
                <div class="mb-3">
                    <a href="{{ route($mainTabItem['route']) }}"
                       class="relative group block p-3.5 rounded-2xl transition-all duration-300 {{ $isMainTabActive ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white shadow-lg shadow-amber-500/30 ring-2 ring-amber-400' : 'bg-gradient-to-r from-amber-500/20 via-orange-500/15 to-amber-600/20 hover:from-amber-500 hover:to-orange-500 border border-amber-500/35 text-amber-300 hover:text-white shadow-sm hover:shadow-amber-500/25' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl {{ $isMainTabActive ? 'bg-white/20 text-white' : 'bg-amber-500 text-white group-hover:bg-white/20' }} flex items-center justify-center text-lg shadow-sm transition-transform duration-300 group-hover:scale-110 shrink-0">
                                <i class="{{ $mainTabItem['icon'] }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-black text-sm tracking-wide truncate" data-ar="{{ $mainTabItem['title'] }}" data-en="{{ $mainTabItem['title_en'] ?? $mainTabItem['title'] }}">{{ $mainTabItem['title'] }}</span>
                                    <span class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase shrink-0 {{ $isMainTabActive ? 'bg-white/25 text-white' : 'bg-amber-500/30 text-amber-200 group-hover:bg-white/20 group-hover:text-white' }}">رئيسي</span>
                                </div>
                                <p class="text-[11px] {{ $isMainTabActive ? 'text-amber-100' : 'text-gray-400 group-hover:text-amber-100' }} truncate mt-0.5" data-ar="{{ $mainTabItem['subtitle'] ?? 'الوصول السريع الأساسي' }}" data-en="{{ $mainTabItem['title_en'] ?? 'Quick Access' }}">{{ $mainTabItem['subtitle'] ?? 'الوصول السريع الأساسي' }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- 2. المجموعات الـ 4 أو الـ 5 بدون أي عناوين فرعية وبنظام أكورديون تفاعلي سلس --}}
                <div class="space-y-2.5" id="sidebarAccordionContainer">
                    @foreach ($orderedGroups as $grpKey => $group)
                        @php
                            // فحص هل يحتوي هذا الجروب على التاب النشطة حالياً لفتحه تلقائياً
                            $hasActiveTab = false;
                            foreach ($group['items'] as $itemKey => $item) {
                                $cRoute = request()->route() ? request()->route()->getName() : '';
                                if ($cRoute === $item['route']) {
                                    $hasActiveTab = true;
                                    break;
                                } elseif ($item['key'] === 'tables' && request()->routeIs('tables.*') && !request()->routeIs('tables.busy_tables')) {
                                    $hasActiveTab = true;
                                    break;
                                } elseif ($item['key'] === 'busy_tables' && (request()->routeIs('tables.busy_tables') || request()->is('tables/busy*'))) {
                                    $hasActiveTab = true;
                                    break;
                                } elseif ($item['key'] === 'shifts_management' && ((request()->routeIs('shifts.index', 'shifts.show') || request()->is('shifts')) && !request()->routeIs('shifts.my_shift'))) {
                                    $hasActiveTab = true;
                                    break;
                                } elseif ($item['key'] === 'shifts' && (request()->routeIs('shifts.my_shift') || request()->is('shifts/my-shift*'))) {
                                    $hasActiveTab = true;
                                    break;
                                } elseif ($item['key'] === 'analytics_comprehensive' && (request()->routeIs('analytics.*') || request()->is('analytics*'))) {
                                    $hasActiveTab = true;
                                    break;
                                } elseif ($item['key'] === 'user_guide' && (request()->routeIs('user_guide.*') || request()->is('user-guide*'))) {
                                    $hasActiveTab = true;
                                    break;
                                } else {
                                    $rPrefix = explode('.', $item['route'])[0];
                                    if (request()->routeIs($rPrefix . '.*') || (isset($item['active_pattern']) && request()->is($item['active_pattern']))) {
                                        $hasActiveTab = true;
                                        break;
                                    }
                                }
                            }
                        @endphp

                        <div class="sidebar-accordion-group rounded-2xl bg-gray-900/50 border border-gray-800/80 overflow-hidden transition-all duration-200" data-group="{{ $grpKey }}">
                            {{-- رأس الجروب (Accordion Header) --}}
                            <button type="button"
                                    onclick="toggleSidebarGroup('{{ $grpKey }}')"
                                    class="w-full flex items-center justify-between px-3.5 py-3 text-right hover:bg-gray-800/60 transition group cursor-pointer select-none {{ $hasActiveTab ? 'bg-gray-800/40 text-white' : 'text-gray-300' }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-xl bg-gray-800 border border-gray-700/60 text-cafePrimary flex items-center justify-center text-xs group-hover:scale-105 transition shrink-0 shadow-xs">
                                        <i class="{{ $group['icon'] }}"></i>
                                    </div>
                                    <span class="font-bold text-xs sm:text-[13px] tracking-wide truncate">
                                        {{ $group['label'] }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if($hasActiveTab)
                                        <span class="w-2 h-2 rounded-full bg-cafePrimary animate-pulse"></span>
                                    @endif
                                    <span class="text-[10px] text-gray-500 font-mono px-2 py-0.5 rounded-lg bg-gray-800/80 border border-gray-700/50">
                                        {{ count($group['items']) }}
                                    </span>
                                    <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 group-chevron-{{ $grpKey }} transition-transform duration-200 {{ $hasActiveTab ? 'rotate-180 text-cafePrimary' : '' }}"></i>
                                </div>
                            </button>

                            {{-- محتوى التابات الداخلية (Accordion Content) --}}
                            <div id="group-content-{{ $grpKey }}"
                                 class="sidebar-group-content {{ $hasActiveTab ? '' : 'hidden' }} px-2 pb-2.5 pt-1 space-y-1">
                                @foreach ($group['items'] as $itemKey => $item)
                                    @php
                                        $cRoute = request()->route() ? request()->route()->getName() : '';
                                        $isActive = false;
                                        if ($cRoute === $item['route']) {
                                            $isActive = true;
                                        } elseif ($item['key'] === 'tables') {
                                            $isActive = request()->routeIs('tables.*') && !request()->routeIs('tables.busy_tables');
                                        } elseif ($item['key'] === 'busy_tables') {
                                            $isActive = request()->routeIs('tables.busy_tables') || request()->is('tables/busy*');
                                        } elseif ($item['key'] === 'shifts_management') {
                                            $isActive = (request()->routeIs('shifts.index', 'shifts.show') || request()->is('shifts')) && !request()->routeIs('shifts.my_shift');
                                        } elseif ($item['key'] === 'shifts') {
                                            $isActive = request()->routeIs('shifts.my_shift') || request()->is('shifts/my-shift*');
                                        } elseif ($item['key'] === 'analytics_comprehensive') {
                                            $isActive = request()->routeIs('analytics.*') || request()->is('analytics*');
                                        } elseif ($item['key'] === 'user_guide') {
                                            $isActive = request()->routeIs('user_guide.*') || request()->is('user-guide*');
                                        } else {
                                            $routePrefix = explode('.', $item['route'])[0];
                                            $isActive = request()->routeIs($routePrefix . '.*') || (isset($item['active_pattern']) && request()->is($item['active_pattern']));
                                        }
                                    @endphp

                                    <a href="{{ route($item['route']) }}"
                                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 group {{ $isActive ? 'bg-sky-500/15 text-sky-300 font-black border-r-3 border-sky-400 shadow-2xs' : 'text-gray-300 hover:text-white hover:bg-gray-800/80 font-medium' }}">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-6 h-6 rounded-lg flex items-center justify-center text-xs {{ $isActive ? 'text-sky-300' : 'text-gray-400 group-hover:text-cafePrimary' }} transition shrink-0">
                                                <i class="{{ $item['icon'] }}"></i>
                                            </div>
                                            <span class="truncate text-xs sm:text-[13px] leading-tight" data-ar="{{ $item['title'] }}" data-en="{{ $item['title_en'] ?? $item['title'] }}">{{ $item['title'] }}</span>
                                        </div>
                                        @if ($isActive)
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 shrink-0 shadow-sm shadow-sky-400"></span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- زر مخصص لإعادة ترتيب القائمة الجانبية مرتبط بالحساب --}}
                <div class="pt-3 border-t border-gray-800/80 mt-3">
                    <button type="button" onclick="openSidebarReorderModal()"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-800 transition-all text-xs group">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-arrows-up-down text-cafePrimary group-hover:scale-110 transition-transform"></i>
                            <span data-ar="تخصيص القائمة والتاب الأساسية" data-en="Customize Sidebar & Main Tab">تخصيص القائمة والتاب الأساسية</span>
                        </div>
                        <i class="fa-solid fa-sliders text-[11px] text-gray-500 group-hover:text-cafePrimary transition-colors"></i>
                    </button>
                </div>
            @endif
        </nav>

        <div class="p-4 mt-auto border-t border-gray-800 relative">
            <div id="profileDropdownBtn" onclick="toggleProfileDropdown(event)"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-800 cursor-pointer transition select-none">
                <div
                    class="w-8 h-8 rounded-lg bg-gray-700 flex items-center justify-center text-cafePrimary text-xs shrink-0">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="flex-1 overflow-hidden">
                    <p class="text-white text-xs font-semibold truncate">{{ auth()->user()->name ?? 'مستخدم' }}</p>
                    <p class="text-[10px] text-gray-500 truncate">
                        {{ auth()->user() ? (auth()->user()->role === 'admin' ? 'مدير عام' : (auth()->user()->role === 'supervisor' ? 'مشرف' : (auth()->user()->role === 'cashier' ? 'كاشير' : (auth()->user()->role === 'barista' ? 'باريستا' : auth()->user()->role)))) : 'صلاحية الحساب' }}
                    </p>
                </div>
                <i class="fa-solid fa-chevron-up text-gray-500 text-[10px] transition-transform duration-200"
                    id="profileChevron"></i>
            </div>

            <div id="profileDropdown"
                class="absolute bottom-[75px] left-4 right-4 bg-gray-800 border border-gray-700 rounded-xl shadow-xl overflow-hidden hidden z-50 animate-slide-in">
                <div class="p-1.5 space-y-1">
                    <a href="{{ route('settings.index') }}"
                        class="w-full flex items-center gap-3 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition text-right rtl:text-right">
                        <i class="fa-solid fa-sliders text-cafePrimary"></i>
                        <span data-ar="الإعدادات وتخصيص الحساب" data-en="Settings & Preferences">الإعدادات وتخصيص الحساب</span>
                    </a>
                    <button type="button" onclick="openSidebarReorderModal(); closeAllProfileDropdowns();"
                        class="w-full flex items-center gap-3 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition text-right rtl:text-right">
                        <i class="fa-solid fa-arrows-up-down text-sky-400"></i>
                        <span data-ar="ترتيب القائمة الجانبية" data-en="Reorder Sidebar">ترتيب القائمة الجانبية</span>
                    </button>
                    <div class="border-t border-gray-700/60 my-1"></div>
                    <form method="POST" action="{{ route('logout') ?? '#' }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2 text-xs text-red-400 hover:text-red-300 hover:bg-gray-700 rounded-lg transition text-right rtl:text-right font-bold">
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
            class="bg-white border-b border-gray-100 p-4 flex justify-between items-center z-20 shrink-0 shadow-xs">
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
                    class="border border-gray-200 rounded-xl px-3 py-2 text-xs transition hover:bg-gray-50 font-semibold shadow-xs flex items-center gap-2">
                    🇪🇬 العربية
                </button>
                <button class="relative p-2 text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                </button>

                {{-- ويدجت الملف الشخصي وسهم تسجيل الخروج بالناف بار العلوي --}}
                @auth
                <div class="relative" id="headerProfileContainer">
                    <button type="button" onclick="toggleHeaderProfileDropdown(event)" id="headerProfileBtn"
                        class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition bg-white shadow-xs select-none cursor-pointer">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 text-cafePrimary flex items-center justify-center text-xs shrink-0 font-bold shadow-xs">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold text-gray-800 leading-tight truncate max-w-[120px]">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-gray-400 leading-tight mt-0.5">
                                {{ auth()->user()->role === 'admin' ? 'مدير عام' : (auth()->user()->role === 'supervisor' ? 'مشرف' : (auth()->user()->role === 'cashier' ? 'كاشير' : (auth()->user()->role === 'barista' ? 'باريستا' : auth()->user()->role))) }}
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-down text-gray-400 text-[10px] transition-transform duration-200 ml-0.5" id="headerProfileChevron"></i>
                    </button>

                    <div id="headerProfileDropdown"
                        class="absolute left-0 mt-2 w-56 bg-white border border-gray-100 rounded-2xl shadow-xl overflow-hidden hidden z-50 animate-slide-in">
                        <div class="p-3.5 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gray-800 text-cafePrimary flex items-center justify-center text-sm font-bold shadow-xs shrink-0">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-xs font-black text-gray-800 truncate">{{ auth()->user()->name }}</p>
                                <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100 mt-0.5">
                                    {{ auth()->user()->role === 'admin' ? 'مدير عام' : (auth()->user()->role === 'supervisor' ? 'مشرف' : (auth()->user()->role === 'cashier' ? 'كاشير' : (auth()->user()->role === 'barista' ? 'باريستا' : auth()->user()->role))) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-2 space-y-1">
                            <a href="{{ route('settings.index') }}"
                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-gray-700 hover:text-blue-600 hover:bg-blue-50/70 rounded-xl transition">
                                <i class="fa-solid fa-sliders text-gray-400 text-xs"></i>
                                <span>الإعدادات وتخصيص الحساب</span>
                            </a>
                            <button type="button" onclick="openSidebarReorderModal(); closeAllProfileDropdowns();"
                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-gray-700 hover:text-blue-600 hover:bg-blue-50/70 rounded-xl transition">
                                <i class="fa-solid fa-arrows-up-down text-sky-500 text-xs"></i>
                                <span>ترتيب القائمة الجانبية</span>
                            </button>
                            <div class="border-t border-gray-100 my-1"></div>
                            <form method="POST" action="{{ route('logout') ?? '#' }}">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-red-600 hover:text-red-700 hover:bg-red-50 rounded-xl transition cursor-pointer">
                                    <i class="fa-solid fa-arrow-right-from-bracket text-red-500 text-xs"></i>
                                    <span>تسجيل الخروج</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endauth
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 relative animate-fade-in">
            @yield('content')
        </div>
    </main>

    {{-- مودال تخصيص وإعادة ترتيب القائمة واختيار التاب الأساسية --}}
    @auth
    <div id="reorderSidebarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs hidden transition-opacity duration-300">
        <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] shadow-2xl flex flex-col overflow-hidden text-right border border-gray-100 animate-slide-in" dir="rtl">
            {{-- Modal Header --}}
            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shadow-inner">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-800">تخصيص القائمة والتاب الأساسية</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">اختر التاب الرئيسية لحسابك ورتب المجموعات الخمسة بسهولة</p>
                    </div>
                </div>
                <button type="button" onclick="closeSidebarReorderModal()" class="w-8 h-8 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 overflow-y-auto flex-1 space-y-5">
                {{-- 1. اختيار التاب الأساسية (Main Tab) --}}
                <div class="space-y-2 bg-amber-50/40 p-4 rounded-2xl border border-amber-100">
                    <label class="block text-xs font-black text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-star text-amber-500"></i>
                        <span>التاب الأساسية المفضلة (Hero Tab & Default Landing):</span>
                    </label>
                    <p class="text-[11px] text-gray-500 leading-relaxed">
                        هذه التاب ستظهر أعلى القائمة الجانبية بتصميم Hero المميز مثل صفحة البيع، وسيتم تحويلك إليها فور تسجيل الدخول.
                    </p>
                    @php
                        $userAvailItems = auth()->user()->getAvailableSidebarItems();
                        $currentMainKey = auth()->user()->getMainTabKey();
                    @endphp
                    <select id="modalMainTabSelect" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-sky-300">
                        @foreach ($userAvailItems as $mKey => $mItem)
                            <option value="{{ $mKey }}" {{ $currentMainKey === $mKey ? 'selected' : '' }}>
                                {{ $mItem['title'] }} ({{ $mItem['group_label'] ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. ترتيب المجموعات الـ 4 أو الـ 5 (Reorder Groups) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-black text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-arrows-up-down text-sky-500"></i>
                            <span>ترتيب المجموعات الرئيسية (التابات بالداخل ثابتة):</span>
                        </label>
                        <span class="text-[10px] text-gray-400 font-bold">استخدم الأسهم للترتيب</span>
                    </div>

                    <div class="space-y-2" id="modalGroupList">
                        @php
                            $modalGroups = auth()->user()->getOrderedSidebarGroups();
                        @endphp
                        @foreach ($modalGroups as $gKey => $gData)
                            <div data-group-key="{{ $gKey }}" class="modal-group-item flex items-center justify-between p-3 rounded-2xl border border-gray-100 bg-gray-50/70 hover:bg-gray-100/70 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-white border border-gray-200 text-cafePrimary flex items-center justify-center text-xs shadow-xs">
                                        <i class="{{ $gData['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-xs text-gray-800 block">{{ $gData['label'] }}</span>
                                        <span class="text-[10px] text-gray-400 font-medium">مجموعة تضم {{ count($gData['items']) }} تابات ثابتة</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" onclick="moveModalGroupUp(this)" class="w-7 h-7 rounded-lg bg-white hover:bg-sky-50 text-gray-600 hover:text-sky-600 border border-gray-200 flex items-center justify-center transition" title="تحريك لأعلى">
                                        <i class="fa-solid fa-arrow-up text-[10px]"></i>
                                    </button>
                                    <button type="button" onclick="moveModalGroupDown(this)" class="w-7 h-7 rounded-lg bg-white hover:bg-sky-50 text-gray-600 hover:text-sky-600 border border-gray-200 flex items-center justify-center transition" title="تحريك لأسفل">
                                        <i class="fa-solid fa-arrow-down text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 border-t border-gray-100 bg-gray-50/70 flex items-center justify-between gap-2">
                <button type="button" onclick="resetSidebarOrderModal()" class="px-3.5 py-2 text-xs font-bold text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition">
                    <i class="fa-solid fa-rotate-left ml-1"></i>
                    استعادة الافتراضي
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeSidebarReorderModal()" class="px-3.5 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        إلغاء
                    </button>
                    <button type="button" onclick="saveSidebarOrderModal()" id="modalSaveOrderBtn" class="px-5 py-2 text-xs font-bold bg-sky-500 hover:bg-sky-600 text-white rounded-xl shadow-xs transition active:scale-95 flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>حفظ التخصيص</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endauth

    {{-- مساحة استدعاء النوافذ المنبثقة (Modals) في مستوى الـ Body لمنع مشاكل الـ Fixed والـ Stacking Context --}}
    @stack('modals')

    <script>
        // دالة تبديل مجموعات الأكورديون في القائمة الجانبية (Accordion with auto-close)
        function toggleSidebarGroup(grpKey) {
            const targetContent = document.getElementById('group-content-' + grpKey);
            const targetChevron = document.querySelector('.group-chevron-' + grpKey);
            const isCurrentlyOpen = targetContent && !targetContent.classList.contains('hidden');

            // إغلاق جميع المجموعات المفتوحة الأخرى تلقائياً
            document.querySelectorAll('.sidebar-group-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.querySelectorAll('[class*="group-chevron-"]').forEach(chevron => {
                chevron.classList.remove('rotate-180', 'text-cafePrimary');
            });

            // لو كانت المجموعة المستهدفة مغلقة -> فتحها
            if (!isCurrentlyOpen && targetContent) {
                targetContent.classList.remove('hidden');
                if (targetChevron) {
                    targetChevron.classList.add('rotate-180', 'text-cafePrimary');
                }
            }
        }

        function openSidebarReorderModal() {
            const modal = document.getElementById('reorderSidebarModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeSidebarReorderModal() {
            const modal = document.getElementById('reorderSidebarModal');
            if (modal) modal.classList.add('hidden');
        }

        function moveModalGroupUp(btn) {
            const item = btn.closest('.modal-group-item');
            const prev = item.previousElementSibling;
            if (prev) item.parentNode.insertBefore(item, prev);
        }

        function moveModalGroupDown(btn) {
            const item = btn.closest('.modal-group-item');
            const next = item.nextElementSibling;
            if (next) item.parentNode.insertBefore(next, item);
        }

        async function saveSidebarOrderModal() {
            const btn = document.getElementById('modalSaveOrderBtn');
            const groupItems = document.querySelectorAll('#modalGroupList .modal-group-item');
            const groupOrder = Array.from(groupItems).map(el => el.dataset.groupKey);
            const mainTab = document.getElementById('modalMainTabSelect') ? document.getElementById('modalMainTabSelect').value : null;

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
                    body: JSON.stringify({
                        group_order: groupOrder,
                        main_tab: mainTab
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('حدث خطأ أثناء الحفظ.');
                }
            } catch (e) {
                alert('حدث خطأ أثناء الحفظ.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>حفظ التخصيص</span>';
            }
        }

        async function resetSidebarOrderModal() {
            if (!confirm('هل تريد استعادة الترتيب الافتراضي للقائمة والتاب الأساسية؟')) return;

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

        document.addEventListener('DOMContentLoaded', function() {
            const reorderModalEl = document.getElementById('reorderSidebarModal');
            if (reorderModalEl) {
                reorderModalEl.addEventListener('click', function(e) {
                    if (e.target === reorderModalEl) closeSidebarReorderModal();
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeSidebarReorderModal();
            });

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

            // التبديل بين اللغات
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
                if (btn) btn.innerHTML = lang === "ar" ? "🇪🇬 العربية" : "🇺🇸 English";
            }

            const saved = localStorage.getItem("lang") || "ar";
            applyLanguage(saved);

            if (btn) {
                btn.onclick = () => {
                    const current = localStorage.getItem("lang");
                    applyLanguage(current === "ar" ? "en" : "ar");
                };
            }
        });

        // دوال فتح وإغلاق قوائم المستخدم وتسجيل الخروج (السهم بالناف بار والسايد بار)
        function toggleProfileDropdown(e) {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('profileDropdown');
            const chevron = document.getElementById('profileChevron');
            if (!dropdown) return;
            const isHidden = dropdown.classList.contains('hidden');
            closeAllProfileDropdowns();
            if (isHidden) {
                dropdown.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function toggleHeaderProfileDropdown(e) {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('headerProfileDropdown');
            const chevron = document.getElementById('headerProfileChevron');
            if (!dropdown) return;
            const isHidden = dropdown.classList.contains('hidden');
            closeAllProfileDropdowns();
            if (isHidden) {
                dropdown.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function closeAllProfileDropdowns() {
            const dropdown = document.getElementById('profileDropdown');
            const chevron = document.getElementById('profileChevron');
            const hDropdown = document.getElementById('headerProfileDropdown');
            const hChevron = document.getElementById('headerProfileChevron');

            if (dropdown && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
            }
            if (hDropdown && !hDropdown.classList.contains('hidden')) {
                hDropdown.classList.add('hidden');
                if (hChevron) hChevron.classList.remove('rotate-180');
            }
        }

        document.addEventListener('click', function(e) {
            closeAllProfileDropdowns();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllProfileDropdowns();
            }
        });

        // 🔄 نبض البقاء نشطاً (Session Keep-Alive) لمنع خطأ 419 Page Expired نهائياً
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                fetch("{{ route('keep_alive') }}", {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                }).catch(function(err) {
                    console.log('Session keep-alive ping');
                });
            }
        }, 10 * 60 * 1000);
    </script>
    @stack('scripts')
</body>

</html>
