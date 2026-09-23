<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function updateInventory(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing(['items.menu.recipes.inventoryItem', 'invoice']);

            $requirements = [];
            foreach ($order->items as $orderItem) {
                $menu = $orderItem->menu;
                if (!$menu) {
                    continue;
                }

                foreach ($menu->recipes as $recipe) {
                    if (!$recipe->inventory_item_id) {
                        continue;
                    }

                    $neededQuantity = (float) $recipe->quantity_used * (int) $orderItem->quantity;
                    $requirements[$recipe->inventory_item_id] = ($requirements[$recipe->inventory_item_id] ?? 0.0) + $neededQuantity;
                }
            }

            if (empty($requirements)) {
                return;
            }

            // Enforce consistent locking order to prevent deadlocks
            ksort($requirements);

            $items = InventoryItem::whereIn('id', array_keys($requirements))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requirements as $itemId => $neededQuantity) {
                /** @var InventoryItem $inventoryItem */
                $inventoryItem = $items->get($itemId);

                if (!$inventoryItem) {
                    continue;
                }

                if ((float) $inventoryItem->quantity < $neededQuantity) {
                    throw new RuntimeException("Insufficient inventory for [{$inventoryItem->name}]. Required: {$neededQuantity} {$inventoryItem->unit}, Available: {$inventoryItem->quantity} {$inventoryItem->unit}.");
                }

                $newBalance = round((float) $inventoryItem->quantity - $neededQuantity, 2);
                $inventoryItem->update(['quantity' => $newBalance]);

                InventoryMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type'              => 'sale',
                    'quantity'          => -$neededQuantity,
                    'balance_after'     => $newBalance,
                    'invoice_id'        => $order->invoice?->id,
                    'user_id'           => Auth::check() ? Auth::id() : ($order->created_by ?: 1),
                ]);
            }
        });
    }
}
