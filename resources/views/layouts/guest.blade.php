<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --prime: #ddcdbc;
            --bg: #515831;
            --white: #ffffff;
            --prime-soft: #e6ddd4;
            --bg-dark: #3f4526;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(
                -45deg,
                var(--bg),
                var(--bg-dark),
                var(--bg),
                #4a502d
            );
            background-size: 400% 400%;
            animation: gradientMove 14s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-family: system-ui, sans-serif;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* موج ناعم باللون الأساسي */
        .wave {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 220px;
            background: linear-gradient(
                to right,
                transparent,
                rgba(221, 205, 188, 0.25),
                transparent
            );
            border-radius: 100%;
            animation: waveMove 12s linear infinite;
        }

        .wave:nth-child(2) {
            animation-duration: 18s;
            opacity: .18;
            bottom: 40px;
        }

        @keyframes waveMove {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }

        /* مثال ستايل للكروت */
        .glass-box {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            border-radius: 18px;
            padding: 30px;
            border: 1px solid rgba(221, 205, 188, 0.3);
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
        }

        .btn-prime {
            background: var(--prime);
            color: var(--bg);
            padding: 10px 22px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s ease;
        }

        .btn-prime:hover {
            background: var(--prime-soft);
        }
    </style>
</head>

<body>
    <div class="wave"></div>
    <div class="wave"></div>

    {{ $slot }}
</body>
</html>
