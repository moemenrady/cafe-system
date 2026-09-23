<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use App\Models\Invoice;
use App\Models\PrinterJob;
use App\Services\OrderService;

echo "=======================================================\n";
echo "    FULL LIVE SCENARIOS END-TO-END VERIFICATION       \n";
echo "=======================================================\n\n";

$orderService = app(OrderService::class);

// Make sure we have 2 menu items totaling 100 EGP or find two items
$item1 = Menu::where('is_available', true)->first();
$item2 = Menu::where('is_available', true)->skip(1)->first();

if (!$item1 || !$item2) {
    die("Error: Insufficient menu items in database.\n");
}

$price1 = (float) $item1->price;
$price2 = (float) $item2->price;
echo "Using Menu Items: [{$item1->name}: {$price1} EGP], [{$item2->name}: {$price2} EGP]\n\n";

// -------------------------------------------------------------
// SCENARIO A: DINE-IN
// -------------------------------------------------------------
echo ">>> SCENARIO A: DINE-IN (Table 1)\n";
$table = Table::firstOrCreate(
    ['name' => 'طاولة 1'],
    ['capacity' => 4, 'is_active' => true]
);

// Verify table is available initially
if ($table->isOccupied()) {
    echo "Releasing existing order on Table 1 for clean test...\n";
    foreach ($table->activeOrders as $ao) {
        $ao->update(['status' => 'completed', 'payment_status' => 'paid', 'closed_at' => now()]);
    }
}
$table->refresh();
echo "Table 1 initial isOccupied: " . ($table->isOccupied() ? "YES" : "NO (Available)") . "\n";

// Open table & create order
$orderA = $orderService->createOrder([
    'type' => 'dine_in',
    'table_id' => $table->id,
    'customer_name' => 'عميل الصالة',
    'customer_phone' => '01111111111',
    'items' => [
        ['menu_id' => $item1->id, 'quantity' => 2],
        ['menu_id' => $item2->id, 'quantity' => 1],
    ],
]);

$expectedSubtotalA = round(($price1 * 2) + ($price2 * 1), 2);
$expectedVatA = round($expectedSubtotalA * 0.14, 2);
$expectedTotalA = round($expectedSubtotalA + $expectedVatA, 2);

echo "Order A Created: {$orderA->order_number} | Status: {$orderA->status} | Payment: {$orderA->payment_status}\n";
echo "Calculated: Subtotal = {$orderA->subtotal} (Expected {$expectedSubtotalA}), VAT (14%) = {$orderA->vat} (Expected {$expectedVatA}), Total = {$orderA->total} (Expected {$expectedTotalA})\n";

if ((float)$orderA->vat !== $expectedVatA || (float)$orderA->total !== $expectedTotalA) {
    die("FATAL ERROR: Scenario A Dine-in VAT mismatch!\n");
}
echo "✓ Dine-in 14% VAT Verified in DB\n";

// Verify Table is now Occupied
$table->refresh();
echo "Table 1 isOccupied: " . ($table->isOccupied() ? "YES (Occupied)" : "NO") . "\n";
if (!$table->isOccupied()) {
    die("FATAL ERROR: Table should be occupied!\n");
}

// Print Invoice (Pre-bill)
echo "Printing Pre-Bill Invoice...\n";
$invA1 = $orderService->printInvoice($orderA);
echo "Invoice Created: {$invA1->invoice_number} | Subtotal: {$invA1->subtotal} | VAT: {$invA1->vat} | Total: {$invA1->total}\n";

// Verify Idempotency - Print Invoice Again
$invA2 = $orderService->printInvoice($orderA);
echo "Second Print Invoice: {$invA2->invoice_number} (Matches: " . ($invA1->id === $invA2->id ? "YES" : "NO") . ")\n";
if ($invA1->id !== $invA2->id) {
    die("FATAL ERROR: Duplicate invoice generated!\n");
}

// Verify Table STILL Occupied
$table->refresh();
echo "Table 1 isOccupied after pre-bill print: " . ($table->isOccupied() ? "YES (Still Occupied)" : "NO") . "\n";
if (!$table->isOccupied() || $orderA->fresh()->status !== 'open') {
    die("FATAL ERROR: Printing invoice closed table prematurely!\n");
}

