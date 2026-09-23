<?php

namespace App\Helpers;

use App\Services\DocumentSequenceService;

class InvoiceNumberHelper
{
    public static function generate(): string
    {
        return DocumentSequenceService::getNextInvoiceNumber();
    }
}