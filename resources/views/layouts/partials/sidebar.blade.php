<style>
    .top-header {
        background-color: var(--card-bg);
        padding: 15px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        position: sticky;
        top: 0;
        z-index: 99;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .menu-toggle-btn {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        color: var(--text-main);
        cursor: pointer;
    }

    .page-title h1 {
        font-size: 20px;
        color: var(--text-main);
        margin-bottom: 2px;
    }

    .page-title .breadcrumb {
        font-size: 13px;
        color: var(--text-muted);
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    /* أزرار الهيدر الإضافية (يمكنك تمريرها من خلال الـ Yield) */
    .header-actions {
        display: flex;
        gap: 10px;
    }

    .header-icon-btn {
        background: none;
        border: none;
        font-size: 20px;
        color: var(--text-muted);
        cursor: pointer;
        position: relative;
        padding: 5px;
    }
    
    .header-icon-btn .badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: #ef4444;
        color: white;
        font-size: 10px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    @media (max-width: 992px) {
        .menu-toggle-btn { display: block; }
        .top-header { padding: 15px; }
    }
    @media (max-width: 576px) {
        .header-actions { display: none; /* إخفاء الأزرار الفرعية في الموبايل الصغير جداً وتوفيرها داخل الصفحة */ }
    }
</style>

<header class="top-header">
    <div class="header-right">
        <button class="menu-toggle-btn" id="menuToggle">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        
        <div class="page-title">
            <h1>@yield('page_title', 'لوحة التحكم')</h1>
            <div class="breadcrumb">@yield('breadcrumb', 'الرئيسية')</div>
        </div>
    </div>

    <div class="header-left">
        <div class="header-actions">
            @yield('header_actions')
        </div>

        <div style="border-right: 1px solid var(--border-color); padding-right: 15px; display:flex; gap:15px; align-items:center;">
            <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; padding:5px 10px; border-radius:6px; border:1px solid var(--border-color); font-size:13px;">
                <span>مايو 2024</span>
                <i class="fa-regular fa-calendar" style="color:var(--text-muted);"></i>
            </div>
            <button class="header-icon-btn">
                <i class="fa-regular fa-bell"></i>
                <span class="badge">3</span>
            </button>
        </div>
    </div>
</header>