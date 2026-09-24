<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. تحديث جدول فواتير الشراء الأساسي
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // جعل الحقول القديمة قابلة للقيم الفارغة إن كانت موجودة
            if (Schema::hasColumn('purchase_invoices', 'inventory_item_id')) {
                $table->foreignId('inventory_item_id')->nullable()->change();
            }
            if (Schema::hasColumn('purchase_invoices', 'quantity')) {
                $table->decimal('quantity', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('purchase_invoices', 'cost')) {
                $table->decimal('cost', 10, 2)->nullable()->change();
            }

            // إضافة الحقول الجديدة للفاتورة
            if (!Schema::hasColumn('purchase_invoices', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('purchase_invoices', 'invoice_date')) {
                $table->date('invoice_date')->nullable()->after('supplier_name');
            }
            if (!Schema::hasColumn('purchase_invoices', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('invoice_date');
            }
            if (!Schema::hasColumn('purchase_invoices', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'tax')) {
                $table->decimal('tax', 10, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'net_amount')) {
                $table->decimal('net_amount', 12, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('purchase_invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('net_amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'remaining_amount')) {
                $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('remaining_amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'payment_status')) {
                $table->string('payment_status')->default('paid')->after('payment_method');
            }
            if (!Schema::hasColumn('purchase_invoices', 'status')) {
                $table->string('status')->default('completed')->after('payment_status');
            }
            if (!Schema::hasColumn('purchase_invoices', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });

        // 2. إنشاء جدول أصناف فواتير الشراء المتعددة
        if (!Schema::hasTable('purchase_invoice_items')) {
            Schema::create('purchase_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->onDelete('cascade');
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
                $table->string('item_name');
                $table->string('unit')->default('قطعة');
                $table->decimal('quantity', 10, 2);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $columns = [
                'invoice_number',
                'invoice_date',
                'total_amount',
                'discount',
                'tax',
                'net_amount',
                'paid_amount',
                'remaining_amount',
                'payment_method',
                'payment_status',
                'status',
                'notes',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('purchase_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
