<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Menu;
use App\Models\User;
use Database\Seeders\UnoMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnoMenuAndDailyTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_uno_menu_seeder_cleans_old_data_and_seeds_uno_cafe_menu(): void
    {
        // إضافة بيانات تجريبية وهمية للتأكد من حذفها
        $oldCat = Category::create(['name' => 'Old Dummy Category']);
        Menu::create([
            'category_id' => $oldCat->id,
            'name' => 'Old Dummy Product',
            'price' => 999.00,
            'is_available' => true,
        ]);

        $this->assertEquals(1, Category::where('name', 'Old Dummy Category')->count());

        // تشغيل السيدر الجديد
        $this->seed(UnoMenuSeeder::class);

        // التأكد من حذف البيانات القديمة تماماً
        $this->assertEquals(0, Category::where('name', 'Old Dummy Category')->count());
        $this->assertEquals(0, Menu::where('name', 'Old Dummy Product')->count());

        // التأكد من إدخال الأقسام الـ 17 الحقيقية لـ UNO Cafe
        $this->assertGreaterThanOrEqual(17, Category::count());
        $this->assertTrue(Category::where('name', 'Break Fast')->exists());
        $this->assertTrue(Category::where('name', 'Iced Coffee')->exists());
        $this->assertTrue(Category::where('name', 'V 60')->exists());
        $this->assertTrue(Category::where('name', 'Hot Drinks')->exists());
        $this->assertTrue(Category::where('name', 'Milk Shake')->exists());
        $this->assertTrue(Category::where('name', 'Dessert')->exists());
        $this->assertTrue(Category::where('name', 'Fruit Salad')->exists());

        // التأكد من الأسعار الحقيقية لبعض الأصناف من الـ PDF
        $bagel = Menu::where('name', 'Bagel')->first();
        $this->assertNotNull($bagel);
        $this->assertEquals(60.00, (float) $bagel->price);

        $yemeni = Menu::where('name', 'Yemeni')->first();
        $this->assertNotNull($yemeni);
        $this->assertEquals(180.00, (float) $yemeni->price);

        $cappuccino = Menu::where('name', 'Cappuccino')->first();
        $this->assertNotNull($cappuccino);
        $this->assertEquals(120.00, (float) $cappuccino->price);

        $specialFruitSalad = Menu::where('name', 'Special Fruit salad')->first();
        $this->assertNotNull($specialFruitSalad);
        $this->assertEquals(600.00, (float) $specialFruitSalad->price);
    }

    public function test_menu_index_page_displays_grouped_categories_and_pdf_button(): void
    {
        $this->seed(UnoMenuSeeder::class);

        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get(route('menu.index'));

        $response->assertOk();
        $response->assertSee('قائمة أصناف ومنتجات UNO Cafe');
        $response->assertSee('عرض منيو UNO PDF الأصلي');
        $response->assertSee('Break Fast');
        $response->assertSee('Bagel');
        $response->assertSee('Iced Coffee');
        $response->assertSee('Classic Latte');
    }

    public function test_menu_pdf_route_streams_actual_pdf(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->get(route('menu.pdf'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_daily_inventory_tracking_computes_exact_kpis_and_lists_items(): void
    {
        $this->seed(UnoMenuSeeder::class);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('inventory.daily_tracking'));

        $response->assertOk();
        $response->assertSee('تقرير متابعة المخزون والتكاليف');
        $response->assertSee('ملخص الأرصدة والقيم المالية للأصناف المتاحة');
        $response->assertSee('متابعة_المخزون_والتكاليف.xlsx');
        $response->assertSee('إجمالي قيمة المخزون');
        $response->assertSee('الأصناف المتاحة (عدد)');
        $response->assertSee('أصناف تحتاج إعادة طلب');
        $response->assertSee('ملاحظات الإدارة وتوصيات الشراء');
        $response->assertSee('نواقص المخزون:');
        $response->assertSee('أرصدة منخفضة:');
    }

    public function test_inventory_tracking_csv_export_works_properly(): void
    {
        $this->seed(UnoMenuSeeder::class);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('inventory.export_tracking'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('كود الصنف', $response->streamedContent());
        $this->assertStringContainsString('اسم الصنف / البيان', $response->streamedContent());
        $this->assertStringContainsString('القيمة الإجمالية', $response->streamedContent());
    }
}
