<?php

namespace App\Helpers;

use App\Models\Invoice;

class InvoiceNumberHelper
{
    public static function generate()
    {
        $last = Invoice::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;

        // هيطلع شكلها كدا: INV-20260702-00001
        return 'INV-' . now()->format('Ymd') . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}