<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $items
    ) {}

    public function build()
    {
        $total = $this->items['total'];
        $expired = $this->items['expired']->count();
        $dueToday = $this->items['due_today']->count();

        $urgentCount = $expired + $dueToday;

        $emoji = match (true) {
            $expired > 0 => '🔴',
            $dueToday > 0 => '🟡',
            default => '✅'
        };

        $subject = "{$emoji} [BGOC] Info Hub ({$total} total";
        if ($urgentCount > 0) {
            $subject .= ", {$urgentCount} Expired";
        }
        $subject .= ")";

        return $this->subject($subject)
            ->view('emails.user_digest')
            ->with([
                'data' => [
                    'user' => $this->user,
                    'expired' => $this->items['expired'],
                    'due_today' => $this->items['due_today'],
                    'due_tomorrow' => $this->items['due_tomorrow'],
                    'pending' => $this->items['pending'],
                ],
            ]);
    }
}
