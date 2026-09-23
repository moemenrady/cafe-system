<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    public static function getNextOrderNumber(): string
    {
        return self::generateSequence('order', 'ORD');
    }

    public static function getNextInvoiceNumber(): string
    {
        return self::generateSequence('invoice', 'INV');
    }

    public static function generateSequence(string $type, string $prefix): string
    {
        $today = Carbon::now()->format('Ymd');

        return DB::transaction(function () use ($type, $prefix, $today) {
            $sequence = DB::table('document_sequences')
                ->where('type', $type)
                ->where('date_key', $today)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                try {
                    DB::table('document_sequences')->insert([
                        'type' => $type,
                        'date_key' => $today,
                        'last_number' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $next = 1;
                } catch (\Throwable $e) {
                    $sequence = DB::table('document_sequences')
                        ->where('type', $type)
                        ->where('date_key', $today)
                        ->lockForUpdate()
                        ->first();

                    $next = ($sequence ? $sequence->last_number : 0) + 1;

                    DB::table('document_sequences')
                        ->where('type', $type)
                        ->where('date_key', $today)
                        ->update([
                            'last_number' => $next,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                $next = $sequence->last_number + 1;

                DB::table('document_sequences')
                    ->where('type', $type)
                    ->where('date_key', $today)
                    ->update([
                        'last_number' => $next,
                        'updated_at' => now(),
                    ]);
            }

            return sprintf('%s-%s-%05d', $prefix, $today, $next);
        });
    }
}
