{{-- resources/views/layouts/analytics.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <!-- CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <title>@yield('title', 'لوحة التحليلات')</title>

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    :root{
      --bg:#07070a;
      --card: rgba(255,255,255,0.03);
      --glass: rgba(255,255,255,0.02);
      --accent-grad: linear-gradient(90deg,#F8E0C1,#D9B1AB);
      --muted: rgba(233,237,240,0.66);
      --glass-border: rgba(255,255,255,0.04);
      --accent-shadow: rgba(217,177,171,0.08);
    }
    html,body{height:100%}
    body{
      margin:0;
      min-height:100vh;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background:
        radial-gradient(1200px 600px at 10% 10%, rgba(217,177,171,0.06), transparent 10%),
        radial-gradient(1000px 500px at 90% 90%, rgba(248,224,193,0.04), transparent 8%),
        var(--bg);
      color:#e9edf0;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      overflow-x:hidden;
      direction: rtl;
    }

    /* page layout */
    .analytics-wrap{ min-height:100vh; display:flex; gap:20px; padding:28px; box-sizing:border-box; }
    .sidebar{
      width:260px; min-width:200px; border-radius:14px;
      background: linear-gradient(180deg, var(--glass), rgba(255,255,255,0.008));
      padding:18px; box-shadow: 0 10px 30px rgba(0,0,0,0.6);
      border:1px solid var(--glass-border);
      position:relative;
      overflow:hidden;
    }
    .main{
      flex:1; display:flex; flex-direction:column; gap:18px;
    }

    /* header */
    .analytics-header{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .page-title { font-weight:800; font-size:20px; background:var(--accent-grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
    .sub-muted { color:var(--muted); font-size:13px }

    /* cards grid */
    .stats-grid{ display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:14px; }
    .card{
      background: linear-gradient(180deg, rgba(255,255,255,0.018), rgba(255,255,255,0.01));
      padding:14px; border-radius:12px; border:1px solid var(--glass-border);
      box-shadow: 0 8px 30px rgba(2,6,23,0.6);
    }
    .card .num{ font-weight:800; font-size:20px }
    .card .label{ color:var(--muted); font-size:13px }

    /* content area */
    .content-row{ display:grid; grid-template-columns: 1fr 360px; gap:18px; align-items:start; }
    @media (max-width:1000px){ .content-row{ grid-template-columns:1fr } .sidebar{ display:none } }

    /* small components */
    .glass-box{ background:var(--card); padding:12px; border-radius:10px; border:1px solid var(--glass-border) }
    .chart-placeholder{ height:260px; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:14px }

    /* decorative shine + dots (مثل صفحة اللوجين) */
    .shine{
      position:absolute; left:-30%; top:-20%; width:160%; height:200%;
      background: linear-gradient(120deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.06) 20%, rgba(255,255,255,0.0) 40%);
      transform: rotate(-12deg);
      animation: slowShift 10s linear infinite;
      pointer-events:none;
    }
    @keyframes slowShift{ 0%{transform:translateX(-30%) rotate(-12deg)} 50%{transform:translateX(0%) rotate(-12deg)} 100%{transform:translateX(30%) rotate(-12deg)} }

    .dots { position:absolute; inset:0; pointer-events:none; }
    .dot {
      position:absolute; width:8px;height:8px;border-radius:999px;
      background:rgba(217,177,171,0.14); box-shadow: 0 0 18px rgba(217,177,171,0.06);
      animation: floatDot 8s ease-in-out infinite;
    }
    @keyframes floatDot{ 0%{transform:translateY(0) translateX(0) scale(1)} 50%{transform:translateY(-18px) translateX(6px) scale(1.15)} 100%{transform:translateY(0) translateX(0) scale(1)} }

    /* nice table */
    table.analytics-table{ width:100%; border-collapse:collapse; font-size:13px; color:#e9edf0; }
    table.analytics-table th, table.analytics-table td{ padding:10px 12px; text-align:right }
    table.analytics-table thead th{ color:var(--muted); font-weight:700; font-size:12px }
    table.analytics-table tbody tr{ border-bottom:1px solid rgba(255,255,255,0.03) }

    /* small helpers */
    .muted{ color:var(--muted) }
  </style>
  @yield('style')
</head>
<body>
  <div class="analytics-wrap">

    {{-- SIDEBAR --}}
    <aside class="sidebar" aria-hidden="false">
      <div style="display:flex; gap:12px; align-items:center; margin-bottom:16px">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRKV4y9snY_3mPxdOqtcAngk1wiaw_qc0HkCQ&s" alt="logo" style="width:44px;height:44px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.6)">
        <div>
          <div style="font-weight:800">Analytics</div>
          <div class="muted" style="font-size:12px">لوحة التحليل العامة</div>
        </div>
      </div>

      <nav style="display:flex; flex-direction:column; gap:8px">
        <a href="{{ route('analytics.all') ?? '#' }}" style="text-decoration:none; color:inherit; display:flex; justify-content:space-between; align-items:center; padding:8px; border-radius:8px;">
          <div class="muted">التحليل العام</div>
          <div class="muted">📊</div>
        </a>

        {{-- Links to each analysis page --}}
        <a href="{{ route('analytics.bookings') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">الحجوزات</a>
        <a href="{{ route('analytics.clients') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">العملاء</a>
        <a href="{{ route('analytics.halls') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">القاعات</a>
        <a href="{{ route('analytics.money') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">التحصيل</a>
        <a href="{{ route('analytics.plans') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">الخطط</a>
        <a href="{{ route('analytics.products') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">المنتجات</a>
        <a href="{{ route('analytics.sessions') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">الجلسات</a>
        <a href="{{ route('analytics.subscriptions') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">الاشتراكات</a>
        <a href="{{ route('analytics.users') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">المستخدمين</a>
        <a href="{{ route('analytics.visits') ?? '#' }}" style="text-decoration:none; color:inherit; padding:8px; border-radius:8px">الزيارات</a>
      </nav>

      <div style="margin-top:18px" class="glass-box">
        <div style="font-weight:700">ملخص سريع</div>
        <div class="muted" style="font-size:13px; margin-top:8px">إجمالي اليوم: <strong>--</strong></div>
      </div>

      <div class="shine" aria-hidden="true"></div>
      <div class="dots" aria-hidden="true">
        <div class="dot" style="left:8%; top:10%; width:6px;height:6px; animation-delay:-1s"></div>
        <div class="dot" style="left:20%; top:70%; width:9px;height:9px; animation-delay:-2s"></div>
        <div class="dot" style="left:78%; top:36%; width:7px;height:7px; animation-delay:-3s"></div>
      </div>
    </aside>

    {{-- MAIN --}}
    <main class="main">
    @yield('content')  
    </main>

  </div>

  {{-- small script to add hover effect like login page --}}
  <script>
    (function(){
      const sidebar = document.querySelector('.sidebar');
      if(!sidebar) return;
      if(window.matchMedia('(pointer: coarse)').matches) return;
      let left = sidebar;
      window.addEventListener('mousemove', (evt)=>{
        const rect = left.getBoundingClientRect();
        const cx = rect.left + rect.width/2;
        const cy = rect.top + rect.height/2;
        const dx = (evt.clientX - cx)/rect.width;
        const dy = (evt.clientY - cy)/rect.height;
        left.style.transform = `translate3d(${dx*6}px, ${dy*6}px, 0)`;
      });
    })();
  </script>
</body>
</html>
