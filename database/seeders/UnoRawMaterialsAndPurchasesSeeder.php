<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Recipe;
use App\Models\User;

class UnoRawMaterialsAndPurchasesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. مسح أي خامات أو حركات أو فواتير مشتريات سابقة بأمان تام
        Schema::disableForeignKeyConstraints();
        Recipe::query()->update(['inventory_item_id' => null]);
        InventoryMovement::truncate();
        PurchaseInvoiceItem::truncate();
        PurchaseInvoice::truncate();
        InventoryItem::truncate();
        Schema::enableForeignKeyConstraints();

        // مستخدم النظام لتسجيل الحركات والفواتير
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'المدير العام',
                'email' => 'admin@cafe.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // 2. قائمة الفواتير الـ 13 المستخرجة بدقة متناهية من مستند المشتريات
        $invoicesData = [
            // ==========================================
            // فاتورة 1: هيثم - رقم 1291 (29 أغسطس 2026)
            // ==========================================
            [
                'invoice_number' => '1291',
                'supplier_name'  => 'هيثم',
                'invoice_date'   => '2026-08-29',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة توريد خامات ومستلزمات هيثم رقم 1291',
                'items'          => [
                    ['name' => 'بودر ايس شوكلت 10 ك', 'qty' => 1.0, 'unit_price' => 1750.00, 'unit' => 'عبوة', 'category' => 'بودرات ومشروبات باردة'],
                    ['name' => 'لتر سيرب بيكان دافنشي', 'qty' => 1.0, 'unit_price' => 350.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'بودر ايس فانيليا 10 ك', 'qty' => 1.0, 'unit_price' => 1350.00, 'unit' => 'عبوة', 'category' => 'بودرات ومشروبات باردة'],
                    ['name' => 'لتر كراش اوستريج اناناس', 'qty' => 2.0, 'unit_price' => 310.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر سيرب سويت اند ساور', 'qty' => 1.0, 'unit_price' => 400.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب موهيتو', 'qty' => 1.0, 'unit_price' => 460.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب ايرش', 'qty' => 1.0, 'unit_price' => 490.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر صولو كولا', 'qty' => 1.0, 'unit_price' => 180.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'جوانتي', 'qty' => 1.0, 'unit_price' => 220.00, 'unit' => 'علبة', 'category' => 'مستهلكات وتشغيل'],
                    ['name' => 'بيوريه باشون', 'qty' => 1.0, 'unit_price' => 940.00, 'unit' => 'عبوة', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'بيوريه كيوي', 'qty' => 1.0, 'unit_price' => 875.00, 'unit' => 'عبوة', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'بيوريه تفاح اخضر', 'qty' => 1.0, 'unit_price' => 875.00, 'unit' => 'عبوة', 'category' => 'بيوريهات وفواكه كراش'],
                ],
            ],

            // ==========================================
            // فاتورة 2: هيثم - رقم 1354 (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => '1354',
                'supplier_name'  => 'هيثم',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة مناديل ورقية هيثم رقم 1354',
                'items'          => [
                    ['name' => 'مناديل هاندي 18 علبه 550منديل', 'qty' => 1.0, 'unit_price' => 460.00, 'unit' => 'كرتونة', 'category' => 'مستهلكات وتشغيل'],
                    ['name' => 'مناديل سي فولد 20 باكت', 'qty' => 1.0, 'unit_price' => 300.00, 'unit' => 'كرتونة', 'category' => 'مستهلكات وتشغيل'],
                ],
            ],

            // ==========================================
            // فاتورة 3: العجواني العطار - رقم 7320 (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => '7320',
                'supplier_name'  => 'العجواني العطار',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 97.24,
                'notes'          => 'فاتورة عطارة ومستلزمات حلويات - العجواني العطار رقم 7320 (خصم تسوية: 97.24 ج.م)',
                'items'          => [
                    ['name' => 'دقيق النجار حلويات', 'qty' => 5.0, 'unit_price' => 25.00, 'unit' => 'كجم', 'category' => 'خامات ومخبوزات'],
                    ['name' => 'عين جمل مقشر', 'qty' => 0.252, 'unit_price' => 600.00, 'unit' => 'كجم', 'category' => 'مكسرات وعطارة'],
                    ['name' => 'لوز مقشر', 'qty' => 0.250, 'unit_price' => 650.00, 'unit' => 'كجم', 'category' => 'مكسرات وعطارة'],
                    ['name' => 'كاجو جامبو', 'qty' => 0.254, 'unit_price' => 750.00, 'unit' => 'كجم', 'category' => 'مكسرات وعطارة'],
                    ['name' => 'بندق احمر', 'qty' => 0.246, 'unit_price' => 1000.00, 'unit' => 'كجم', 'category' => 'مكسرات وعطارة'],
                    ['name' => 'اعشاب رويال', 'qty' => 6.0, 'unit_price' => 92.00, 'unit' => 'علبة', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'شاي احمد تي اخضر 250 جم', 'qty' => 2.0, 'unit_price' => 145.00, 'unit' => 'عبوة', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'سكر سويتال 75 كيس', 'qty' => 1.0, 'unit_price' => 85.00, 'unit' => 'عبوة', 'category' => 'خامات ومشروبات'],
                    ['name' => 'فيبا 2ك', 'qty' => 1.0, 'unit_price' => 90.00, 'unit' => 'جركن', 'category' => 'نظافة وتشغيل'],
                    ['name' => 'سلك جامبو', 'qty' => 1.0, 'unit_price' => 15.00, 'unit' => 'قطعة', 'category' => 'نظافة وتشغيل'],
                    ['name' => 'ذكية القرش 2قطعه', 'qty' => 1.0, 'unit_price' => 20.00, 'unit' => 'باكت', 'category' => 'نظافة وتشغيل'],
                    ['name' => 'اسكوتش', 'qty' => 1.0, 'unit_price' => 15.00, 'unit' => 'قطعة', 'category' => 'نظافة وتشغيل'],
                    ['name' => 'مياه معدنيه', 'qty' => 1.0, 'unit_price' => 6.00, 'unit' => 'زجاجة', 'category' => 'مشروبات معبأة'],
                    ['name' => 'زبده فيرن 1كيلو', 'qty' => 2.0, 'unit_price' => 150.00, 'unit' => 'كجم', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'كيك شيف ظرف', 'qty' => 1.0, 'unit_price' => 130.00, 'unit' => 'عبوة', 'category' => 'خامات ومخبوزات'],
                    ['name' => 'سكر بني 600 جرام', 'qty' => 2.0, 'unit_price' => 70.00, 'unit' => 'كيس', 'category' => 'خامات ومشروبات'],
                    ['name' => 'عسل شاهين', 'qty' => 2.0, 'unit_price' => 120.00, 'unit' => 'برطمان', 'category' => 'خامات ومشروبات'],
                    ['name' => 'كمبوت اناناس', 'qty' => 2.0, 'unit_price' => 135.00, 'unit' => 'علبة', 'category' => 'فواكه ومأكولات'],
                    ['name' => 'عسل اسود سايب', 'qty' => 1.554, 'unit_price' => 50.00, 'unit' => 'كجم', 'category' => 'خامات ومشروبات'],
                    ['name' => 'لبن بودره كامل الدسم', 'qty' => 0.626, 'unit_price' => 320.00, 'unit' => 'كجم', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'كريم شانتيه', 'qty' => 1.002, 'unit_price' => 360.00, 'unit' => 'كجم', 'category' => 'خامات ومخبوزات'],
                    ['name' => 'قرنفل حصا', 'qty' => 0.254, 'unit_price' => 700.00, 'unit' => 'كجم', 'category' => 'مكسرات وعطارة'],
                    ['name' => 'قرفه سيجار', 'qty' => 0.250, 'unit_price' => 450.00, 'unit' => 'كجم', 'category' => 'مكسرات وعطارة'],
                    ['name' => 'زيت كريستال', 'qty' => 1.0, 'unit_price' => 530.00, 'unit' => 'عبوة', 'category' => 'خامات ومأكولات'],
                    ['name' => 'كيس سكر', 'qty' => 20.0, 'unit_price' => 23.00, 'unit' => 'كيس', 'category' => 'خامات ومشروبات'],
                ],
            ],

            // ==========================================
            // فاتورة 4: شركة الريس - ابو زياد الريس (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => 'INV-RAYES-01',
                'supplier_name'  => 'ابو زياد الريس',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة بن وشاي - شركة الريس',
                'items'          => [
                    ['name' => 'بن ساده', 'qty' => 1.0, 'unit_price' => 540.00, 'unit' => 'كجم', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'بن محوج', 'qty' => 1.0, 'unit_price' => 540.00, 'unit' => 'كجم', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'شاي احمد 100 فتله', 'qty' => 2.0, 'unit_price' => 135.00, 'unit' => 'علبة', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'شاي احمد فواكه', 'qty' => 3.0, 'unit_price' => 80.00, 'unit' => 'علبة', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'نسكافيه بلاك', 'qty' => 2.0, 'unit_price' => 100.00, 'unit' => 'برطمان', 'category' => 'بن وشاي ومشروبات ساخنة'],
                ],
            ],

            // ==========================================
            // فاتورة 5: عطار (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => 'INV-ATTAR-01',
                'supplier_name'  => 'عطار',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة خامات وتزيين حلويات - عطار',
                'items'          => [
                    ['name' => 'ورق دهب', 'qty' => 1.0, 'unit_price' => 20.00, 'unit' => 'دفتر', 'category' => 'مستلزمات حلويات وتزيين'],
                    ['name' => 'ورق فضي', 'qty' => 1.0, 'unit_price' => 45.00, 'unit' => 'دفتر', 'category' => 'مستلزمات حلويات وتزيين'],
                    ['name' => 'علبه اوريو', 'qty' => 3.0, 'unit_price' => 125.00, 'unit' => 'علبة', 'category' => 'مأكولات وحلويات'],
                    ['name' => 'رولس مجروش', 'qty' => 1.0, 'unit_price' => 180.00, 'unit' => 'عبوة', 'category' => 'مأكولات وحلويات'],
                    ['name' => 'كامينا فانيليا', 'qty' => 1.0, 'unit_price' => 590.00, 'unit' => 'عبوة', 'category' => 'خامات ومشروبات'],
                    ['name' => 'كيس مارشميلو', 'qty' => 5.0, 'unit_price' => 50.00, 'unit' => 'كيس', 'category' => 'مأكولات وحلويات'],
                ],
            ],

            // ==========================================
            // فاتورة 6: حاتم زينهم - خضروات وفواكه العيلة (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => 'INV-VEG-01',
                'supplier_name'  => 'حاتم زينهم',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة فواكه وخضروات طازجة - حاتم زينهم (خضروات وفواكه العيلة)',
                'items'          => [
                    ['name' => 'نعناع', 'qty' => 2.0, 'unit_price' => 5.00, 'unit' => 'حزمة', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'زعتر', 'qty' => 1.0, 'unit_price' => 15.00, 'unit' => 'حزمة', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'ليمون', 'qty' => 0.855, 'unit_price' => 40.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'كيوي', 'qty' => 0.405, 'unit_price' => 320.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'افوكادو مستورد', 'qty' => 1.200, 'unit_price' => 250.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'مشمش احمر', 'qty' => 0.455, 'unit_price' => 180.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'رمان', 'qty' => 2.070, 'unit_price' => 75.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'برتقال بلدي', 'qty' => 1.405, 'unit_price' => 40.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'برقوق', 'qty' => 0.505, 'unit_price' => 150.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'تين برشومي', 'qty' => 0.465, 'unit_price' => 95.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'موز', 'qty' => 1.390, 'unit_price' => 50.00, 'unit' => 'كجم', 'category' => 'فواكه وخضروات طازجة'],
                ],
            ],

            // ==========================================
            // فاتورة 7: القواس - رقم 112218 (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => '112218',
                'supplier_name'  => 'القواس',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة منتجات ألبان ومشروبات ومياه - رقم 112218',
                'items'          => [
                    ['name' => 'لمار حليب خالي الدسم (كرتونة)', 'qty' => 2.0, 'unit_price' => 564.00, 'unit' => 'كرتونة', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'لمار حليب كامل الدسم', 'qty' => 1.0, 'unit_price' => 47.00, 'unit' => 'لتر', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'تي بريك شاي كرك', 'qty' => 3.0, 'unit_price' => 80.00, 'unit' => 'عبوة', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'لمار حليب خالي الدسم (عبوة)', 'qty' => 2.0, 'unit_price' => 96.00, 'unit' => 'عبوة', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'جهينه حليب خالي الدسم', 'qty' => 2.0, 'unit_price' => 50.00, 'unit' => 'لتر', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'الوادي بيض احمر 30 ق', 'qty' => 1.0, 'unit_price' => 105.00, 'unit' => 'طبق', 'category' => 'خامات ومأكولات'],
                    ['name' => 'مانا حليب مكثف محلى', 'qty' => 5.0, 'unit_price' => 110.00, 'unit' => 'علبة', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'سفن اب كانز ك 24ق', 'qty' => 2.0, 'unit_price' => 310.00, 'unit' => 'كرتونة', 'category' => 'مشروبات معبأة'],
                    ['name' => 'ريدبول', 'qty' => 1.0, 'unit_price' => 1272.00, 'unit' => 'كرتونة', 'category' => 'مشروبات معبأة'],
                    ['name' => 'نستله مياه ك 20ق', 'qty' => 5.0, 'unit_price' => 115.00, 'unit' => 'كرتونة', 'category' => 'مشروبات معبأة'],
                    ['name' => 'اوه مياه 20 ك ق', 'qty' => 5.0, 'unit_price' => 95.00, 'unit' => 'كرتونة', 'category' => 'مشروبات معبأة'],
                ],
            ],

            // ==========================================
            // فاتورة 8: عصير زين (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => 'INV-ZAIN-01',
                'supplier_name'  => 'عصير زين',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة عصائر طبيعية مركزة - عصير زين',
                'items'          => [
                    ['name' => 'لتر فراولة', 'qty' => 4.0, 'unit_price' => 220.00, 'unit' => 'لتر', 'category' => 'فواكه وخضروات طازجة'],
                    ['name' => 'لتر مانجو عادة', 'qty' => 4.0, 'unit_price' => 350.00, 'unit' => 'لتر', 'category' => 'فواكه وخضروات طازجة'],
                ],
            ],

            // ==========================================
            // فاتورة 9: ماتشا (6 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => 'INV-MATCHA-01',
                'supplier_name'  => 'مورد ماتشا',
                'invoice_date'   => '2026-09-06',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة ماتشا يابانية فاخرة',
                'items'          => [
                    ['name' => 'ماتشا', 'qty' => 1.0, 'unit_price' => 1200.00, 'unit' => 'عبوة', 'category' => 'بودرات ومشروبات باردة'],
                ],
            ],

            // ==========================================
            // فاتورة 10: هيثم - رقم 1598 (28 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => '1598',
                'supplier_name'  => 'هيثم',
                'invoice_date'   => '2026-09-28',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة سيربات وشوكولاتة ومستلزمات - رقم 1598',
                'items'          => [
                    ['name' => 'بيكر كندر 3ك', 'qty' => 1.0, 'unit_price' => 1570.00, 'unit' => 'جردل 3 كجم', 'category' => 'سيربات وصوصات'],
                    ['name' => 'بيكر نوتيلا بني 4.50ك', 'qty' => 1.0, 'unit_price' => 920.00, 'unit' => 'جردل 4.5 كجم', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر كراش اوستريج باشن', 'qty' => 1.0, 'unit_price' => 320.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر كراش اوستريج اناناس', 'qty' => 1.0, 'unit_price' => 310.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر كراش اوستريج فراوله', 'qty' => 1.0, 'unit_price' => 300.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر سيرب بطيخ', 'qty' => 1.0, 'unit_price' => 490.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب كلاودي', 'qty' => 1.0, 'unit_price' => 425.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'كوستر', 'qty' => 2.0, 'unit_price' => 20.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتشغيل'],
                ],
            ],

            // ==========================================
            // فاتورة 11: القواس - رقم 87660 (28 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => '87660',
                'supplier_name'  => 'القواس',
                'invoice_date'   => '2026-09-28',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة مشروبات وحليب ومياه - رقم 87660',
                'items'          => [
                    ['name' => 'ريدبول توت', 'qty' => 2.0, 'unit_price' => 1272.00, 'unit' => 'كرتونة', 'category' => 'مشروبات معبأة'],
                    ['name' => 'سفن اب 24 ق', 'qty' => 2.0, 'unit_price' => 310.00, 'unit' => 'كرتونة', 'category' => 'مشروبات معبأة'],
                    ['name' => 'لمار حليب كامل الدسم (كرتونة)', 'qty' => 2.0, 'unit_price' => 564.00, 'unit' => 'كرتونة', 'category' => 'ألبان ومشتقاتها'],
                    ['name' => 'بركه مياه 24 ق', 'qty' => 5.0, 'unit_price' => 104.00, 'unit' => 'كرتونة', 'category' => 'مشروبات معبأة'],
                ],
            ],

            // ==========================================
            // فاتورة 12: القواس - رقم 87663 (28 سبتمبر 2026)
            // ==========================================
            [
                'invoice_number' => '87663',
                'supplier_name'  => 'القواس',
                'invoice_date'   => '2026-09-28',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة بسكويت اوريو - رقم 87663',
                'items'          => [
                    ['name' => 'اوريو بسكوت فانيليا 5+1', 'qty' => 2.0, 'unit_price' => 110.00, 'unit' => 'علبة', 'category' => 'مأكولات وحلويات'],
                    ['name' => 'اوريو بسكوت فانيليا 2+1', 'qty' => 2.0, 'unit_price' => 55.00, 'unit' => 'علبة', 'category' => 'مأكولات وحلويات'],
                ],
            ],

            // ==========================================
            // فاتورة 13: مورد مستلزمات وخامات - 27 سبتمبر 2026
            // ==========================================
            [
                'invoice_number' => 'PUR-20260927-01',
                'supplier_name'  => 'مورد مستلزمات وخامات',
                'invoice_date'   => '2026-09-27',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'discount'       => 0.00,
                'notes'          => 'فاتورة توريد خامات ومستلزمات وأكواب - 27 سبتمبر',
                'items'          => [
                    ['name' => 'ريد فيلفيت شاسكو', 'qty' => 1.0, 'unit_price' => 220.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'سبريد بندق محمص شاسكو', 'qty' => 1.0, 'unit_price' => 260.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'كيندر شاسكو', 'qty' => 1.0, 'unit_price' => 170.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'وايت شاسكو', 'qty' => 1.0, 'unit_price' => 140.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'فستق شاسكو', 'qty' => 1.0, 'unit_price' => 450.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'بيكر لوتس 3كيلو', 'qty' => 1.0, 'unit_price' => 450.00, 'unit' => 'جردل 3 كجم', 'category' => 'سيربات وصوصات'],
                    ['name' => 'فرابيه فانيليا', 'qty' => 1.0, 'unit_price' => 310.00, 'unit' => 'كيس', 'category' => 'بودرات ومشروبات باردة'],
                    ['name' => 'فرابيه شوكلت', 'qty' => 1.0, 'unit_price' => 390.00, 'unit' => 'كيس', 'category' => 'بودرات ومشروبات باردة'],
                    ['name' => 'اسبريسو ازرق سوبريم', 'qty' => 1.0, 'unit_price' => 775.00, 'unit' => 'كجم', 'category' => 'بن وشاي ومشروبات ساخنة'],
                    ['name' => 'بوبا', 'qty' => 1.0, 'unit_price' => 350.00, 'unit' => 'عبوة', 'category' => 'بودرات ومشروبات باردة'],
                    ['name' => 'لتر كراش اوستريج باشن', 'qty' => 1.0, 'unit_price' => 310.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر كراش اوستريج جوز هند', 'qty' => 1.0, 'unit_price' => 310.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر كراش اوستريج مانجو', 'qty' => 1.0, 'unit_price' => 300.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر كراش اوستريج فراوله', 'qty' => 1.0, 'unit_price' => 280.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر كراش اوستريج خوخ', 'qty' => 1.0, 'unit_price' => 290.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'لتر كراش اوستريج توت ازرق', 'qty' => 1.0, 'unit_price' => 330.00, 'unit' => 'لتر', 'category' => 'بيوريهات وفواكه كراش'],
                    ['name' => 'صولو كراميل جامبو', 'qty' => 1.0, 'unit_price' => 400.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'صولو وايت', 'qty' => 1.0, 'unit_price' => 425.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'صولو شوكت جامبو', 'qty' => 1.0, 'unit_price' => 400.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'صولو كراميل مملح جامبو', 'qty' => 1.0, 'unit_price' => 460.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب بطيخ', 'qty' => 1.0, 'unit_price' => 490.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب رمان', 'qty' => 1.0, 'unit_price' => 490.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب ليمون', 'qty' => 1.0, 'unit_price' => 490.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب كراميل', 'qty' => 1.0, 'unit_price' => 460.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'ملي سيرب كوكيز', 'qty' => 1.0, 'unit_price' => 475.00, 'unit' => 'عبوة', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب فانيليا', 'qty' => 1.0, 'unit_price' => 510.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب بندق', 'qty' => 1.0, 'unit_price' => 510.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'لتر سيرب بلو كرواسون', 'qty' => 1.0, 'unit_price' => 510.00, 'unit' => 'لتر', 'category' => 'سيربات وصوصات'],
                    ['name' => 'غطاء اكوا شاي', 'qty' => 4.0, 'unit_price' => 50.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'كوبايات كابتشينو 12', 'qty' => 2.0, 'unit_price' => 65.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'كوبايات شاي 8', 'qty' => 4.0, 'unit_price' => 55.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'غطاء يوشيب اكوا اسود', 'qty' => 2.0, 'unit_price' => 50.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'غطاء يوشيب اكوا شفاف', 'qty' => 2.0, 'unit_price' => 50.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'كوبايات يوشيب 16', 'qty' => 4.0, 'unit_price' => 90.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'شاليموه مغلف 6 100 قطعه', 'qty' => 2.0, 'unit_price' => 30.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'معلقه كبير كيس', 'qty' => 2.0, 'unit_price' => 35.00, 'unit' => 'كيس', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'سكينه كيس', 'qty' => 2.0, 'unit_price' => 35.00, 'unit' => 'كيس', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'شوكه كيس', 'qty' => 2.0, 'unit_price' => 30.00, 'unit' => 'كيس', 'category' => 'مستهلكات وتعبئة'],
                    ['name' => 'سكر', 'qty' => 4.0, 'unit_price' => 50.00, 'unit' => 'كيس', 'category' => 'خامات ومشروبات'],
                    ['name' => 'استيلر خشب', 'qty' => 2.0, 'unit_price' => 135.00, 'unit' => 'باكت', 'category' => 'مستهلكات وتعبئة'],
                ],
            ],
        ];

        // 3. إدخال وتوريد كل فاتورة وتحديث أرصدة المخزن وحركات التوريد
        $codeCounter = 1;

        foreach ($invoicesData as $invData) {
            $totalAmount = 0.0;
            foreach ($invData['items'] as $item) {
                $totalAmount += round((float)$item['qty'] * (float)$item['unit_price'], 2);
            }

            $discount = (float) $invData['discount'];
            $netAmount = round($totalAmount - $discount, 2);

            $purchaseInvoice = PurchaseInvoice::create([
                'invoice_number'   => $invData['invoice_number'],
                'supplier_name'    => $invData['supplier_name'],
                'invoice_date'     => $invData['invoice_date'],
                'total_amount'     => $totalAmount,
                'discount'         => $discount,
                'tax'              => 0.00,
                'net_amount'       => $netAmount,
                'paid_amount'      => $netAmount,
                'remaining_amount' => 0.00,
                'payment_method'   => $invData['payment_method'],
                'payment_status'   => $invData['payment_status'],
                'status'           => 'completed',
                'notes'            => $invData['notes'],
                'user_id'          => $user->id,
            ]);

            foreach ($invData['items'] as $item) {
                $qty = (float) $item['qty'];
                $price = (float) $item['unit_price'];
                $subtotal = round($qty * $price, 2);

                // البحث عن الخامة أو إنشائها برمز تسلسلي منظم
                $invItem = InventoryItem::where('name', $item['name'])->first();

                if (!$invItem) {
                    $itemCode = sprintf('ITM-%03d', $codeCounter++);
                    $invItem = InventoryItem::create([
                        'code'          => $itemCode,
                        'name'          => $item['name'],
                        'category'      => $item['category'],
                        'quantity'      => $qty,
                        'unit'          => $item['unit'],
                        'unit_price'    => $price,
                        'reorder_level' => 5.00,
                    ]);
                } else {
                    $invItem->increment('quantity', $qty);
                    $invItem->update([
                        'unit_price' => $price,
                        'unit'       => $item['unit'],
                        'category'   => $item['category'],
                    ]);
                }

                // تسجيل صنف الفاتورة
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'inventory_item_id'   => $invItem->id,
                    'item_name'           => $item['name'],
                    'unit'                => $item['unit'],
                    'quantity'            => $qty,
                    'unit_price'          => $price,
                    'subtotal'            => $subtotal,
                ]);

                // تسجيل حركة التوريد بالمخزن
                InventoryMovement::create([
                    'inventory_item_id' => $invItem->id,
                    'type'              => 'restock',
                    'quantity'          => $qty,
                    'balance_after'     => $invItem->quantity,
                    'user_id'           => $user->id,
                ]);
            }
        }
    }
}
