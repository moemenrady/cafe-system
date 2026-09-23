<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta charset="UTF-8">
    <title>

        @yield('title')

    </title>
    @yield('style')
    <style>

        /* مساحة افتراضية أعلى الصفحة (يتم تحديثها ديناميكياً بالـ JS) */
        :root {
            --top-offset: 72px;
            /* fallback */
        }

        /* نستخدم المتغير لتعويد المحتوى على وجود أزرار ثابتة أعلاه */
        body {
            /* نحفظ المسافة الفارغة أعلى المحتوى لتفادي تداخل العناصر الثابتة */
            padding-top: calc(var(--top-offset) + 12px);
            transition: padding-top .18s ease;
        }

        /* زرّ الرجوع (كما عندك) */
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #E6C7FF;
            border-radius: 50%;
            padding: 13px;
            cursor: pointer;
            transition: transform 0.2s;
            z-index: 2000;
        }

        /* زرّات الإجراءات في أعلى اليمين (لو عندك) يجب أن يكون لها هذه الصفة */
        .page-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
        }

        /* Snackbar: لا نضعه عند top:20px مباشرة، بل نستعمل المتغير بحيث يكون أسفل الأزرار الثابتة */
        .snackbar {
            position: fixed;
            top: calc(var(--top-offset) + 8px);
            /* أسفل الأزرار الثابتة */
            right: 20px;
            background: #333;
            color: #fff;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 14px;
            z-index: 1990;
            /* أقل من الأزرار الثابتة حتى لا يغطيها */
            opacity: 0;
            transform: translateX(120%);
            transition: opacity 0.4s ease, transform 0.4s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .snackbar.show {
            opacity: 1;
            transform: translateX(0);
        }

        .snackbar.success {
            background: #28a745;
        }

        .snackbar.error {
            background: #dc3545;
        }

        /* لو في أكثر من snackbar نتباعد بينهم */
        .snackbar-stack+.snackbar-stack {
            margin-top: 8px;
        }

        /* حماية على الشاشات الصغيرة: نقّص المسافة لو تحتاج */
        @media (max-width: 520px) {
            :root {
                --top-offset: 64px;
            }

            body {
                padding-top: calc(var(--top-offset) + 8px);
            }

            .snackbar {
                right: 12px;
                left: 12px;
                top: calc(var(--top-offset) + 8px);
            }
        }
    </style>

    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</head>

<body>
    {{-- ✅ تنبيهات Toast باستخدام Snackbar --}}
    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showSnackbar("{{ session('success') }}", "success");
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showSnackbar("{{ session('error') }}", "error");
            });
        </script>
    @endif

    {{-- زر الرجوع للرئيسيه --}}
    <form action="{{ route('managment.create') }}">
        <button class="back-btn">الإدارة</button>

    </form>

    @yield('content')



    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Pusher JS -->
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
    <!-- Laravel Echo (IIFE) -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

    <script>
        function showSnackbar(message, type = "success") {
            const box = document.createElement('div');
            box.className = `snackbar ${type}`;
            box.textContent = message;
            document.body.appendChild(box);

            setTimeout(() => box.classList.add("show"), 50);

            setTimeout(() => {
                box.classList.remove("show");
                setTimeout(() => box.remove(), 300);
            }, 4000);
        }
    </script>



</body>
