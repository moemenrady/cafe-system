<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('style')

    <style>
        :root {
            --prime: #ddcdbc;
            --prime-soft: #e6ddd4;
            --bg: #515831;
            --bg-dark: #3f4526;
            --white: #ffffff;
        }

        /* خلفية سينماتيك متحركة */
        body {
            min-height: 100vh;
            background: linear-gradient(-45deg, var(--bg), var(--bg-dark), var(--bg));
            background-size: 400% 400%;
            animation: gradientMove 14s ease infinite;
            color: var(--white);
            font-family: system-ui, sans-serif;
            padding: 80px 16px 40px;
        }

        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Animation دخول الصفحة */
        .page-animate {
            animation: fadeUp .7s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Glass Container */
        .show-container {
            max-width: 900px;
            margin: auto;
            background: rgba(221, 205, 188, 0.18);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(221, 205, 188, 0.35);
            border-radius: 22px;
            padding: 26px 22px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .35);
        }

        /* Title */
        .show-title {
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            color: var(--prime);
            margin-bottom: 20px;
            letter-spacing: .4px;
        }

        /* Back Buttons */
        .back-main,
        .back-history {
            position: fixed;
            top: 18px;
            background: linear-gradient(135deg, var(--prime), var(--prime-soft));
            color: var(--bg);
            border: none;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 26px rgba(0, 0, 0, .3);
            transition: .25s ease;
            z-index: 2000;
        }

        .back-main {
            right: 20px;
        }

        .back-history {
            left: 20px;
        }

        .back-main:hover,
        .back-history:hover {
            transform: scale(1.1) rotate(-8deg);
        }

        /* Snackbar */
        .snackbar {
            position: fixed;
            top: 80px;
            right: 20px;
            background: var(--bg-dark);
            color: var(--white);
            padding: 12px 18px;
            border-radius: 14px;
            font-size: 14px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .35);
            opacity: 0;
            transform: translateX(120%);
            transition: .4s ease;
            z-index: 3000;
        }

        .snackbar.show {
            opacity: 1;
            transform: translateX(0);
        }

        .snackbar.success {
            border-left: 4px solid var(--prime);
        }

        .snackbar.error {
            border-left: 4px solid #ff6b6b;
        }

        /* Mobile */
        @media (max-width: 600px) {
            .show-container {
                padding: 20px 16px;
            }

            .show-title {
                font-size: 18px;
            }
        }
    </style>
    <!-- Bootstrap CSS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    {{-- زر الرجوع للرئيسية --}}
    <form action="{{ route('main.create') }}">
        <button class="back-main">🏠</button>
    </form>

    {{-- زر الرجوع للخلف --}}
    <button class="back-history" onclick="history.back()">
        <i class="fas fa-arrow-left"></i>
    </button>

    {{-- محتوى الصفحة --}}
    <div class="show-container page-animate">

        <div class="show-title">
            @yield('page_title')
        </div>

        @yield('content')

    </div>

    {{-- Snackbar --}}
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

    <script>
        function showSnackbar(message, type) {
            const sb = document.createElement("div");
            sb.className = `snackbar ${type}`;
            sb.innerText = message;
            document.body.appendChild(sb);

            setTimeout(() => sb.classList.add("show"), 100);
            setTimeout(() => {
                sb.classList.remove("show");
                setTimeout(() => sb.remove(), 400);
            }, 3000);
        }
    </script>

</body>

</html>
