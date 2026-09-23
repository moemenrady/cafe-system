<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'can_start_shift',
        'sidebar_order',
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
        return in_array($this->role, ['admin', 'supervisor'], true);
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
    public function getAvailableSidebarItems(): array
    {
        // 1. للموظف (كاشير، باريستا، عميل، أو غير مدير)
        // يظهر له فقط: بيع ومينيو والترابيزات المشغولة والمصروفات والشيفت ورديتي وإعدادات فقط
        if (!$this->isManager()) {
            return [
                'pos' => [
                    'key'            => 'pos',
                    'title'          => 'البيع (نقطة البيع)',
                    'title_en'       => 'POS / Sales',
                    'route'          => 'pos.index',
                    'active_pattern' => 'pos*',
                    'icon'           => 'fa-solid fa-cash-register',
                    'group'          => 'operations',
                    'group_label'    => 'العمليات والطلبات',
                    'group_icon'     => 'fa-solid fa-mug-hot',
                    'subgroup'       => 'pos',
                    'subgroup_label' => 'نقطة البيع',
                    'is_hero'        => true,
                ],
                'busy_tables' => [
                    'key'            => 'busy_tables',
                    'title'          => 'الترابيزات المشغولة',
                    'title_en'       => 'Busy Tables',
                    'route'          => 'tables.busy_tables',
                    'active_pattern' => 'tables/busy*',
                    'icon'           => 'fa-solid fa-bell-concierge',
                    'group'          => 'operations',
                    'group_label'    => 'العمليات والطلبات',
                    'group_icon'     => 'fa-solid fa-mug-hot',
                    'subgroup'       => 'tables',
                    'subgroup_label' => 'الترابيزات والصالة',
                ],
                'menu' => [
                    'key'            => 'menu',
                    'title'          => 'المينيو',
                    'title_en'       => 'Menu',
                    'route'          => 'menu.index',
                    'active_pattern' => 'menu*',
                    'icon'           => 'fa-solid fa-utensils',
                    'group'          => 'operations',
                    'group_label'    => 'العمليات والطلبات',
                    'group_icon'     => 'fa-solid fa-mug-hot',
                    'subgroup'       => 'food',
                    'subgroup_label' => 'الأصناف والمينيو',
                ],
                'shifts' => [
                    'key'            => 'shifts',
                    'title'          => 'الشيفت (ورديتي)',
                    'title_en'       => 'My Shift',
                    'route'          => 'shifts.my_shift',
                    'active_pattern' => 'shifts/my-shift*',
                    'icon'           => 'fa-solid fa-clock',
                    'group'          => 'financials',
                    'group_label'    => 'المالية والورديات',
                    'group_icon'     => 'fa-solid fa-coins',
                    'subgroup'       => 'shifts',
                    'subgroup_label' => 'الورديات',
                ],
                'expenses' => [
                    'key'            => 'expenses',
                    'title'          => 'المصروفات',
                    'title_en'       => 'Expenses',
                    'route'          => 'expenses.index',
                    'active_pattern' => 'expenses*',
                    'icon'           => 'fa-solid fa-wallet',
                    'group'          => 'financials',
                    'group_label'    => 'المالية والورديات',
                    'group_icon'     => 'fa-solid fa-coins',
                    'subgroup'       => 'accounting',
                    'subgroup_label' => 'المصروفات',
                ],
                'settings' => [
                    'key'            => 'settings',
                    'title'          => 'الإعدادات',
                    'title_en'       => 'Settings',
                    'route'          => 'settings.index',
                    'active_pattern' => 'settings*',
                    'icon'           => 'fa-solid fa-sliders',
                    'group'          => 'system',
                    'group_label'    => 'النظام والحساب',
                    'group_icon'     => 'fa-solid fa-gear',
                    'subgroup'       => 'settings',
                    'subgroup_label' => 'الإعدادات',
                ],
            ];
        }

        // 2. للمدير والمشرف (Admin / Supervisor)
        $items = [
            'pos' => [
                'key'            => 'pos',
                'title'          => 'البيع (نقطة البيع)',
                'title_en'       => 'POS / Sales',
                'route'          => 'pos.index',
                'active_pattern' => 'pos*',
                'icon'           => 'fa-solid fa-cash-register',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
                'subgroup'       => 'pos',
                'subgroup_label' => 'نقطة البيع',
                'is_hero'        => true,
            ],
            'tables' => [
                'key'            => 'tables',
                'title'          => 'إدارة الترابيزات',
                'title_en'       => 'Tables',
                'route'          => 'tables.index',
                'active_pattern' => 'tables',
                'icon'           => 'fa-solid fa-table-cells-large',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
                'subgroup'       => 'tables',
                'subgroup_label' => 'الترابيزات والصالة',
            ],
            'busy_tables' => [
                'key'            => 'busy_tables',
                'title'          => 'الترابيزات المشغولة',
                'title_en'       => 'Busy Tables',
                'route'          => 'tables.busy_tables',
                'active_pattern' => 'tables/busy*',
                'icon'           => 'fa-solid fa-bell-concierge',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
                'subgroup'       => 'tables',
                'subgroup_label' => 'الترابيزات والصالة',
            ],
            'menu' => [
                'key'            => 'menu',
                'title'          => 'المينيو',
                'title_en'       => 'Menu',
                'route'          => 'menu.index',
                'active_pattern' => 'menu*',
                'icon'           => 'fa-solid fa-utensils',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
                'subgroup'       => 'food',
                'subgroup_label' => 'الأصناف والمينيو',
            ],
            'categories' => [
                'key'            => 'categories',
                'title'          => 'الأقسام',
                'title_en'       => 'Categories',
                'route'          => 'categories.index',
                'active_pattern' => 'categories*',
                'icon'           => 'fa-solid fa-layer-group',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
                'subgroup'       => 'food',
                'subgroup_label' => 'الأصناف والمينيو',
            ],
            'recipes' => [
                'key'            => 'recipes',
                'title'          => 'الوصفات والتكاليف',
                'title_en'       => 'Recipes',
                'route'          => 'recipes.index',
                'active_pattern' => 'recipes*',
                'icon'           => 'fa-solid fa-receipt',
                'group'          => 'operations',
                'group_label'    => 'العمليات والطلبات',
                'group_icon'     => 'fa-solid fa-mug-hot',
                'subgroup'       => 'food',
                'subgroup_label' => 'الأصناف والمينيو',
            ],

            'shifts_management' => [
                'key'            => 'shifts_management',
                'title'          => 'إدارة الشيفتات',
                'title_en'       => 'Shifts Management',
                'route'          => 'shifts.index',
                'active_pattern' => 'shifts',
                'icon'           => 'fa-solid fa-clock-rotate-left',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
                'subgroup'       => 'shifts',
                'subgroup_label' => 'الورديات',
            ],
            'shifts' => [
                'key'            => 'shifts',
                'title'          => 'الشيفت (ورديتي)',
                'title_en'       => 'My Shift',
                'route'          => 'shifts.my_shift',
                'active_pattern' => 'shifts/my-shift*',
                'icon'           => 'fa-solid fa-clock',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
                'subgroup'       => 'shifts',
                'subgroup_label' => 'الورديات',
            ],
            'sales_invoices' => [
                'key'            => 'sales_invoices',
                'title'          => 'فواتير المبيعات',
                'title_en'       => 'Sales Invoices',
                'route'          => 'sales-invoices.index',
                'active_pattern' => 'sales-invoices*',
                'icon'           => 'fa-solid fa-file-invoice-dollar',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
                'subgroup'       => 'accounting',
                'subgroup_label' => 'الحسابات والمصروفات',
            ],
            'expenses' => [
                'key'            => 'expenses',
                'title'          => 'المصروفات',
                'title_en'       => 'Expenses',
                'route'          => 'expenses.index',
                'active_pattern' => 'expenses*',
                'icon'           => 'fa-solid fa-wallet',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
                'subgroup'       => 'accounting',
                'subgroup_label' => 'الحسابات والمصروفات',
            ],
            'expense_categories' => [
                'key'            => 'expense_categories',
                'title'          => 'أنواع المصروفات',
                'title_en'       => 'Expense Categories',
                'route'          => 'expense-categories.index',
                'active_pattern' => 'expense-categories*',
                'icon'           => 'fa-solid fa-tags',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
                'subgroup'       => 'accounting',
                'subgroup_label' => 'الحسابات والمصروفات',
            ],
            'withdrawals' => [
                'key'            => 'withdrawals',
                'title'          => 'مسحوبات الموظفين',
                'title_en'       => 'Staff Withdrawals',
                'route'          => 'withdrawals.index',
                'active_pattern' => 'withdrawals*',
                'icon'           => 'fa-solid fa-hand-holding-dollar',
                'group'          => 'financials',
                'group_label'    => 'المالية والورديات',
                'group_icon'     => 'fa-solid fa-coins',
                'subgroup'       => 'accounting',
                'subgroup_label' => 'الحسابات والمصروفات',
            ],

            'inventory' => [
                'key'            => 'inventory',
                'title'          => 'المخزن والجرد',
                'title_en'       => 'Inventory',
                'route'          => 'inventory.index',
                'active_pattern' => 'inventory',
                'icon'           => 'fa-solid fa-boxes-stacked',
                'group'          => 'inventory',
                'group_label'    => 'المخزن والمشتريات',
                'group_icon'     => 'fa-solid fa-boxes-stacked',
                'subgroup'       => 'stock',
                'subgroup_label' => 'المخزون',
            ],
            'inventory_tracking' => [
                'key'            => 'inventory_tracking',
                'title'          => 'متابعة المخزون والتكاليف',
                'title_en'       => 'Cost & Stock Tracking',
                'route'          => 'inventory.daily_tracking',
                'active_pattern' => 'inventory/daily-tracking*',
                'icon'           => 'fa-solid fa-file-invoice-dollar',
                'group'          => 'inventory',
                'group_label'    => 'المخزن والمشتريات',
                'group_icon'     => 'fa-solid fa-boxes-stacked',
                'subgroup'       => 'stock',
                'subgroup_label' => 'المخزون',
            ],
        ];

        // فواتير الشراء خاصة بالأدمن
        if ($this->role === 'admin') {
            $items['purchase_invoices'] = [
                'key'            => 'purchase_invoices',
                'title'          => 'فواتير الشراء',
                'title_en'       => 'Purchase Invoices',
                'route'          => 'purchase-invoices.index',
                'active_pattern' => 'purchase-invoices*',
                'icon'           => 'fa-solid fa-file-invoice',
                'group'          => 'inventory',
                'group_label'    => 'المخزن والمشتريات',
                'group_icon'     => 'fa-solid fa-boxes-stacked',
                'subgroup'       => 'stock',
                'subgroup_label' => 'المخزون',
            ];
        }

        // العملاء
        $items['customers'] = [
            'key'            => 'customers',
            'title'          => 'العملاء',
            'title_en'       => 'Customers',
            'route'          => 'customers.index',
            'active_pattern' => 'customers*',
            'icon'           => 'fa-solid fa-users',
            'group'          => 'crm_users',
            'group_label'    => 'العملاء وفريق العمل',
            'group_icon'     => 'fa-solid fa-user-group',
            'subgroup'       => 'crm',
            'subgroup_label' => 'العملاء',
        ];

        // إدارة الموظفين (أدمن فقط)
        if ($this->role === 'admin') {
            $items['employees'] = [
                'key'            => 'employees',
                'title'          => 'إدارة الموظفين',
                'title_en'       => 'Staff Management',
                'route'          => 'employees.index',
                'active_pattern' => 'employees*',
                'icon'           => 'fa-solid fa-users-gear',
                'group'          => 'crm_users',
                'group_label'    => 'العملاء وفريق العمل',
                'group_icon'     => 'fa-solid fa-user-group',
                'subgroup'       => 'staff',
                'subgroup_label' => 'فريق العمل',
            ];
        }

        // لوحة الإدارة والأمان (أدمن فقط)
        if ($this->role === 'admin') {
            $items['movements'] = [
                'key'            => 'movements',
                'title'          => 'حركات المشرفين',
                'title_en'       => 'Supervisor Movements',
                'route'          => 'admin.movements',
                'active_pattern' => 'admin/movements*',
                'icon'           => 'fa-solid fa-shield-halved',
                'group'          => 'management',
                'group_label'    => 'الإدارة والتحكم',
                'group_icon'     => 'fa-solid fa-folder-tree',
                'subgroup'       => 'system_admin',
                'subgroup_label' => 'النظام والمراقبة',
            ];

            $items['management'] = [
                'key'            => 'management',
                'title'          => 'لوحة الإدارة',
                'title_en'       => 'Management',
                'route'          => 'dashboard',
                'active_pattern' => 'management*',
                'icon'           => 'fa-solid fa-folder-tree',
                'group'          => 'management',
                'group_label'    => 'الإدارة والتحكم',
                'group_icon'     => 'fa-solid fa-folder-tree',
                'subgroup'       => 'system_admin',
                'subgroup_label' => 'النظام والمراقبة',
            ];
        }

        // الإعدادات (لكل المدراء)
        $items['settings'] = [
            'key'            => 'settings',
            'title'          => 'الإعدادات وتخصيص القائمة',
            'title_en'       => 'Settings',
            'route'          => 'settings.index',
            'active_pattern' => 'settings*',
            'icon'           => 'fa-solid fa-sliders',
            'group'          => 'management',
            'group_label'    => 'الإدارة والتحكم',
            'group_icon'     => 'fa-solid fa-sliders',
            'subgroup'       => 'system_settings',
            'subgroup_label' => 'الإعدادات',
        ];

        return $items;
    }

    /**
     * جلب عناصر الناف بار مرتبة حسب ترتيب المستخدم المحفوظ في حسابه
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
