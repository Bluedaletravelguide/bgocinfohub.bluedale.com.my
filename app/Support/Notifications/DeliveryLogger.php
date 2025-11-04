<?php

namespace App\Support\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeliveryLogger
{
    /**
     * Return true to SKIP sending (debounced).
     */
    public function shouldDebounce(int $userId, int $itemId, string $channel, string $event, int $seconds = 300): bool
    {
        $key = $this->fingerprint($userId, $itemId, $channel, $event);
        if (Cache::has($key)) {
            return true;
        }
        Cache::put($key, 1, $seconds);
        return false;
    }

    public function logSent(int $userId, int $itemId, string $channel, string $event, array $extra = []): void
    {
        Log::info('[Notify][SENT]', array_merge([
            'user_id' => $userId,
            'item_id' => $itemId,
            'channel' => $channel,
            'event'   => $event,
        ], $extra));
    }

    public function logSkipped(int $userId, int $itemId, string $channel, string $event, array $extra = []): void
    {
        Log::info('[Notify][SKIPPED]', array_merge([
            'user_id' => $userId,
            'item_id' => $itemId,
            'channel' => $channel,
            'event'   => $event,
        ], $extra));
    }

    private function fingerprint(int $userId, int $itemId, string $channel, string $event): string
    {
        return "notify:{$channel}:{$event}:u{$userId}:i{$itemId}";
    }
}
