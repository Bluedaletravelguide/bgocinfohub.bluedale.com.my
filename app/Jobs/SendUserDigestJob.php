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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SendUserDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;
    public $backoff = [30, 120, 300];

    public function __construct(public int $userId) {}

    public function handle(DeliveryLogger $logger): void
    {
        try {
            $user = User::find($this->userId);

            if (!$user || !$user->email) {
                Log::warning('[Digest][SKIP] user missing or no email', ['user_id' => $this->userId]);
                return;
            }

            $today = Carbon::now('Asia/Kuala_Lumpur')->startOfDay();
            $tomorrow = (clone $today)->addDay();

            // --- Robust task matching dengan pengecekan kolom ---
            $name = trim((string) $user->name);
            $email = trim((string) $user->email);

            $query = Item::query()
                ->where('status', '!=', 'Completed')
                ->where(function ($q) use ($user, $name, $email) {
                    // 1) Cek kolom assign_to_user_id (jika ada)
                    if (Schema::hasColumn('items', 'assign_to_user_id')) {
                        $q->orWhere('assign_to_user_id', $user->id);
                    }

                    // 2) Cek kolom assign_to_id (legacy - biasanya berisi nama)
                    if (Schema::hasColumn('items', 'assign_to_id')) {
                        $q->orWhereRaw('LOWER(TRIM(assign_to_id)) = ?', [Str::lower($name)]);
                    }

                    // 3) Cek kolom assign_to (fallback nama)
                    if (Schema::hasColumn('items', 'assign_to')) {
                        $q->orWhereRaw('LOWER(TRIM(assign_to)) = ?', [Str::lower($name)]);
                    }

                    // 4) Cek kolom assign_to_email (jika ada)
                    if (Schema::hasColumn('items', 'assign_to_email')) {
                        $q->orWhereRaw('LOWER(TRIM(assign_to_email)) = ?', [Str::lower($email)]);
                    }
                })
                ->orderBy('deadline', 'asc');

            $allItems = $query->get();

            // Normalize deadlines to Carbon (null-safe)
            $asDate = function ($d) {
                if (!$d) return null;
                try {
                    return $d instanceof Carbon ? $d->copy()->startOfDay() : Carbon::parse($d)->startOfDay();
                } catch (\Throwable) {
                    return null;
                }
            };

            // Grouping berdasarkan deadline
            $expired = $allItems->filter(fn($it) => ($d = $asDate($it->deadline)) && $d->lt($today));
            $dueToday = $allItems->filter(fn($it) => ($d = $asDate($it->deadline)) && $d->equalTo($today));
            $dueTomorrow = $allItems->filter(fn($it) => ($d = $asDate($it->deadline)) && $d->equalTo($today->copy()->addDay()));
            $pending = $allItems->filter(fn($it) => ($d = $asDate($it->deadline)) && $d->gt($tomorrow));

            // Debounce check
            if (method_exists($logger, 'shouldDebounce') && $logger->shouldDebounce($user->id, 0, 'mail', 'user_digest')) {
                $logger->logSkipped($user->id, 0, 'mail', 'user_digest', ['reason' => 'debounced']);
                Log::info('[Digest][SKIP] debounced', ['user_id' => $user->id, 'email' => $user->email]);
                return;
            }

            // ALWAYS send (even if zero) so every user gets a daily summary
            $payload = [
                'expired'      => $expired,
                'due_today'    => $dueToday,
                'due_tomorrow' => $dueTomorrow,
                'pending'      => $pending,
                'total'        => $allItems->count(),
            ];

            Mail::to($user->email)->send(new UserDigestMail($user, $payload));
            $logger->logSent($user->id, 0, 'mail', 'user_digest');

            Log::info('[Digest][SENT]', [
                'user_id'     => $user->id,
                'user_name'   => $user->name,
                'email'       => $user->email,
                'total_items' => $payload['total'],
                'expired'     => $expired->count(),
                'due_today'   => $dueToday->count(),
                'tomorrow'    => $dueTomorrow->count(),
                'pending'     => $pending->count(),
            ]);

        } catch (\Throwable $e) {
            Log::error('[Digest][FAIL]', [
                'user_id' => $this->userId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
