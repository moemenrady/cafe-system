<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

class OrderItemService
{
    public function __construct(
        protected OrderCalculationService $calculationService
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, OrderItem>
     */
    public function createItems(Order $order, array $items): Collection
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Order items are required.');
        }

        $createdItems = collect();

        foreach ($items as $itemData) {
            $menu = Menu::findOrFail($itemData['menu_id'] ?? null);
            $quantity = (int) ($itemData['quantity'] ?? 1);
            $price = (float) $menu->price;
            $total = $price * $quantity;

            $item = OrderItem::create([
                'order_id' => $order->id,
                'menu_id'  => $menu->id,
                'quantity' => $quantity,
                'price'    => $price,
                'total'    => $total,
                'notes'    => $itemData['notes'] ?? null,
            ]);

            $createdItems->push($item);
        }

        $this->calculationService->recalculateOrder($order);

        return $createdItems;
    }
}
