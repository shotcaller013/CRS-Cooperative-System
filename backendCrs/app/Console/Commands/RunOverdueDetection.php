<?php
// app/Console/Commands/RunOverdueDetection.php
namespace App\Console\Commands;

use App\Services\OverdueDetectionService;
use Illuminate\Console\Command;

class RunOverdueDetection extends Command
{
    protected $signature   = 'coop:detect-overdue';
    protected $description = 'Flag overdue amortization periods and compute penalties';

    public function handle(OverdueDetectionService $service): int
    {
        $this->info('Running overdue detection…');
        $count = $service->run();
        $this->info("Done — {$count} period(s) flagged.");
        return Command::SUCCESS;
    }
}
