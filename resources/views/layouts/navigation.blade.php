<nav class="navbar fixed-top navbar-expand-lg" style="background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); border-bottom: 1px solid var(--glass-border);">
    <div class="container">
        <button class="btn border-0 text-white" onclick="toggleMenu()">
            <svg width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <a class="navbar-brand fw-extrabold text-white ms-3" href="#">
            <span style="color: var(--primary);">PRODUCTION</span> CORE
        </a>

        <div class="collapse navbar-collapse d-none d-lg-block">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="#">Home</a>
                </li>

                @if (Auth::user()->role == 'admin')
                    <li class="nav-item">
                        <a class="nav-link text-white px-3" href="#">Dashboard</a>
                    </li>
                @endif

                @if (Auth::user()->role == 'supervisor')
                    <li class="nav-item">
                        <a class="nav-link text-white px-3" href="#">Employee Interactions</a>
                    </li>
                @endif
            </ul>
        </div>

        <div class="ms-auto d-flex align-items-center">
            <span class="d-none d-md-inline me-3 opacity-75">Welcome, {{ Auth::user()->name }}</span>
            <div class="vr me-3 d-none d-md-block" style="height: 30px; color: white;"></div>
            <button class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" style="background: var(--primary); border:none;">
                Notifications
            </button>
        </div>
    </div>
</nav>