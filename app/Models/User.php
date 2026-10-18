<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'can_start_shift',
        'sidebar_order',
        'main_tab',
        'sidebar_group_order',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_start_shift' => 'boolean',
            'is_active' => 'boolean',
            'sidebar_order' => 'array',
            'sidebar_group_order' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Roles Helpers
    |--------------------------------------------------------------------------
    */

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'supervisor', 'manager'], true);
    }

    public function isEmployee(): bool
    {
        return in_array($this->role, ['cashier', 'barista', 'client'], true);
    }

    public function canStartShift(): bool
    {
        return (bool) ($this->can_start_shift ?? true);
    }

    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function activeShift()
    {
        return $this->hasOne(Shift::class)->where('status', 'open')->latestOfMany();
    }

    public function shiftActions()
    {
        return $this->hasMany(ShiftAction::class);
    }

    /**
     * قائمة عناصر الناف بار المتاحة للمستخدم بحسب دوره
     */
    /**
     * تعريف المجموعات الخمسة الرئيسية للناف بار (Groups Definition)
     * لا تزيد عن 5 مجموعات أبداً، خالية من أي عناوين فرعية معقدة، وتعمل كـ accordion ذكي
     */
    public static function getSidebarGroupsDefinition(): array
    {
        return [
            'operations' => [
                'key'         => 'operations',
                'label'       => 'العمليات والطلبات',
                'label_en'    => 'Operations & POS',
                'icon'        => 'fa-solid fa-mug-hot',
                'badge_color' => 'amber',
            ],
            'financials' => [
                'key'         => 'financials',
                'label'       => 'المالية والورديات',
                'label_en'    => 'Financials & Shifts',
                'icon'        => 'fa-solid fa-coins',
                'badge_color' => 'emerald',
            ],
            'inventory' => [
                'key'         => 'inventory',
                'label'       => 'المخزن والمشتريات',
                'label_en'    => 'Inventory & Stock',
                'icon'        => 'fa-solid fa-boxes-stacked',
                'badge_color' => 'sky',
            ],
            'analytics' => [
                'key'         => 'analytics',
                'label'       => 'التحليلات والتقارير',
                'label_en'    => 'Analytics & Reports',
                'icon'        => 'fa-solid fa-chart-pie',
                'badge_color' => 'purple',
            ],
            'management' => [
                'key'         => 'management',
                'label'       => 'النظام والإدارة',
                'label_en'    => 'Management & System',
                'icon'        => 'fa-solid fa-gears',
                'badge_color' => 'rose',
            ],
        ];
    }

    /**
     * قائمة عناصر الناف بار المتاحة للمستخدم بحسب دوره
     */
    public function getAvailableSidebarItems(): array
    {
        $items = [];

        // 1️⃣ مجموعة العمليات والطلبات (operations)
        $items['pos'] = [
            'key'            => 'pos',
            'title'          => 'البيع (نقطة البيع)',
            'title_en'       => 'POS / Sales',
            'subtitle'       => 'نقطة البيع السريع والطلبات',
            'route'          => 'pos.index',
            'active_pattern' => 'pos*',
            'icon'           => 'fa-solid fa-cash-register',
            'group'          => 'operations',
            'group_label'    => 'العمليات والطلبات',
            'group_icon'     => 'fa-solid fa-mug-hot',
        ];

        $items['busy_tables'] = [
            'key'            => 'busy_tables',
            'title'          => 'الترابيزات المشغولة',
            'title_en'       => 'Busy Tables',
            'subtitle'       => 'متابعة الصالة والطلبات المعلقة',
            'route'          => 'tables.busy_tables',
            'active_pattern' => 'tables/busy*',
            'icon'           => 'fa-solid fa-bell-concierge',
            'group'          => 'operations',
            'group_label'    => 'العمليات والطلبات',
            'group_icon'     => 'fa-solid fa-mug-hot',
        ];

        if ($this->isManager()) {
            $items['tables'] = [
                'key'            => 'tables',
                'title'          => 'إدارة الترابيزات والصالة',
                'title_en'       => 'Tables Management',
                'subtitle'       => 'خريطة وترتيب ترابيزات الكافيه',
                'route'          => 'tables.index',
                'active_pattern' => 'tables',
                'icon'           => 'fa-solid fa-table-cells-large',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
            ];
        }

        $items['menu'] = [
            'key'            => 'menu',
            'title'          => 'المينيو والأصناف',
            'title_en'       => 'Menu Items',
            'subtitle'       => 'قائمة المشروبات والحلويات والأسعار',
            'route'          => 'menu.index',
            'active_pattern' => 'menu*',
            'icon'           => 'fa-solid fa-utensils',
            'group'          => 'operations',
            'group_label'    => 'العمليات والطلبات',
            'group_icon'     => 'fa-solid fa-mug-hot',
        ];

        if ($this->isManager()) {
            $items['categories'] = [
                'key'            => 'categories',
                'title'          => 'تصنيفات المينيو',
                'title_en'       => 'Categories',
                'subtitle'       => 'أقسام وتصنيفات المشروبات',
                'route'          => 'categories.index',
                'active_pattern' => 'categories*',
                'icon'           => 'fa-solid fa-layer-group',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
            ];
        }

        if ($this->isManager() || $this->role === 'barista') {
            $items['recipes'] = [
                'key'            => 'recipes',
                'title'          => 'الوصفات وتكاليف المكونات',
                'title_en'       => 'Recipes & Costs',
                'subtitle'       => 'معايير التحضير وتكلفة الخامة',
                'route'          => 'recipes.index',
                'active_pattern' => 'recipes*',
                'icon'           => 'fa-solid fa-receipt',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
            ];
        }

        // 2️⃣ مجموعة المالية والورديات (financials)
        $items['shifts'] = [
            'key'            => 'shifts',
            'title'          => 'الشيفت (ورديتي)',
            'title_en'       => 'My Shift',
            'subtitle'       => 'بدء وإنهاء وردية العمل والعهدة',
            'route'          => 'shifts.my_shift',
            'active_pattern' => 'shifts/my-shift*',
            'icon'           => 'fa-solid fa-clock',
            'group'          => 'financials',
            'group_label'    => 'المالية والورديات',
            'group_icon'     => 'fa-solid fa-coins',
        ];

        if ($this->isManager()) {
            $items['shifts_management'] = [
                'key'            => 'shifts_management',
                'title'          => 'إدارة ومتابعة الشيفتات',
                'title_en'       => 'Shifts Management',
                'subtitle'       => 'سجل وتقارير ورديات الكاشير',
                'route'          => 'shifts.index',
                'active_pattern' => 'shifts',
                'icon'           => 'fa-solid fa-clock-rotate-left',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
            ];
        }

        $items['sales_invoices'] = [
            'key'            => 'sales_invoices',
            'title'          => 'فواتير المبيعات',
            'title_en'       => 'Sales Invoices',
            'subtitle'       => 'أرشيف وسجل فواتير البيع',
            'route'          => 'sales-invoices.index',
            'active_pattern' => 'sales-invoices*',
            'icon'           => 'fa-solid fa-file-invoice-dollar',
            'group'          => 'financials',
            'group_label'    => 'المالية والورديات',
            'group_icon'     => 'fa-solid fa-coins',
        ];

        $items['expenses'] = [
            'key'            => 'expenses',
            'title'          => 'المصروفات',
            'title_en'       => 'Expenses',
            'subtitle'       => 'تسجيل ومتابعة المصروفات اليومية',
            'route'          => 'expenses.index',
            'active_pattern' => 'expenses*',
            'icon'           => 'fa-solid fa-wallet',
            'group'          => 'financials',
            'group_label'    => 'المالية والورديات',
            'group_icon'     => 'fa-solid fa-coins',
        ];

        if ($this->isManager()) {
            $items['expense_categories'] = [
                'key'            => 'expense_categories',
                'title'          => 'أنواع وبنود المصروفات',
                'title_en'       => 'Expense Categories',
                'subtitle'       => 'تصنيفات وأبواب المصاريف',
                'route'          => 'expense-categories.index',
                'active_pattern' => 'expense-categories*',
                'icon'           => 'fa-solid fa-tags',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
            ];

            $items['withdrawals'] = [
                'key'            => 'withdrawals',
                'title'          => 'مسحوبات الموظفين',
                'title_en'       => 'Staff Withdrawals',
                'subtitle'       => 'سجل السلف ومسحوبات الكادر',
                'route'          => 'withdrawals.index',
                'active_pattern' => 'withdrawals*',
                'icon'           => 'fa-solid fa-hand-holding-dollar',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
            ];
        }

        // 3️⃣ مجموعة المخزن والمشتريات (inventory)
        if ($this->isManager()) {
            $items['inventory'] = [
                'key'            => 'inventory',
                'title'          => 'المخزن والجرد',
                'title_en'       => 'Inventory',
                'subtitle'       => 'أرصدة الخامات وتنبيهات النواقص',
                'route'          => 'inventory.index',
                'active_pattern' => 'inventory',
                'icon'           => 'fa-solid fa-boxes-stacked',
                'group'          => 'inventory',
                'group_label'    => 'المخزن والمشتريات',
                'group_icon'     => 'fa-solid fa-boxes-stacked',
            ];

            $items['inventory_tracking'] = [
                'key'            => 'inventory_tracking',
                'title'          => 'متابعة المخزون والاستهلاك',
                'title_en'       => 'Stock Tracking & Costs',
                'subtitle'       => 'متابعة حركة الاستهلاك وتكلفة اليوم',
                'route'          => 'inventory.daily_tracking',
                'active_pattern' => 'inventory/daily-tracking*',
                'icon'           => 'fa-solid fa-chart-line',
                'group'          => 'inventory',
                'group_label'    => 'المخزن والمشتريات',
                'group_icon'     => 'fa-solid fa-boxes-stacked',
            ];

            $items['purchase_invoices'] = [
                'key'            => 'purchase_invoices',
                'title'          => 'فواتير الشراء والتوريدات',
                'title_en'       => 'Purchase Invoices',
                'subtitle'       => 'فواتير الموردين وتوريد الخامات',
                'route'          => 'purchase-invoices.index',
                'active_pattern' => 'purchase-invoices*',
                'icon'           => 'fa-solid fa-file-invoice',
                'group'          => 'inventory',
                'group_label'    => 'المخزن والمشتريات',
                'group_icon'     => 'fa-solid fa-boxes-stacked',
            ];

            $items['suppliers'] = [
                'key'            => 'suppliers',
                'title'          => 'الموردين',
                'title_en'       => 'Suppliers',
                'subtitle'       => 'بيانات الموردين وحساباتهم',
                'route'          => 'suppliers.index',
                'active_pattern' => 'suppliers*',
                'icon'           => 'fa-solid fa-truck-field',
                'group'          => 'inventory',
                'group_label'    => 'المخزن والمشتريات',
                'group_icon'     => 'fa-solid fa-boxes-stacked',
            ];
        }

        // 4️⃣ مجموعة التحليلات والتقارير (analytics)
        if ($this->isManager()) {
            $items['analytics_comprehensive'] = [
                'key'            => 'analytics_comprehensive',
                'title'          => 'التحليل الشامل والذكاء التشغيلي',
                'title_en'       => 'Comprehensive Analytics',
                'subtitle'       => 'تحليلات المبيعات والأرباح وساعات الذروة',
                'route'          => 'analytics.comprehensive',
                'active_pattern' => 'analytics*',
                'icon'           => 'fa-solid fa-chart-pie',
                'group'          => 'analytics',
                'group_label'    => 'التحليلات والتقارير',
                'group_icon'     => 'fa-solid fa-chart-pie',
            ];

            $items['dashboard'] = [
                'key'            => 'dashboard',
                'title'          => 'لوحة التحكم اليومية',
                'title_en'       => 'Dashboard',
                'subtitle'       => 'المؤشرات السريعة لليوم الحالي',
                'route'          => 'dashboard',
                'active_pattern' => 'dashboard*',
                'icon'           => 'fa-solid fa-gauge-high',
                'group'          => 'analytics',
                'group_label'    => 'التحليلات والتقارير',
                'group_icon'     => 'fa-solid fa-chart-pie',
            ];
        }

        // 5️⃣ مجموعة النظام والإدارة (management)
        $items['user_guide'] = [
            'key'            => 'user_guide',
            'title'          => 'دليل الاستخدام التفاعلي',
            'title_en'       => 'User Guide',
            'subtitle'       => 'دليل سؤال وجواب لتشغيل النظام باحترافية',
            'route'          => 'user_guide.index',
            'active_pattern' => 'user-guide*',
            'icon'           => 'fa-solid fa-circle-question',
            'group'          => 'management',
            'group_label'    => 'النظام والإدارة',
            'group_icon'     => 'fa-solid fa-gears',
        ];

        if ($this->isManager()) {
            $items['employees'] = [
                'key'            => 'employees',
                'title'          => 'إدارة الموظفين والرواتب',
                'title_en'       => 'Staff & Payroll',
                'subtitle'       => 'بيانات الكادر ومسير الرواتب',
                'route'          => 'employees.index',
                'active_pattern' => 'employees*',
                'icon'           => 'fa-solid fa-users-gear',
                'group'          => 'management',
                'group_label'    => 'النظام والإدارة',
                'group_icon'     => 'fa-solid fa-gears',
            ];
        }

        if ($this->isManager()) {
            $items['customers'] = [
                'key'            => 'customers',
                'title'          => 'بيانات العملاء',
                'title_en'       => 'Customers',
                'subtitle'       => 'سجل عملاء الكافيه ونقاط الولاء',
                'route'          => 'customers.index',
                'active_pattern' => 'customers*',
                'icon'           => 'fa-solid fa-users',
                'group'          => 'management',
                'group_label'    => 'النظام والإدارة',
                'group_icon'     => 'fa-solid fa-gears',
            ];
        }

        if ($this->role === 'admin') {
            $items['movements'] = [
                'key'            => 'movements',
                'title'          => 'حركات المشرفين',
                'title_en'       => 'Supervisor Movements',
                'subtitle'       => 'سجل الرقابة والأمان الإداري',
                'route'          => 'admin.movements',
                'active_pattern' => 'admin/movements*',
                'icon'           => 'fa-solid fa-shield-halved',
                'group'          => 'management',
                'group_label'    => 'النظام والإدارة',
                'group_icon'     => 'fa-solid fa-gears',
            ];
        }

        $items['settings'] = [
            'key'            => 'settings',
            'title'          => 'الإعدادات وتخصيص النظام',
            'title_en'       => 'Settings',
            'subtitle'       => 'تخصيص الحساب والناف بار والصلاحيات',
            'route'          => 'settings.index',
            'active_pattern' => 'settings*',
            'icon'           => 'fa-solid fa-sliders',
            'group'          => 'management',
            'group_label'    => 'النظام والإدارة',
            'group_icon'     => 'fa-solid fa-gears',
        ];

        return $items;
    }

    /**
     * جلب مفتاح التاب الأساسية للمستخدم (Main Tab Key)
     */
    public function getMainTabKey(): string
    {
        $available = $this->getAvailableSidebarItems();
        if ($this->main_tab && isset($available[$this->main_tab])) {
            return $this->main_tab;
        }

        // الافتراضي: لوحة التحكم للمدير، ونقطة البيع POS للكاشير وباقي الأدوار
        if ($this->isManager()) {
            return isset($available['dashboard']) ? 'dashboard' : 'pos';
        }

        return 'pos';
    }

    /**
     * جلب عنصر التاب الأساسية كاملاً
     */
    public function getMainTabItem(): array
    {
        $available = $this->getAvailableSidebarItems();
        $key = $this->getMainTabKey();
        return $available[$key] ?? $available['pos'] ?? reset($available);
    }

    /**
     * جلب اسم الراوت الخاص بالتاب الأساسية
     */
    public function getMainTabRoute(): string
    {
        $item = $this->getMainTabItem();
        return $item['route'] ?? 'pos.index';
    }

    /**
     * جلب المجموعات الـ 4 أو الـ 5 مرتبة حسب تفضيل المستخدم المحفوظ
     * بحيث يبقى محتوى كل مجموعة ثابتاً بداخلها، ويكون التحكم في ترتيب المجموعات فقط
     */
    public function getOrderedSidebarGroups(): array
    {
        $allGroups = self::getSidebarGroupsDefinition();
        $availableItems = $this->getAvailableSidebarItems();

        // تجميع العناصر داخل مجموعاتها الـ 5
        $grouped = [];
        foreach ($availableItems as $key => $item) {
            $grpKey = $item['group'] ?? 'management';
            if (!isset($grouped[$grpKey])) {
                $grpDef = $allGroups[$grpKey] ?? [
                    'key'         => $grpKey,
                    'label'       => $item['group_label'] ?? $grpKey,
                    'label_en'    => $grpKey,
                    'icon'        => $item['group_icon'] ?? 'fa-solid fa-layer-group',
                    'badge_color' => 'gray',
                ];
                $grouped[$grpKey] = array_merge($grpDef, ['items' => []]);
            }
            $grouped[$grpKey]['items'][$key] = $item;
        }

        // ترتيب المجموعات بحسب ترتيب المستخدم المحفوظ في sidebar_group_order
        $savedOrder = $this->sidebar_group_order;
        if (!empty($savedOrder) && is_array($savedOrder)) {
            $ordered = [];
            foreach ($savedOrder as $gKey) {
                if (isset($grouped[$gKey])) {
                    $ordered[$gKey] = $grouped[$gKey];
                    unset($grouped[$gKey]);
                }
            }
            foreach ($grouped as $gKey => $groupData) {
                $ordered[$gKey] = $groupData;
            }
            return $ordered;
        }

        return $grouped;
    }

    /**
     * جلب عناصر الناف بار مرتبة (للتوافق القديم)
     */
    public function getOrderedSidebarItems(): array
    {
        $available = $this->getAvailableSidebarItems();
        $savedOrder = $this->sidebar_order;

        if (empty($savedOrder) || !is_array($savedOrder)) {
            return $available;
        }

        $ordered = [];
        foreach ($savedOrder as $key) {
            if (isset($available[$key])) {
                $ordered[$key] = $available[$key];
                unset($available[$key]);
            }
        }

        foreach ($available as $key => $item) {
            $ordered[$key] = $item;
        }

        return $ordered;
    }
}
