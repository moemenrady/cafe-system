<nav class="navbar-custom">
    <a href="{{ route('session.index-manager') }}" class="{{ Request::is('sessions') ? 'active' : '' }}">
        <i class="fa-solid fa-chair"></i>
        <span class="label">الجلسات</span>
    </a>

    <a href="{{ route('bookings.index-manager') }}" class="{{ Request::is('bookings-manager') ? 'active' : '' }}">
        <i class="fa-solid fa-calendar-days"></i>
        <span class="label">الحجوزات</span>
    </a>

    <a href="{{ route('subscriptions.index-manager') }}" class="{{ Request::is('subscriptions-manager') ? 'active' : '' }}">
        <i class="fa-solid fa-id-card"></i>
        <span class="label">الاشتراكات</span>
    </a>

    <a href="{{ route('sale_proccess.create') }}" class="{{ Request::is('sale-proccess/create') ? 'active' : '' }}">
        <i class="fa-solid fa-cart-plus"></i>
        <span class="label">بيع منفصل</span>
    </a>
        <a href="{{ route('invoice.index') }}" class="{{ Request::is('invoices') ? 'active' : '' }}">
                <i class="fa-solid fa-file-invoice"></i>
      
          <span class="label">الفواتير</span>
    </a>

    @if (Auth::user()->role === 'user')
        <a href="{{ route('expense-drafts.index') }}" class="{{ Request::is('expense-drafts') ? 'active' : '' }}">
            <i class="fa-solid fa-coins"></i>
            <span class="label">المصروف</span>
        </a>
    @endif

    @if (Auth::user()->role === 'admin')
        <a href="{{ route('expenses.create') }}" class="{{ Request::is('expenses/create') ? 'active' : '' }}">
            <i class="fa-solid fa-money-bill-wave"></i>
            <span class="label">المصروف</span>
        </a>

        <a href="{{ route('admin.calendar') }}" class="{{ Request::is('admin/calendar') ? 'active' : '' }}">
            <i class="fa-solid fa-calendar-week"></i>
            <span class="label">اليومي</span>
        </a>
    @endif
</nav>
