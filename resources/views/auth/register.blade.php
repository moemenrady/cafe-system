{{-- resources/views/auth/login-fancy.blade.php --}}
<!DOCTYPE html>
<html id="htmlRoot" lang="ar" dir="rtl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title data-ar="تسجيل الدخول | لوحة التحكم" data-en="Login | Dashboard">تسجيل الدخول | لوحة التحكم</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ====== المتغيرات (Theme Colors المحدثة) ====== */
        :root {
            --sidebar: #1F2937;
            /* الرصاصي الداكن - رمادي 800 */
            --active-gold: #7DD3FC;
            /* الأزرق الفاتح - سيكاي بلو */
            --primary: #7DD3FC;
            --primary-hover: #BAE6FD;
            /* درجة أفتح للتفاعل */
            --body-bg: #F1F5F9;
            --white: #FFFFFF;

            --success: #22C55E;
            --warning: #F59E0B;
            --error: #EF4444;
            --info: #3B82F6;

            --text-main: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8F0;

            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* إعدادات الخطوط بناءً على اللغة */
        body.ar {
            font-family: 'Cairo', system-ui, sans-serif;
        }

        body.en {
            font-family: 'Inter', system-ui, sans-serif;
        }

        body {
            background-color: var(--body-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* زر تغيير اللغة */
        .lang-switcher {
            position: fixed;
            top: 20px;
            inset-inline-end: 20px;
            /* ليتجاوب مع الاتجاه */
            z-index: 100;
            background: var(--white);
            color: var(--sidebar);
            border: 2px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lang-switcher:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* ====== الحاوية الرئيسية ====== */
        .layout-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
            background: var(--body-bg);
        }

        /* ====== الجانب المرئي ====== */
        .visual-section {
            flex: 1.2;
            background: var(--sidebar);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            color: var(--white);
        }

        .visual-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(var(--active-gold) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.15;
        }

        /* الأشكال المتحركة */
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: pulseShape 10s ease-in-out infinite alternate;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: rgba(125, 211, 252, 0.25);
            /* #7DD3FC opacity */
            top: -10%;
            inset-inline-end: -10%;
        }

        .shape-2 {
            width: 500px;
            height: 500px;
            background: rgba(0, 0, 0, 0.5);
            bottom: -20%;
            inset-inline-start: -20%;
            animation-delay: -5s;
        }

        .shape-3 {
            width: 300px;
            height: 300px;
            border: 2px solid rgba(125, 211, 252, 0.15);
            border-radius: 30%;
            top: 40%;
            inset-inline-end: 30%;
            filter: none;
            animation: spin 20s linear infinite;
        }

        .visual-content {
            position: relative;
            z-index: 1;
            max-width: 500px;
        }

        .visual-badge {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(125, 211, 252, 0.15);
            color: var(--primary);
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 24px;
            border: 1px solid rgba(125, 211, 252, 0.3);
            backdrop-filter: blur(10px);
        }

        .visual-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--white);
        }

        .visual-content h1 span {
            color: var(--active-gold);
        }

        .visual-content p {
            font-size: 1.1rem;
            color: #94A3B8;
            line-height: 1.8;
        }

        /* ====== جانب نموذج تسجيل الدخول ====== */
        .form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
        }

        .form-card {
            width: 100%;
            max-width: 480px;
            background: var(--white);
            padding: 3.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(31, 41, 55, 0.08);
            position: relative;
            z-index: 2;
            animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .form-header {
            margin-bottom: 2.5rem;
            text-align: start;
            /* مهم ليتبع اتجاه النص */
        }

        .form-header h2 {
            font-size: 2rem;
            color: var(--sidebar);
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* تصميم الحقول (Floating Labels) */
        .input-group {
            position: relative;
            margin-bottom: 1.8rem;
            opacity: 0;
            transform: translateY(10px);
            animation: slideUpFade 0.5s ease forwards;
        }

        .input-group:nth-child(2) {
            animation-delay: 0.1s;
        }

        .input-group:nth-child(3) {
            animation-delay: 0.2s;
        }

        .input-group:nth-child(4) {
            animation-delay: 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 18px 16px 10px;
            font-size: 1rem;
            font-family: inherit;
            color: var(--text-main);
            background: #F8FAFC;
            border: 2px solid transparent;
            border-radius: 12px;
            outline: none;
            transition: var(--transition);
        }

        .input-group input:focus,
        .input-group input:not(:placeholder-shown) {
            background: var(--white);
            border-color: var(--active-gold);
            box-shadow: 0 0 0 4px rgba(125, 211, 252, 0.15);
        }

        .input-group label {
            position: absolute;
            inset-inline-end: 16px;
            /* متجاوب مع الاتجاه بدلاً من right */
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
            pointer-events: none;
            transition: var(--transition);
            background: transparent;
        }

        .input-group input:focus~label,
        .input-group input:not(:placeholder-shown)~label {
            top: 10px;
            font-size: 0.8rem;
            color: var(--sidebar);
            font-weight: 600;
        }

        .toggle-password {
            position: absolute;
            inset-inline-start: 16px;
            /* متجاوب مع الاتجاه بدلاً من left */
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--active-gold);
        }

        /* خيارات إضافية */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            opacity: 0;
            animation: slideUpFade 0.5s 0.4s ease forwards;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .remember-me input {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            background: var(--white);
            cursor: pointer;
            position: relative;
            transition: var(--transition);
        }

        .remember-me input:checked {
            background: var(--sidebar);
            border-color: var(--sidebar);
        }

        .remember-me input:checked::after {
            content: '✔';
            position: absolute;
            color: white;
            font-size: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .forgot-link {
            color: var(--sidebar);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .forgot-link:hover {
            color: var(--active-gold);
        }

        /* زر الإرسال */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--sidebar);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: slideUpFade 0.5s 0.5s ease forwards;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: 0.5s;
        }

        .btn-submit:hover {
            background: var(--primary);
            color: var(--sidebar);
            box-shadow: 0 10px 20px rgba(125, 211, 252, 0.3);
            transform: translateY(-2px);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit svg {
            transition: transform 0.3s ease;
        }

        /* ضبط السهم حسب الاتجاه */
        html[dir="rtl"] .btn-submit:hover svg {
            transform: translateX(-5px);
        }

        html[dir="ltr"] .btn-submit:hover svg {
            transform: translateX(5px);
        }

        .error-msg {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 6px;
            display: block;
            font-weight: 600;
        }

        /* ====== الأنيميشن ====== */
        @keyframes slideUpFade {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulseShape {
            0% {
                transform: scale(1) translate(0, 0);
            }

            100% {
                transform: scale(1.1) translate(20px, 20px);
            }
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        /* ====== التجاوب (Responsive) ====== */
        @media (max-width: 992px) {
            .layout-container {
                flex-direction: column;
            }

            .visual-section {
                flex: none;
                padding: 3rem 2rem;
                align-items: center;
                text-align: center;
            }

            .visual-content h1 {
                font-size: 2.5rem;
            }

            .shape-3 {
                display: none;
            }

            .form-section {
                padding: 2rem 1rem;
                margin-top: -40px;
            }

            .form-card {
                padding: 2.5rem;
                box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.1);
            }
        }

        @media (max-width: 480px) {
            .visual-content h1 {
                font-size: 2rem;
            }

            .form-card {
                padding: 2rem 1.5rem;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>

<body class="ar">

    <button id="languageBtn" class="lang-switcher">
        🇪🇬 العربية
    </button>

    <div class="layout-container">
        <div class="visual-section">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>

            <div class="visual-content">
                <div class="visual-badge" data-ar="✨ نظام إدارة الكافيه الذكي" data-en="✨ Smart Cafe Management System">
                    ✨ نظام إدارة الكافيه الذكي
                </div>

                <h1 data-ar="مرحباً بك في <br><span>البُعد الجديد</span> للأعمال"
                    data-en="Welcome to the <br><span>New Dimension</span> of Business">
                    مرحباً بك في <br><span>البُعد الجديد</span> للأعمال
                </h1>

                <p data-ar="لوحة تحكم مصممة بدقة لتمنحك السيطرة الكاملة، سرعة فائقة في الأداء، وتجربة مستخدم ترتقي بتطلعاتك المهنية."
                    data-en="A meticulously designed dashboard giving you full control, blazing fast performance, and a user experience that elevates your professional aspirations.">
                    لوحة تحكم مصممة بدقة لتمنحك السيطرة الكاملة، سرعة فائقة في الأداء، وتجربة مستخدم ترتقي بتطلعاتك
                    المهنية.
                </p>
            </div>
        </div>

        <div class="form-section">
            <div class="form-card">

                <div class="form-header">
                    <h2 data-ar="تسجيل الدخول 👋" data-en="Welcome Back 👋">تسجيل الدخول 👋</h2>
                    <p data-ar="أدخل بيانات الاعتماد الخاصة بك للوصول لحسابك"
                        data-en="Enter your credentials to access your account">
                        أدخل بيانات الاعتماد الخاصة بك للوصول لحسابك
                    </p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />



                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="input-group">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            autofocus autocomplete="username" placeholder=" ">
                        <label for="email" data-ar="البريد الإلكتروني" data-en="Email Address">البريد
                            الإلكتروني</label>
                        <x-input-error :messages="$errors->get('email')" class="error-msg" />
                    </div>

                    <div class="input-group">
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            placeholder=" ">
                        <label for="password" data-ar="كلمة المرور" data-en="Password">كلمة المرور</label>
                        <div class="toggle-password" onclick="togglePassword()">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="error-msg" />
                    </div>

                    <div class="form-options">
                        <label class="remember-me" for="remember_me">
                            <input id="remember_me" type="checkbox" name="remember">
                            <span data-ar="تذكر بياناتي" data-en="Remember me">تذكر بياناتي</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}" data-ar="نسيت كلمة المرور؟"
                                data-en="Forgot Password?">نسيت كلمة المرور؟</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-submit">
                        <span data-ar="الدخول للوحة التحكم" data-en="Login to Dashboard">الدخول للوحة التحكم</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
        // 1. نظام تغيير اللغات (Language Switcher) المدمج بالـ LocalStorage
        const html = document.getElementById("htmlRoot");
        const langBtn = document.getElementById("languageBtn");

        function applyLanguage(lang) {
            localStorage.setItem("lang", lang);
            html.lang = lang;
            html.dir = lang === "ar" ? "rtl" : "ltr";

            // تغيير فئة البودي لتطبيق الخطوط
            document.body.classList.remove("ar", "en");
            document.body.classList.add(lang);

            // تحديث جميع النصوص التي تحتوي على بيانات الترجمة
            document.querySelectorAll("[data-ar]").forEach(el => {
                el.innerHTML = lang === "ar" ? el.dataset.ar : el.dataset.en;
            });

            // تحديث نص الزر ليعرض اللغة الأخرى المتاحة
            langBtn.innerHTML = lang === "ar" ? "🇺🇸 English" : "🇪🇬 العربية";
        }

        // قراءة اللغة المحفوظة أو تعيين العربية كافتراضي
        const savedLang = localStorage.getItem("lang") || "ar";
        applyLanguage(savedLang);

        // تفعيل الزر للتبديل بين اللغات
        langBtn.onclick = () => {
            const currentLang = localStorage.getItem("lang");
            applyLanguage(currentLang === "ar" ? "en" : "ar");
        }

        // 2. إظهار/إخفاء كلمة المرور (UX)
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML =
                    `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML =
                    `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
            }
        }

        // 3. تأثير بارالاكس (Parallax) مخصص لأجهزة الكمبيوتر
        document.addEventListener('mousemove', (e) => {
            if (window.innerWidth < 992) return;

            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;

            requestAnimationFrame(() => {
                document.querySelector('.shape-1').style.transform = `translate(${x}px, ${y}px)`;
                document.querySelector('.shape-2').style.transform = `translate(${-x}px, ${-y}px)`;
            });
        });
    </script>
</body>

</html>
