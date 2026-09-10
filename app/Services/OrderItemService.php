<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;

class OrderItemService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, total: float}
     */
    public function createItems(Order $order, array $items): array
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Order items are required.');
        }

        $subtotal = 0;

        foreach ($items as $itemData) {
            $menu = Menu::findOrFail($itemData['menu_id'] ?? null);
            $quantity = (int) ($itemData['quantity'] ?? 1);
            $price = (float) $menu->price;
            $total = $price * $quantity;

            OrderItem::create([
                'order_id' => $order->id,
                'menu_id' => $menu->id,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total,
                'notes' => $itemData['notes'] ?? null,
            ]);

            $subtotal += $total;
        }

        $order->update([
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);

        return [
            'subtotal' => (float) $subtotal,
            'total' => (float) $subtotal,
        ];
    }
}