// Close Table
echo "Closing Table 1...\n";
$closedOrderA = $orderService->closeTable($orderA, [
    'payment_method' => 'cash',
]);
echo "Closed Order A: Status = {$closedOrderA->status}, Payment = {$closedOrderA->payment_status}, Closed At = {$closedOrderA->closed_at}\n";

$table->refresh();
echo "Table 1 isOccupied after close: " . ($table->isOccupied() ? "YES" : "NO (Available)") . "\n";
if ($table->isOccupied()) {
    die("FATAL ERROR: Table 1 should be available after closeTable!\n");
}
echo "✓ Scenario A Completed Successfully!\n\n";

// -------------------------------------------------------------
// SCENARIO B: TAKEAWAY
// -------------------------------------------------------------
echo ">>> SCENARIO B: TAKEAWAY\n";
$orderB = $orderService->createOrder([
    'type' => 'takeaway',
    'customer_name' => 'عميل التيك أواي',
    'customer_phone' => '01222222222',
    'payment_method' => 'cash',
    'items' => [
        ['menu_id' => $item1->id, 'quantity' => 1],
        ['menu_id' => $item2->id, 'quantity' => 1],
    ],
]);

$expectedSubtotalB = round(($price1 * 1) + ($price2 * 1), 2);
$expectedVatB = 0.00;
$expectedTotalB = $expectedSubtotalB;

echo "Order B Created: {$orderB->order_number} | Type: {$orderB->type} | Status: {$orderB->status}\n";
echo "Calculated: Subtotal = {$orderB->subtotal}, VAT (0%) = {$orderB->vat} (Expected 0.00), Total = {$orderB->total} (Expected {$expectedTotalB})\n";

if ((float)$orderB->vat !== 0.00 || (float)$orderB->total !== $expectedTotalB) {
    die("FATAL ERROR: Scenario B Takeaway VAT is not 0%!\n");
}

$invB = $orderB->invoice;
echo "Invoice B: {$invB->invoice_number} | Subtotal = {$invB->subtotal} | VAT = {$invB->vat} | Total = {$invB->total}\n";
if ((float)$invB->vat !== 0.00 || (float)$invB->total !== $expectedTotalB) {
    die("FATAL ERROR: Scenario B Invoice VAT is not 0%!\n");
}

$lastJobB = PrinterJob::where('order_id', $orderB->id)->where('type', 'customer')->latest('id')->first();
echo "Print Job B Payload Tax Rate: {$lastJobB->payload['tax_rate']}%, Tax Amount: {$lastJobB->payload['tax_amount']} EGP\n";
if ($lastJobB->payload['tax_rate'] !== 0 || (float)$lastJobB->payload['tax_amount'] !== 0.00) {
    die("FATAL ERROR: Scenario B Print Job tax is not 0%!\n");
}
echo "✓ Scenario B Takeaway 0% VAT Verified in Order, Invoice, and Print Job!\n\n";

// -------------------------------------------------------------
// SCENARIO C: DELIVERY
// -------------------------------------------------------------
echo ">>> SCENARIO C: DELIVERY\n";
$orderC = $orderService->createOrder([
    'type' => 'delivery',
    'phone' => '01099999999',
    'delivery_address' => 'شارع التحرير، الدقي، الجيزة',
    'delivery_person' => 'كابتن أحمد',
    'items' => [
        ['menu_id' => $item1->id, 'quantity' => 1],
    ],
]);

$expectedSubtotalC = round($price1 * 1, 2);
$expectedVatC = 0.00;
$expectedTotalC = $expectedSubtotalC;

echo "Order C Created: {$orderC->order_number} | Type: {$orderC->type} | Status: {$orderC->status}\n";
echo "Calculated: Subtotal = {$orderC->subtotal}, VAT (0%) = {$orderC->vat} (Expected 0.00), Total = {$orderC->total} (Expected {$expectedTotalC})\n";

if ((float)$orderC->vat !== 0.00 || (float)$orderC->total !== $expectedTotalC) {
    die("FATAL ERROR: Scenario C Delivery VAT is not 0%!\n");
}
echo "✓ Scenario C Delivery 0% VAT Verified in Order!\n\n";

echo "=======================================================\n";
echo "     ALL 3 SCENARIOS VERIFIED SUCCESSFULLY 100%!       \n";
echo "=======================================================\n";
