<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule): void
    {
        // Envoyer la newsletter tous les lundis à 9h
        $schedule->command('newsletter:hebdo')
            ->weekly()
            ->mondays()
            ->at('09:00')
            ->withoutOverlapping();
    }
}
