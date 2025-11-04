<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Jobs\SendItemNotificationJob;
use Carbon\Carbon;

class SendDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:send-deadline-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for items with deadlines (H-3 and H)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $threeDaysLater = Carbon::today()->addDays(3);

        // Find items with deadline = today OR 3 days from now
        // AND status is not "done" or "completed" or "cancelled"
        $items = Item::whereNotNull('deadline')
            ->whereIn('deadline', [$today->format('Y-m-d'), $threeDaysLater->format('Y-m-d')])
            ->whereNotIn('status', ['done', 'completed', 'cancelled', 'Done', 'Completed', 'Cancelled'])
            ->get();

        $count = 0;
        foreach ($items as $item) {
            $daysUntilDeadline = Carbon::parse($item->deadline)->diffInDays($today, false);

            $context = [
                'days_until_deadline' => $daysUntilDeadline,
                'is_deadline_today' => $daysUntilDeadline === 0,
                'is_three_days_before' => $daysUntilDeadline === -3,
            ];

            SendItemNotificationJob::dispatch('reminder', $item->id, $context);
            $count++;

            $this->info("Reminder queued for Item #{$item->id} (Deadline: {$item->deadline})");
        }

        $this->info("Total reminders queued: {$count}");

        return Command::SUCCESS;
    }
}
