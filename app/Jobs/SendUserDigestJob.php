<?php

namespace App\Jobs;

use App\Mail\UserDigestMail;
use App\Models\Item;
use App\Models\User;
use App\Support\Notifications\DeliveryLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendUserDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 300];

    public function __construct(
        public int $userId
    ) {}

    public function handle(DeliveryLogger $logger): void
    {
        try {
            $user = User::find($this->userId);
            if (!$user || !$user->email) {
                Log::warning('SendUserDigestJob: User not found or no email', [
                    'user_id' => $this->userId,
                ]);
                return;
            }

            $today = now('Asia/Kuala_Lumpur')->startOfDay();

            // 🔥 IMPORTANT: Query by user NAME (which matches assign_to_id)
            $allItems = Item::where('assign_to_id', $user->name) // ← Changed from $user->id to $user->name
                ->where('status', '!=', 'Completed')
                ->orderBy('deadline', 'asc')
                ->get();

            // Group by urgency
            $expired = $allItems->filter(fn($item) =>
                $item->deadline && $item->deadline < $today->toDateString()
            );

            $dueToday = $allItems->filter(fn($item) =>
                $item->deadline === $today->toDateString()
            );

            $dueTomorrow = $allItems->filter(fn($item) =>
                $item->deadline === $today->copy()->addDay()->toDateString()
            );

            $pending = $allItems->filter(fn($item) =>
                $item->deadline > $today->copy()->addDay()->toDateString()
            );

            // Don't send if user has no items
            if ($allItems->isEmpty()) {
                Log::info('SendUserDigestJob: No items for user', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);
                return;
            }

            // Debounce check (optional)
            if ($logger->shouldDebounce($user->id, 0, 'mail', 'user_digest')) {
                $logger->logSkipped($user->id, 0, 'mail', 'user_digest', ['reason' => 'debounced']);
                return;
            }

            Mail::to($user->email)->send(new UserDigestMail($user, [
                'expired' => $expired,
                'due_today' => $dueToday,
                'due_tomorrow' => $dueTomorrow,
                'pending' => $pending,
                'total' => $allItems->count(),
            ]));

            $logger->logSent($user->id, 0, 'mail', 'user_digest');

            Log::info('SendUserDigestJob: Sent digest', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'total_items' => $allItems->count(),
                'expired' => $expired->count(),
                'due_today' => $dueToday->count(),
            ]);

        } catch (\Throwable $e) {
            Log::error('SendUserDigestJob failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
