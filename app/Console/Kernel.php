<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Admin digest at 9 AM
        $schedule->command('items:send-deadline-reminders')->dailyAt('09:00');

        // User-specific digests at 8 AM
        $schedule->call(function () {
            $users = \App\Models\User::whereNotNull('email')
                ->where('role', '!=', 'admin')  // Skip admins (they get the admin digest)
                ->get();

            foreach ($users as $user) {
                \App\Jobs\SendUserDigestJob::dispatch($user->id);
            }
        })->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
