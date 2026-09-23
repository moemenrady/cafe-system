<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use InvalidArgumentException;

class OrderItemService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, discount: float, service_charge: float, vat: float, total: float}
     */
    public function createItems(
        Order $order,
        array $items,
        float $discount = 0.0,
        float $serviceCharge = 0.0,
        float $vatRate = 0.0
    ): array {
        if (empty($items)) {
            throw new InvalidArgumentException('Order items are required.');
        }

        $menuIds = array_filter(array_column($items, 'menu_id'));
        $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

        $subtotal = '0.00';
        $orderItemsToInsert = [];

        foreach ($items as $itemData) {
            $menuId = $itemData['menu_id'] ?? null;
            $menu = $menus->get($menuId);

            if (!$menu) {
                throw new InvalidArgumentException("Menu item with ID {$menuId} not found.");
            }

            $quantity = (int) ($itemData['quantity'] ?? 1);
            $price = number_format((float) $menu->price, 2, '.', '');
            $itemTotal = bcmul($price, (string) $quantity, 2);

            $orderItemsToInsert[] = [
                'order_id' => $order->id,
                'menu_id' => $menu->id,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $itemTotal,
                'notes' => $itemData['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $subtotal = bcadd($subtotal, $itemTotal, 2);
        }

        OrderItem::insert($orderItemsToInsert);

        if ($discount <= 0.0 && (float) $order->discount > 0.0) {
            $discount = (float) $order->discount;
        }
        if ($serviceCharge <= 0.0 && (float) $order->service_charge > 0.0) {
            $serviceCharge = (float) $order->service_charge;
        }

        $discountAmount = number_format(min((float) $subtotal, max(0.0, $discount)), 2, '.', '');
        $taxableAmount = bcsub($subtotal, $discountAmount, 2);
        $vatAmount = $vatRate > 0 ? bcmul($taxableAmount, (string) ($vatRate / 100), 2) : number_format((float) $order->vat, 2, '.', '');
        $serviceAmount = number_format(max(0.0, $serviceCharge), 2, '.', '');

        $netTotal = bcadd(bcadd($taxableAmount, $vatAmount, 2), $serviceAmount, 2);

        $order->update([
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'service_charge' => $serviceAmount,
            'vat' => $vatAmount,
            'total' => $netTotal,
        ]);

        return [
            'subtotal' => (float) $subtotal,
            'discount' => (float) $discountAmount,
            'service_charge' => (float) $serviceAmount,
            'vat' => (float) $vatAmount,
            'total' => (float) $netTotal,
        ];
    }
}
