# ☕ Café POS & Management System — Production Audit & Technical Roadmap

This roadmap documents all architectural, financial, security, operational, and performance issues identified during the backend audit. Every issue includes its exact location, technical root cause, real-world operational risk, and the required implementation blueprint.

Items are categorized strictly by urgency:
- **P0 — Showstoppers & Integrity Flaws:** Must be resolved before processing live transactions.
- **P1 — Operational Resilience & Core Workflows:** Necessary for smooth café operations, staff accountability, and hardware reliability.
- **P2 — Performance & Maintainability:** Optimization, database indexing, and code refactoring.

---

## 📋 Master Execution Checklist

### 🔴 P0: Showstoppers, Concurrency & Data/Financial Integrity
- [x] **[P0-01]** Atomic Document Sequence Generator (Order & Invoice Number Collisions)
- [x] **[P0-02]** Pessimistic Concurrency Lock on Table Checkout (Double-Spend / Duplicate Invoices)
- [x] **[P0-03]** Pessimistic Row Locking (`lockForUpdate`) on Inventory Decrement
- [x] **[P0-04]** Financial Calculation Precision & Discount/Tax/Service Charge Preservation
- [x] **[P0-05]** Authorization Route Shadowing on Invoice Deletion (`routes/sales.php`)
- [x] **[P0-06]** Thermal Printer Agent Database Schema Mismatch (`printer_jobs` Table)
- [x] **[P0-07]** Database Enum Mismatch on Invoice Cancellation (`sale_cancel` in `inventory_movements`)
- [x] **[P0-08]** Registration Role Enum Crash & Public `/demo` Employee Data Leak
- [x] **[P0-09]** Mass Assignment Vulnerabilities (`Invoice` & `InventoryItem`) & Unauthenticated API Routes

---

### 🟡 P1: Café Operations, Hardware Resilience & Order Lifecycle
- [x] **[P1-01]** Table Lifecycle, Multi-Order Conflicts, & Occupancy Synchronization Gap
- [x] **[P1-02]** Asynchronous Print Job Dispatching (Replacing Blocking `ShouldBroadcastNow`)
- [x] **[P1-03]** Complete Shift & Cash Drawer Reconciliation Subsystem (Floats, Drops, Z-Reports)
- [x] **[P1-04]** POS Order FormRequest Validation & Manager Discount Overrides
- [x] **[P1-05]** Unified Order-to-Invoice Lifecycle & Bidirectional State Synchronization

---

### 🟢 P2: Performance Bottlenecks, Indexing & Code Separation
- [x] **[P2-01]** Eliminate N+1 Query Storm on POS Tables via `withExists()`
- [x] **[P2-02]** Add Composite Database Indexes for Orders, Invoices, and Inventory Movements
- [x] **[P2-03]** Refactor Fat `InvoiceController` into Dedicated Service Classes

---

# Detailed Issue Specifications & Remediation Blueprints

---

## 🔴 P0: Showstoppers, Concurrency & Data/Financial Integrity

---

