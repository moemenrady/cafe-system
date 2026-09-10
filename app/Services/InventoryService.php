<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function updateInventory(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $orderItem) {
                $menu = $orderItem->menu()->with('recipes.inventoryItem')->first();

                if (!$menu) {
                    continue;
                }

                foreach ($menu->recipes as $recipe) {
                    $inventoryItem = $recipe->inventoryItem;

                    if (!$inventoryItem) {
                        continue;
                    }

                    $neededQuantity = $recipe->quantity_used * $orderItem->quantity;

                    if ($inventoryItem->quantity < $neededQuantity) {
                        throw new \RuntimeException("Insufficient inventory for {$inventoryItem->name}.");
                    }

                    $inventoryItem->decrement('quantity', $neededQuantity);

                    InventoryMovement::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'type' => 'sale',
                        'quantity' => -$neededQuantity,
                        'balance_after' => $inventoryItem->fresh()->quantity,
                        'invoice_id' => null,
                        'user_id' => Auth::check() ? Auth::id() : 1,
                    ]);
                }
            }
        });
    }
}
