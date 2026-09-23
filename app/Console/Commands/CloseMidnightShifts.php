<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Console\Command;

class CloseMidnightShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:close-midnight';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إغلاق الشيفتات المفتوحة تلقائياً عند الساعة 12 منتصف الليل وحساب الإجماليات';

    /**
     * Execute the console command.
     */
    public function handle(ShiftService $shiftService): int
    {
        $openShifts = Shift::where('status', 'open')->get();

        if ($openShifts->isEmpty()) {
            $this->info('لا توجد شيفتات مفتوحة حالياً للإغلاق.');
            return Command::SUCCESS;
        }

        $closedCount = 0;
        foreach ($openShifts as $shift) {
            $shiftService->autoCloseMidnight($shift);
            $closedCount++;
        }

        $this->info("تم إغلاق {$closedCount} شيفت(ات) تلقائياً بنجاح.");
        return Command::SUCCESS;
    }
}