### [P0-01] Atomic Document Sequence Generator (Order & Invoice Number Collisions)
- **Target Files:**
  - [`app/Services/OrderService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/OrderService.php#L122-L134) (`generateOrderNumber`)
  - [`app/Helpers/InvoiceNumberHelper.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Helpers/InvoiceNumberHelper.php#L9-L16) (`generate`)
- **Technical Details & Root Cause:**
  Both sequence generators fetch the last recorded ID or order number using non-atomic Eloquent queries (`Order::whereDate('created_at', $today)->latest('id')->first()` and `Invoice::latest('id')->first()`). Neither mechanism utilizes database row locking, mutex locks, or sequence tracking tables.
- **Impact & Risk:**
  During a rush hour where multiple cashier terminals create orders or complete checkouts simultaneously, two concurrent transactions read the same sequence snapshot and compute the exact same string (e.g., `ORD-20260923-0012` or `INV-20260923-00015`). Because both `orders.order_number` and `invoices.invoice_number` have unique database indexes, the second transaction fails with `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`. The cashier's register crashes with an unhandled 500 error.
- **Required Changes:**
  1. Create a migration for a `document_sequences` table with composite primary key `(type, date_key)` and an atomic `last_number` integer.
  2. Implement an atomic `DocumentSequenceService` that increments and retrieves numbers inside a `lockForUpdate()` transaction.
  3. Replace calls in `OrderService` and `InvoiceService`/`InvoiceNumberHelper` with the new atomic sequence service.

---

### [P0-02] Pessimistic Concurrency Lock on Table Checkout (Double-Spend / Duplicate Invoices)
- **Target Files:**
  - [`app/Services/OrderService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/OrderService.php#L34-L52) (`checkout`)
  - [`app/Http/Controllers/OrderController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/OrderController.php#L45-L72) (`checkout`)
- **Technical Details & Root Cause:**
  The `checkout(Order $order)` method receives an in-memory bound model and enters `DB::transaction`. It checks `if ($order->payment_status === 'paid')` against the memory object without querying `Order::where('id', $order->id)->lockForUpdate()->first()`.
- **Impact & Risk:**
  If a cashier double-taps the checkout button, or two cashiers attempt to close Table 3 concurrently, both requests pass the in-memory payment status check simultaneously. Both requests create an `Invoice`, both trigger duplicate print receipts, and both record duplicate revenue and inventory decrements.
- **Required Changes:**
  1. Re-query the target `Order` inside the database transaction using `lockForUpdate()`.
  2. Validate that the fresh database record is still `pending` and not `cancelled`.
  3. Return the generated `Invoice` and synchronize the table occupancy state atomically.

---

### [P0-03] Pessimistic Row Locking (`lockForUpdate`) on Inventory Decrement
- **Target Files:**
  - [`app/Services/InventoryService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/InventoryService.php#L12-L48) (`updateInventory`)
  - [`app/Http/Controllers/InvoiceController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/InvoiceController.php#L304-L327) (`store`)
  - [`app/Http/Controllers/InvoiceController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/InvoiceController.php#L131-L156) (`update`)
- **Technical Details & Root Cause:**
  `InventoryService` iterates order items and accesses `$recipe->inventoryItem` without locking. It compares `$inventoryItem->quantity < $neededQuantity` using stale read state, then issues `decrement()`. `balance_after` is logged by reading `$inventoryItem->fresh()->quantity`, which reads whatever intermediate state other concurrent transactions may have written.
- **Impact & Risk:**
  When multiple orders for items sharing ingredients (e.g., Espresso beans or milk) are placed simultaneously, inventory balances desynchronize, allow negative stock balances, and produce inconsistent historical balance snapshots in `inventory_movements`.
- **Required Changes:**
  1. Calculate aggregated ingredient quantities per order upfront.
  2. Sort ingredient IDs numerically prior to locking to prevent database deadlocks.
  3. Load `InventoryItem` records with `whereIn('id', $ids)->lockForUpdate()`.
  4. Perform the balance verification, decrement, and `InventoryMovement` logging with mathematically computed `balance_after` inside the locked transaction.

---

### [P0-04] Financial Calculation Precision & Discount/Tax/Service Charge Preservation
- **Target Files:**
  - [`app/Services/OrderItemService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/OrderItemService.php#L23-L49) (`createItems`)
  - [`app/Services/InvoiceService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/InvoiceService.php#L17-L26) (`createInvoice`)
  - [`app/Http/Controllers/InvoiceController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/InvoiceController.php#L166-L174) (`update`)
- **Technical Details & Root Cause:**
  1. In `OrderItemService.php`, lines 41-44 overwrite the order totals:
     `$order->update(['subtotal' => $subtotal, 'total' => $subtotal]);`
     This completely wipes out any `discount`, `vat`, or `service_charge` supplied in the order payload or defined in the schema.
  2. All calculations utilize native PHP `(float)` arithmetic. In binary floating point, operations like `19.99 * 3` or progressive addition can drift (e.g. `59.970000000000006`).
- **Impact & Risk:**
  Discounts applied at the register are silently deleted upon item creation, overcharging customers. Tax and service charges are omitted from the order total. Float inaccuracies cause penny/cent drift between POS totals, printed receipts, and card processor batches.
- **Required Changes:**
  1. Use `bcmath` (`bcmul`, `bcadd`, `bcsub`) or integer-based rounding (`round($val, 2)`) for all financial operations.
  2. Compute: `taxable = subtotal - discount`, `vat = taxable * vatRate`, `netTotal = taxable + vat + service_charge`.
  3. Persist `subtotal`, `discount`, `vat`, `service_charge`, and `total` accurately on both `orders` and `invoices`.

---

### [P0-05] Authorization Route Shadowing on Invoice Deletion (`routes/sales.php`)
- **Target Files:**
  - [`routes/sales.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/sales.php#L12)
  - [`routes/shifts.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/shifts.php#L8-L11)
  - [`routes/web.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/web.php#L35-L39)
- **Technical Details & Root Cause:**
  `routes/web.php` requires `sales.php` before `shifts.php`. In `sales.php`, `Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])` is registered under general `['auth', 'verified']` middleware with no role restriction. The supervisor-only route in `shifts.php` (`role:supervisor|admin`) is never reached because the earlier route matches first.
- **Impact & Risk:**
  Any authenticated employee (cashier, barista, or waiter) can issue an HTTP `DELETE` request to `/invoices/{id}` and delete any customer invoice.
- **Required Changes:**
  1. Delete the duplicate `Route::delete('/invoices/{id}')` from `routes/sales.php`.
  2. Create an `InvoicePolicy` authorizing `delete` and `update` only for `admin` and `supervisor` roles.
  3. Apply `$this->authorize('delete', $invoice)` inside `InvoiceController@destroy`.

---

### [P0-06] Thermal Printer Agent Database Schema Mismatch (`printer_jobs` Table)
- **Target Files:**
  - [`app/Http/Controllers/Api/PrintAgentController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/Api/PrintAgentController.php#L28-L35) (`jobs`, `processing`, `complete`, `failed`)
  - [`app/Models/PrinterJob.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/PrinterJob.php#L9-L17)
  - [`database/migrations/2026_07_08_161915_create_printer_jobs_table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/database/migrations/2026_07_08_161915_create_printer_jobs_table.php#L11-L40)
- **Technical Details & Root Cause:**
  `PrintAgentController.php` queries `PrinterJob` using `where('device_uuid', $deviceUuid)` and updates `uuid`, `attempts`, `started_at`, `printed_at`, and `error_message`. However, migration `create_printer_jobs_table.php` only created: `id`, `type`, `order_id`, `payload`, `status`, `copies`, `printer_name`, `timestamps`. None of the agent tracking columns exist.
- **Impact & Risk:**
  The moment the Node.js Windows background agent connects and requests `GET /api/print-agent/jobs`, MySQL crashes with:
  `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'device_uuid' in 'where clause'`. The entire thermal receipt and kitchen ticket printing pipeline fails.
- **Required Changes:**
  1. Create a migration adding `uuid` (unique), `device_uuid` (indexed), `attempts`, `started_at`, `printed_at`, and `error_message` to `printer_jobs`.
  2. Update `$fillable` and `$casts` in `app/Models/PrinterJob.php`.
  3. Ensure `PrintingService` populates `uuid` and `device_uuid` when creating print jobs.

---

### [P0-07] Database Enum Mismatch on Invoice Cancellation (`sale_cancel` in `inventory_movements`)
- **Target Files:**
  - [`app/Http/Controllers/InvoiceController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/InvoiceController.php#L246) (`destroy`)
  - [`database/migrations/2026_09_04_083128_create_inventory_movements_table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/database/migrations/2026_09_04_083128_create_inventory_movements_table.php#L17)
- **Technical Details & Root Cause:**
  When an invoice is deleted in `InvoiceController@destroy`, it creates an `InventoryMovement` with `'type' => 'sale_cancel'`. However, the migration enum for `inventory_movements.type` is strictly defined as `['sale', 'sale_update', 'restock', 'waste']`.
- **Impact & Risk:**
  Whenever a supervisor attempts to cancel or delete an invoice, the database transaction rolls back due to a data truncation / invalid enum value error (`1265 Data truncated for column 'type'`). Invoices cannot be cancelled or deleted.
- **Required Changes:**
  1. Create a migration altering `inventory_movements.type` to include `'sale_cancel'`.
  2. Add unit test asserting that deleting an invoice successfully logs `sale_cancel` movement.

---

### [P0-08] Registration Role Enum Crash & Public `/demo` Employee Data Leak
- **Target Files:**
  - [`app/Http/Controllers/Auth/RegisteredUserController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/Auth/RegisteredUserController.php#L44-L59) (`store`)
  - [`database/migrations/0001_01_01_000000_create_users_table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/database/migrations/0001_01_01_000000_create_users_table.php#L19)
  - [`routes/web.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/web.php#L13)
  - [`app/Http/Controllers/UserController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/UserController.php#L34-L52) (`index`)
- **Technical Details & Root Cause:**
  1. In `RegisteredUserController@store`, validation expects `'role' => 'nullable|in:user,admin'` and defaults to `'user'`. However, `users.role` enum is `['admin', 'supervisor', 'cashier', 'barista', 'client']`. Inserting `'user'` causes an SQL enum failure.
  2. `Route::get('/demo', [UserController::class, "index"])` is completely unprotected by authentication middleware.
- **Impact & Risk:**
  New employee onboarding crashes on registration. Any unauthenticated visitor can browse `/demo` to scrape all staff members' full names and email addresses.
- **Required Changes:**
  1. Remove `/demo` route from `routes/web.php`.
  2. Update `RegisteredUserController` validation and assignment to use valid roles (`cashier`, `barista`, `supervisor`, `admin`, `client`).

---

### [P0-09] Mass Assignment Vulnerabilities (`Invoice` & `InventoryItem`) & Unauthenticated API Routes
- **Target Files:**
  - [`app/Models/Invoice.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Invoice.php#L9) (`protected $guarded = [];`)
  - [`app/Http/Controllers/InventoryController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/InventoryController.php#L39) (`InventoryItem::create($request->all())`)
  - [`routes/api.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/api.php#L117-L122) (`/print-agent/*`)
  - [`routes/inventory.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/inventory.php#L10-L18)
- **Technical Details & Root Cause:**
  1. `Invoice` model has an unconstrained `$guarded = []`.
  2. `InventoryController@store` passes `$request->all()` directly into `create()`.
  3. `routes/api.php` exposes `/print-agent/jobs` and status endpoints without Sanctum or API token middleware.
  4. `routes/inventory.php` has no role middleware; any logged-in user (even non-management) can execute stock adjustments (`POST /inventory/{id}/adjust`).
- **Impact & Risk:**
  Attackers can manipulate financial attributes via mass assignment, arbitrary users on local Wi-Fi can tamper with thermal print jobs, and unauthorized staff can manipulate inventory counts.
- **Required Changes:**
  1. Replace `$guarded = []` with explicit `$fillable` and `$casts` on `Invoice`.
  2. Use validated data `$request->validated()` in `InventoryController@store`.
  3. Add `VerifyPrintAgentToken` middleware to `routes/api.php`.
  4. Restrict inventory adjustments and menu changes in `routes/inventory.php` to `admin` and `supervisor` roles.

---

## 🟡 P1: Café Operations, Hardware Resilience & Order Lifecycle

---

### [P1-01] Table Lifecycle, Multi-Order Conflicts, & Occupancy Synchronization Gap
- **Target Files:**
  - [`app/Http/Requests/OrderRequest.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Requests/OrderRequest.php#L26-L44)
  - [`app/Http/Controllers/TableController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/TableController.php#L93-L107) (`destroy`, `toggle`)
  - [`app/Models/Table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Table.php#L48-L53) (`activeOrders`)
  - [`app/Services/OrderService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/OrderService.php#L34-L52) (`checkout`)
- **Technical Details & Root Cause:**
  1. In `OrderRequest.php`, the validation checks if a table exists and `is_active`, but **does not check if the table is already occupied**.
  2. Table occupancy in `Table::isOccupied()` is derived entirely from `activeOrders()->exists()`. If an order is created via `InvoiceController@store`, no `Order` record is created, so the table appears vacant even if guests are seated.
  3. There is no route or workflow to transfer an active order between tables or release a stuck table.
- **Impact & Risk:**
  Two cashiers can assign different parties to Table 5 simultaneously, causing order mix-ups. Guests seated via manual invoices don't show on the POS floor plan.
- **Required Changes:**
  1. Add validation rule in `OrderRequest` preventing opening an order on an already occupied table unless explicit merging is requested.
  2. Add `TableTransferService` to move active orders from Table A to Table B with atomic row updates.
  3. Ensure table checkout strictly transitions order status to `completed` and releases occupancy.

---

### [P1-02] Asynchronous Print Job Dispatching (Replacing Blocking `ShouldBroadcastNow`)
- **Target Files:**
  - [`app/Events/PrinterJobCreated.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Events/PrinterJobCreated.php#L12)
  - [`app/Services/PrintingService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/PrintingService.php#L94-L108)
- **Technical Details & Root Cause:**
  `PrinterJobCreated` implements `ShouldBroadcastNow`. When an order is created or settled, Laravel broadcasts to Reverb synchronously inside the HTTP worker thread.
- **Impact & Risk:**
  If the WebSocket server or local network experiences a momentary delay, the cashier checkout request hangs until Reverb socket communication times out. Cash registers freeze during rush hours.
- **Required Changes:**
  1. Change `PrinterJobCreated` to implement `ShouldBroadcast` (queued).
  2. Dispatch print job processing via a dedicated queue job (`ProcessPrintJob`) using Laravel's database queue driver with 3 automatic retries and exponential backoff.

---

### [P1-03] Complete Shift & Cash Drawer Reconciliation Subsystem (Floats, Drops, Z-Reports)
- **Target Files:**
  - [`app/Models/Shift.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Shift.php) (Currently empty)
  - [`app/Http/Controllers/ShiftController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/ShiftController.php) (Currently empty stub)
  - [`database/migrations/2026_07_01_091835_create_shifts_table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/database/migrations/2026_07_01_091835_create_shifts_table.php)
  - [`app/Models/Order.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Order.php)
  - [`app/Models/Invoice.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Invoice.php)
- **Technical Details & Root Cause:**
  The `shifts` table only contains `user_id`, `start_time`, `end_time`. It lacks opening float, cash drops, expected cash, actual counted cash, and variance. Furthermore, neither `orders` nor `invoices` possess a `shift_id` column.
- **Impact & Risk:**
  Cashiers cannot be held financially accountable for register shortages or overages. It is impossible to generate an End-of-Day Z-Report summarizing actual cash in drawer versus POS receipts.
- **Required Changes:**
  1. Migration: Add `opening_float`, `cash_sales`, `card_sales`, `instapay_sales`, `cash_drops`, `expected_cash`, `actual_cash`, `difference`, and `status` to `shifts`.
  2. Migration: Add `shift_id` foreign key to `orders` and `invoices`.
  3. Implement `ShiftService` handling:
     - `openShift(user, openingFloat)`
     - `recordCashDrop(shift, amount, reason)`
     - `closeShift(shift, actualCash, notes)` -> calculates variances and generates Z-Report summary.
  4. Implement `ShiftController` endpoints for opening, closing, and auditing cashier shifts.

---

### [P1-04] POS Order FormRequest Validation & Manager Discount Overrides
- **Target Files:**
  - [`app/Http/Requests/OrderRequest.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Requests/OrderRequest.php#L77-L81) (`discount`)
  - [`app/Http/Controllers/OrderController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/OrderController.php#L45) (`checkout`)
- **Technical Details & Root Cause:**
  1. `OrderRequest` accepts any arbitrary `discount` without validating whether the authenticated user has permission to grant discounts, nor does it enforce a maximum discount threshold.
  2. `OrderController@checkout` takes an untyped `Request $request` without a dedicated `CheckoutRequest` (no validation on `payment_method`, amount paid, or change due).
- **Impact & Risk:**
  Cashiers can apply 100% unauthorized discounts to orders without manager PIN approval or supervisor audit logging. Checkout allows missing or invalid payment methods.
- **Required Changes:**
  1. Create `CheckoutRequest` validating `payment_method` (`cash`, `card`, `InstaPay`), `amount_tendered`, and `notes`.
  2. In `OrderRequest`, require `manager_pin` or supervisor approval if `discount > 0` or discount exceeds a configurable percentage (e.g., 10%).

---

### [P1-05] Unified Order-to-Invoice Lifecycle & Bidirectional State Synchronization
- **Target Files:**
  - [`app/Http/Controllers/InvoiceController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/InvoiceController.php#L88-L285)
  - [`app/Services/OrderService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/OrderService.php)
  - [`app/Models/Order.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Order.php)
  - [`app/Models/Invoice.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Invoice.php)
- **Technical Details & Root Cause:**
  The workspace has two conflicting paths: `OrderController` creates an `Order` and calls `InvoiceService` on takeaway or checkout. However, `InvoiceController@store` directly creates an `Invoice` without an `Order`. If an invoice linked to an order is deleted via `InvoiceController@destroy`, the invoice is removed and stock is restored, but the `Order` remains marked `completed`/`paid`.
- **Impact & Risk:**
  Reporting discrepancies between sales invoices and POS orders. Table occupancies get desynchronized from actual financial records.
- **Required Changes:**
  1. Make `Order` the sole source of truth for POS sales.
  2. If an `Invoice` linked to an `order_id` is updated or cancelled, synchronize the underlying `Order` status to `cancelled` and recalculate table occupancy.

---

## 🟢 P2: Performance Bottlenecks, Indexing & Code Separation

---

### [P2-01] Eliminate N+1 Query Storm on POS Tables via `withExists()`
- **Target Files:**
  - [`app/Http/Controllers/SaleController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/SaleController.php#L33-L41) (`index`)
  - [`app/Http/Controllers/TableController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/TableController.php#L16-L19) (`index`)
  - [`app/Http/Controllers/TableController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/TableController.php#L135-L151) (`posIndex`)
  - [`app/Models/Table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Models/Table.php#L65-L68) (`isOccupied`)
- **Technical Details & Root Cause:**
  `SaleController@index` and `TableController@posIndex` fetch all tables and map through them with `$table->isOccupied()`. This executes a separate SQL query for each table to check if active orders exist:
  `SELECT EXISTS(SELECT * FROM orders WHERE table_id = ? AND status IN (...) AND payment_status = 'pending')`
- **Impact & Risk:**
  In a café with 50 tables, loading or polling the POS floor plan executes 51 SQL queries per request. When multiple cashier and waiter devices poll the tables endpoint, database CPU consumption spikes.
- **Required Changes:**
  Replace the loop with Eloquent subquery loading:
  ```php
  $activeTables = Table::active()
      ->withExists(['activeOrders as is_occupied'])
      ->orderBy('name')
      ->get(['id', 'name', 'capacity', 'area']);
  ```
  This reduces 51 queries down to **1 single query**.

---

### [P2-02] Add Composite Database Indexes for Orders, Invoices, and Inventory Movements
- **Target Files:**
  - [`database/migrations/2026_07_08_130634_create_orders_table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/database/migrations/2026_07_08_130634_create_orders_table.php)
  - [`database/migrations/2026_08_01_091900_create_invoices_table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/database/migrations/2026_08_01_091900_create_invoices_table.php)
  - [`database/migrations/2026_09_04_083128_create_inventory_movements_table.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/database/migrations/2026_09_04_083128_create_inventory_movements_table.php)
- **Technical Details & Root Cause:**
  - `orders` table lacks a composite index on `(table_id, status, payment_status)`, resulting in full table scans during occupancy checks.
  - `invoices` table lacks indexes on `created_at` and `(payment_method, created_at)` used by daily sales reporting.
  - `inventory_movements` lacks indexes on `(inventory_item_id, created_at)` and `(type, created_at)`.
- **Impact & Risk:**
  As historical orders grow past 10,000 records, POS table rendering slows down noticeably, and inventory tracking reports will time out.
- **Required Changes:**
  Create a migration adding the following composite indexes:
  1. `orders`: `INDEX idx_orders_table_active (table_id, status, payment_status)`
  2. `orders`: `INDEX idx_orders_created_status (created_at, status)`
  3. `invoices`: `INDEX idx_invoices_date_payment (created_at, payment_method)`
  4. `inventory_movements`: `INDEX idx_movements_item_date (inventory_item_id, created_at)`
  5. `inventory_movements`: `INDEX idx_movements_type_date (type, created_at)`

---

### [P2-03] Refactor Fat `InvoiceController` into Dedicated Service Classes
- **Target Files:**
  - [`app/Http/Controllers/InvoiceController.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Http/Controllers/InvoiceController.php) (378 lines)
  - [`app/Services/InvoiceService.php`](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/app/Services/InvoiceService.php)
- **Technical Details & Root Cause:**
  `InvoiceController` contains mixed business concerns: manual recipe loading, stock balance calculations, inventory movement logging, JSON audits, and discount handling are hardcoded directly into HTTP controller methods (`update`, `store`, `destroy`).
- **Impact & Risk:**
  Code duplication between `OrderService` and `InvoiceController`, high risk of regression bugs during updates, and inability to unit test invoice adjustment workflows without triggering HTTP requests.
- **Required Changes:**
  1. Move invoice calculation and item compilation to `InvoiceService`.
  2. Move stock adjustment from invoice updates to `InventoryService`.
  3. Keep `InvoiceController` methods to less than 25 lines each, handling only validation, delegation to service classes, and JSON response formatting.

---

## 🎯 Verification & Sign-off Protocol

When implementing fixes from this roadmap, verify each phase as follows:
1. **Automated Concurrency Tests:** Run parallel cURL or Pest/PHPUnit tests simulating concurrent checkouts on the same table and order sequence generation.
2. **Printer Agent Integration:** Run mock HTTP requests to `/api/print-agent/jobs` and assert that no missing column SQL errors occur.
3. **Database Assertion:** Verify that all financial calculations match down to the exact piastre/cent without floating-point artifacts.
4. **Shift Balancing:** Verify that closing a shift correctly tallies cash sales and reports accurate variances.
