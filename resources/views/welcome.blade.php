{{-- resources/views/landing.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Cafe POS | Smart Management</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 (RTL) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">

    <!-- FontAwesome & AOS Animation -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

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

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Glassmorphism Utilities */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 10px 30px rgba(125, 211, 252, 0.15);
            border-radius: 1.5rem;
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(125, 211, 252, 0.25);
        }

        /* Buttons & Ripple */
        .btn-glass {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--text-main);
            font-weight: 700;
            border: none;
            border-radius: 1rem;
            padding: 0.75rem 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(125, 211, 252, 0.4);
            transition: all 0.3s;
        }

        .btn-glass:hover {
            color: var(--text-main);
            transform: scale(1.05);
        }

        .btn-outline-glass {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--text-main);
            font-weight: 700;
            border-radius: 1rem;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s;
        }

        .btn-outline-glass:hover {
            background: var(--primary);
            color: var(--text-main);
        }

        /* Navbar */
        .navbar-glass {
            background: rgba(248, 252, 255, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
        }

        /* Modules Grid */
        .module-icon {
            font-size: 2.5rem;
            background: -webkit-linear-gradient(45deg, #0284c7, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .module-card {
            cursor: pointer;
        }

        /* Timeline / Workflow */
        .workflow-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            position: relative;
        }

        .workflow-timeline::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 5%;
            right: 5%;
            height: 3px;
            background: var(--primary);
            z-index: -1;
            opacity: 0.5;
        }

        .workflow-step {
            background: #fff;
            border: 2px solid var(--primary);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(125, 211, 252, 0.3);
            margin-bottom: 1rem;
        }

        /* Skeleton Loader */
        .skeleton {
            background: #e2e8f0;
            background: linear-gradient(90deg, #e2e8f0 25%, #cbd5e1 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 0.5rem;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        /* Features List */
        .feature-item i {
            color: #10B981;
            margin-left: 0.5rem;
        }

        /* Gradient Text */
        .text-gradient {
            background: linear-gradient(135deg, #0284c7, #7DD3FC);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-glass sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="#">
                <i class="fa-solid fa-mug-hot text-primary-custom me-2"></i> POS<span
                    class="text-gradient">System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">المميزات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#modules">النظام</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">الباقات</a></li>
                </ul>
                <div class="d-flex">
                    <a href="#" class="btn btn-outline-glass me-2">تسجيل الدخول</a>
                    <a href="#demo" class="btn btn-glass">تجربة النظام</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 1. Hero Section -->
    <section id="home" class="py-5 mt-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 order-2 order-lg-1 text-center text-lg-end" data-aos="fade-left">
                    <h1 class="display-4 fw-bold mb-3">Enterprise Cafe POS</h1>
                    <h2 class="text-gradient mb-4">أدر مقهاك باحترافية وسهولة</h2>
                    <p class="lead text-muted mb-5">نظام متكامل يجمع بين نقاط البيع، إدارة المخزون، وشاشات المطبخ في
                        واجهة واحدة سريعة وموثوقة.</p>
                    <div class="d-flex justify-content-center justify-content-lg-start gap-3">
                        <button class="btn btn-glass btn-lg"><i class="fa-solid fa-rocket me-2"></i> إطلاق
                            الديمو</button>
                        <button class="btn btn-outline-glass btn-lg"><i class="fa-solid fa-list-check me-2"></i> عرض
                            الخصائص</button>
                    </div>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 mb-5 mb-lg-0" data-aos="zoom-in">
                    <!-- Placeholder for Illustration -->
                    <div class="glass-card p-4 text-center position-relative">
                        <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80"
                            alt="POS Dashboard" class="img-fluid rounded-4 shadow-sm">
                        <div
                            class="position-absolute top-0 start-0 translate-middle p-3 bg-white rounded-circle shadow-lg">
                            <i class="fa-solid fa-check text-success fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Quick Statistics -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                @php
                    $stats = [
                        [
                            'title' => "Today's Sales",
                            'val' => '12450',
                            'prefix' => 'EGP ',
                            'icon' => 'fa-wallet',
                            'color' => '#10B981',
                        ],
                        [
                            'title' => 'Orders Today',
                            'val' => '145',
                            'prefix' => '',
                            'icon' => 'fa-receipt',
                            'color' => '#3B82F6',
                        ],
                        [
                            'title' => 'Customers',
                            'val' => '82',
                            'prefix' => '',
                            'icon' => 'fa-users',
                            'color' => '#8B5CF6',
                        ],
                        [
                            'title' => 'Low Stock',
                            'val' => '6',
                            'prefix' => '',
                            'icon' => 'fa-box-open',
                            'color' => '#EF4444',
                        ],
                        [
                            'title' => 'Branches',
                            'val' => '3',
                            'prefix' => '',
                            'icon' => 'fa-store',
                            'color' => '#F59E0B',
                        ],
                        [
                            'title' => 'Employees Online',
                            'val' => '12',
                            'prefix' => '',
                            'icon' => 'fa-user-clock',
                            'color' => '#06B6D4',
                        ],
                    ];
                @endphp
                @foreach ($stats as $stat)
                    <div class="col-6 col-md-4 col-lg-2" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        <div class="glass-card text-center p-3">
                            <i class="fa-solid {{ $stat['icon'] }} fs-3 mb-2" style="color: {{ $stat['color'] }}"></i>
                            <h6 class="text-muted mb-1">{{ $stat['title'] }}</h6>
                            <h4 class="fw-bold mb-0">{{ $stat['prefix'] }}<span class="counter"
                                    data-target="{{ $stat['val'] }}">0</span></h4>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 3. Modules (Interactive Grid) -->
    <section id="modules" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="fw-bold">نظام وحدات متكامل (Modules)</h2>
                <p class="text-muted">اضغط على أي وحدة لتجربة الواجهة الحية للنظام</p>
            </div>
            <div class="row g-4">
                @php
                    $modules = [
                        ['icon' => '☕', 'title' => 'POS', 'desc' => 'بيع سريع'],
                        ['icon' => '📦', 'title' => 'Inventory', 'desc' => 'إدارة المخزون'],
                        ['icon' => '🛒', 'title' => 'Purchases', 'desc' => 'المشتريات'],
                        ['icon' => '👨‍🍳', 'title' => 'Kitchen Display', 'desc' => 'شاشات المطبخ'],
                        ['icon' => '🍽️', 'title' => 'Tables', 'desc' => 'إدارة الترابيزات'],
                        ['icon' => '👥', 'title' => 'CRM', 'desc' => 'إدارة العملاء'],
                        ['icon' => '🎁', 'title' => 'Loyalty', 'desc' => 'نقاط العملاء'],
                        ['icon' => '📊', 'title' => 'Reports', 'desc' => 'التقارير'],
                        ['icon' => '💰', 'title' => 'Accounting', 'desc' => 'الحسابات'],
                        ['icon' => '👨‍💼', 'title' => 'Employees', 'desc' => 'الموظفين'],
                        ['icon' => '🏢', 'title' => 'Branches', 'desc' => 'الفروع'],
                        ['icon' => '⚙️', 'title' => 'Settings', 'desc' => 'الإعدادات'],
                    ];
                @endphp
                @foreach ($modules as $mod)
                    <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="{{ $loop->index * 50 }}">
                        <div class="glass-card module-card text-center p-4 h-100"
                            onclick="openInteractiveDemo('{{ $mod['title'] }}')">
                            <div class="fs-1 mb-2">{{ $mod['icon'] }}</div>
                            <h5 class="fw-bold">{{ $mod['title'] }}</h5>
                            <p class="text-muted small mb-0">{{ $mod['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 4. Dashboard Preview Mockup -->
    <section class="py-5">
        <div class="container text-center" data-aos="fade-up">
            <h2 class="fw-bold mb-5">نظرة شاملة على لوحة القيادة</h2>
            <div class="glass-card p-2 p-md-4 d-inline-block position-relative shadow-lg w-100">
                <!-- Using a placeholder for the mockup -->
                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80"
                    alt="Dashboard Mockup" class="img-fluid rounded-4 w-100"
                    style="max-height: 600px; object-fit: cover; opacity: 0.9;">

                <!-- Floating Elements to simulate UI -->
                <div class="position-absolute top-0 start-0 m-4 glass-card p-3 d-none d-md-block"
                    data-aos="fade-right" data-aos-delay="300">
                    <i class="fa-solid fa-chart-line text-success fs-4 mb-2"></i>
                    <h6 class="fw-bold mb-0">المبيعات ترتفع بـ 24%</h6>
                </div>
                <div class="position-absolute bottom-0 end-0 m-4 glass-card p-3 d-none d-md-block"
                    data-aos="fade-left" data-aos-delay="500">
                    <i class="fa-solid fa-bell text-warning fs-4 mb-2"></i>
                    <h6 class="fw-bold mb-0">3 طلبات جديدة للتوصيل</h6>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Features Section -->
    <section id="features" class="py-5 bg-white">
        <div class="container">
            <h2 class="fw-bold text-center mb-5" data-aos="fade-up">كل ما تحتاجه وأكثر</h2>
            <div class="row g-3">
                @php
                    $features = [
                        'Multi Branch',
                        'Multi Warehouse',
                        'Offline Mode',
                        'QR Menu',
                        'Kitchen Screen',
                        'Barcode',
                        'Receipt Printer',
                        'Customer Display',
                        'Coupons',
                        'Discounts',
                        'Taxes',
                        'Delivery',
                        'Reservations',
                        'Analytics',
                        'Audit Logs',
                    ];
                @endphp
                @foreach ($features as $feat)
                    <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up">
                        <div class="glass-card p-3 feature-item d-flex align-items-center">
                            <i class="fa-solid fa-circle-check fs-5"></i>
                            <span class="fw-bold">{{ $feat }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 6. Workflow Timeline -->
    <section class="py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-5" data-aos="fade-up">دورة العمل (Workflow)</h2>
            <div class="workflow-timeline d-none d-md-flex" data-aos="zoom-in">
                <div class="d-flex flex-column align-items-center">
                    <div class="workflow-step"><i class="fa-solid fa-user"></i></div>
                    <span class="fw-bold">Customer</span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div class="workflow-step"><i class="fa-solid fa-desktop"></i></div>
                    <span class="fw-bold">Order</span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div class="workflow-step"><i class="fa-solid fa-fire-burner"></i></div>
                    <span class="fw-bold">Kitchen</span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div class="workflow-step"><i class="fa-solid fa-bell-concierge"></i></div>
                    <span class="fw-bold">Ready</span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div class="workflow-step"><i class="fa-solid fa-credit-card"></i></div>
                    <span class="fw-bold">Payment</span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div class="workflow-step"><i class="fa-solid fa-file-invoice"></i></div>
                    <span class="fw-bold">Receipt</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. Technologies & 9. Why Choose Us -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row g-5">
                <!-- Technologies -->
                <div class="col-lg-6" data-aos="fade-left">
                    <h3 class="fw-bold mb-4">بنيت بأحدث التقنيات</h3>
                    <div class="d-flex flex-wrap gap-2">
                        @php $techs = ['Laravel', 'MySQL', 'Bootstrap', 'Chart.js', 'SweetAlert', 'Spatie Permission', 'Laravel Excel', 'Queue', 'Redis', 'Pusher']; @endphp
                        @foreach ($techs as $tech)
                            <span
                                class="badge bg-light text-dark border p-2 fs-6 rounded-pill glass-card">{{ $tech }}</span>
                        @endforeach
                    </div>
                </div>
                <!-- Why Us -->
                <div class="col-lg-6" data-aos="fade-right">
                    <h3 class="fw-bold mb-4">لماذا نظامنا؟</h3>
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="glass-card p-3"><i class="fa-solid fa-bolt text-warning fs-3 mb-2"></i><br>⚡
                                Fast</div>
                        </div>
                        <div class="col-6">
                            <div class="glass-card p-3"><i
                                    class="fa-solid fa-shield-halved text-success fs-3 mb-2"></i><br>🔒 Secure</div>
                        </div>
                        <div class="col-6">
                            <div class="glass-card p-3"><i
                                    class="fa-solid fa-chart-pie text-info fs-3 mb-2"></i><br>📊 Smart Analytics</div>
                        </div>
                        <div class="col-6">
                            <div class="glass-card p-3"><i class="fa-solid fa-cloud text-primary fs-3 mb-2"></i><br>☁
                                Cloud Ready</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 10. Pricing Demo -->
    <section id="pricing" class="py-5">
        <div class="container">
            <h2 class="fw-bold text-center mb-5" data-aos="fade-up">خطط الأسعار</h2>
            <div class="row g-4 justify-content-center text-center">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="glass-card p-5 h-100">
                        <h4>Starter</h4>
                        <h2 class="fw-bold my-3">400 EGP<small class="fs-6 text-muted">/شهرياً</small></h2>
                        <ul class="list-unstyled mb-4 text-end">
                            <li><i class="fa-solid fa-check text-success me-2"></i> فرع واحد</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> 2 مستخدمين</li>
                        </ul>
                        <button class="btn btn-outline-glass w-100">Request Demo</button>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="glass-card p-5 h-100 border-primary"
                        style="transform: scale(1.05); box-shadow: 0 15px 35px rgba(125, 211, 252, 0.4);">
                        <span
                            class="badge bg-primary position-absolute top-0 start-50 translate-middle rounded-pill px-3 py-2">الأكثر
                            طلباً</span>
                        <h4>Professional</h4>
                        <h2 class="fw-bold my-3">900 EGP<small class="fs-6 text-muted">/شهرياً</small></h2>
                        <ul class="list-unstyled mb-4 text-end">
                            <li><i class="fa-solid fa-check text-success me-2"></i> حتى 3 فروع</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> مستخدمين غير محدودين</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> شاشات المطبخ</li>
                        </ul>
                        <button class="btn btn-glass w-100">Request Demo</button>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="glass-card p-5 h-100">
                        <h4>Enterprise</h4>
                        <h2 class="fw-bold my-3">مخصص</h2>
                        <ul class="list-unstyled mb-4 text-end">
                            <li><i class="fa-solid fa-check text-success me-2"></i> فروع لا محدودة</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> API Access</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> سيرفر خاص</li>
                        </ul>
                        <button class="btn btn-outline-glass w-100">تواصل معنا</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 11. Testimonials & 12. FAQ -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row g-5">
                <!-- Testimonials -->
                <div class="col-lg-6" data-aos="fade-left">
                    <h3 class="fw-bold mb-4">آراء العملاء</h3>
                    <div class="glass-card p-4 mb-3">
                        <div class="text-warning mb-2">⭐⭐⭐⭐⭐</div>
                        <p class="mb-1">"زاد المبيعات عندي بشكل ملحوظ، التحكم في المخزون أنقذني من هدر كبير."</p>
                        <strong class="text-primary-custom">- Ahmed, Coffee Shop Owner</strong>
                    </div>
                    <div class="glass-card p-4">
                        <div class="text-warning mb-2">⭐⭐⭐⭐⭐</div>
                        <p class="mb-1">"أفضل وأسرع نظام استخدمته، الـ UI مريحة جداً للكاشير."</p>
                        <strong class="text-primary-custom">- Mohamed, Cafe Manager</strong>
                    </div>
                </div>
                <!-- FAQ -->
                <div class="col-lg-6" data-aos="fade-right">
                    <h3 class="fw-bold mb-4">الأسئلة الشائعة</h3>
                    <div class="accordion glass-card p-2" id="faqAccordion">
                        <div class="accordion-item bg-transparent border-0 border-bottom">
                            <h2 class="accordion-header"><button class="accordion-button bg-transparent fw-bold"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#faq1">هل يدعم النظام
                                    الفروع المتعددة؟</button></h2>
                            <div id="faq1" class="accordion-collapse collapse show"
                                data-bs-parent="#faqAccordion">
                                <div class="accordion-body">نعم، يدعم إدارة فروع غير محدودة من لوحة تحكم مركزية واحدة.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item bg-transparent border-0 border-bottom">
                            <h2 class="accordion-header"><button
                                    class="accordion-button collapsed bg-transparent fw-bold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq2">هل يدعم الطابعات ودرج
                                    الكاشير؟</button></h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">بالتأكيد، متوافق مع كافة طابعات الإيصالات وأجهزة قراءة
                                    الباركود.</div>
                            </div>
                        </div>
                        <div class="accordion-item bg-transparent border-0">
                            <h2 class="accordion-header"><button
                                    class="accordion-button collapsed bg-transparent fw-bold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq3">هل يعمل بدون إنترنت (Offline
                                    Mode)؟</button></h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">نعم، يعمل الـ POS بدون إنترنت ويقوم بمزامنة البيانات فور
                                    عودة الاتصال.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 13. Footer -->
    <footer class="py-4 navbar-glass mt-5">
        <div class="container text-center">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-mug-hot text-primary-custom me-2"></i> POS<span
                    class="text-gradient">System</span></h4>
            <div class="d-flex justify-content-center gap-3 mb-3">
                <a href="#" class="text-muted text-decoration-none">Home</a>
                <a href="#" class="text-muted text-decoration-none">Features</a>
                <a href="#" class="text-muted text-decoration-none">Pricing</a>
                <a href="#" class="text-muted text-decoration-none">Contact</a>
            </div>
            <div class="d-flex justify-content-center gap-4 fs-4 mb-3">
                <a href="#" class="text-muted"><i class="fa-brands fa-facebook"></i></a>
                <a href="#" class="text-muted"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" class="text-muted"><i class="fa-brands fa-instagram"></i></a>
            </div>
            <p class="text-muted small mb-0">© 2026 Enterprise Cafe POS. All rights reserved. Version 2.1.0</p>
        </div>
    </footer>

    <!-- Interactive Demo Modal (The "Wow" Factor) -->
    <div class="modal fade" id="interactiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content glass-card border-0" style="background: rgba(255, 255, 255, 0.95);">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="demoModalTitle">جاري تحميل واجهة ...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 min-vh-50">
                    <!-- Skeleton Loader -->
                    <div id="demoSkeleton">
                        <div class="row g-3">
                            <div class="col-8">
                                <div class="skeleton w-100 mb-2" style="height: 60px;"></div>
                                <div class="skeleton w-100" style="height: 400px;"></div>
                            </div>
                            <div class="col-4">
                                <div class="skeleton w-100 mb-3" style="height: 150px;"></div>
                                <div class="skeleton w-100" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Fake Content (Fades in after JS delay) -->
                    <div id="demoContent" class="d-none text-center py-5">
                        <i class="fa-solid fa-laptop-code fs-1 text-primary-custom mb-3"></i>
                        <h3 class="fw-bold">واجهة تفاعلية حية</h3>
                        <p class="text-muted">هنا سيتم تحميل الـ Blade View الخاص بـ <span id="moduleName"
                                class="fw-bold text-dark"></span> محمل ببيانات وهمية (Dummy Data) ليعيش العميل تجربة
                            الاستخدام الحقيقية.</p>
                        <button class="btn btn-glass mt-3" data-bs-dismiss="modal">إغلاق وتجربة موديول آخر</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS Animation
        AOS.init({
            duration: 800,
            once: true
        });

        // Number Counter Animation
        const counters = document.querySelectorAll('.counter');
        const speed = 200;
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / speed;
                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }
            };
            // Observe to start animation only when scrolled into view
            let observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    updateCount();
                    observer.disconnect();
                }
            });
            observer.observe(counter);
        });

        // Interactive Demo Modal Logic
        const interactiveModal = new bootstrap.Modal(document.getElementById('interactiveModal'));

        function openInteractiveDemo(moduleTitle) {
            document.getElementById('demoModalTitle').innerText = 'واجهة ' + moduleTitle;
            document.getElementById('moduleName').innerText = moduleTitle;

            // Show skeleton, hide content
            document.getElementById('demoSkeleton').classList.remove('d-none');
            document.getElementById('demoContent').classList.add('d-none');

            interactiveModal.show();

            // Simulate AJAX/View load delay (1.5 seconds)
            setTimeout(() => {
                document.getElementById('demoSkeleton').classList.add('d-none');
                document.getElementById('demoContent').classList.remove('d-none');
                document.getElementById('demoContent').classList.add('animate__animated', 'animate__fadeIn');
            }, 1500);
        }
    </script>
</body>

</html>
