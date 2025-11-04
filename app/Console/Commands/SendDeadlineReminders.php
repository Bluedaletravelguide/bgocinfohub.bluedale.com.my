<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Models\User;
use App\Mail\DailyTaskSummary;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendDeadlineReminders extends Command
{
    protected $signature = 'items:send-deadline-reminders';
    protected $description = 'Send daily task summary to all users with their assigned tasks';

    public function handle()
    {
        $today = Carbon::today();

        // Get all items that are assigned to someone
        $items = Item::whereNotNull('assign_to_id')
            ->get();

        if ($items->isEmpty()) {
            $this->info('No items to send.');
            return Command::SUCCESS;
        }

        // Group items by assignee
        $itemsByUser = [];
        
        foreach ($items as $item) {
            $user = $this->resolveUser($item->assign_to_id);
            
            if (!$user) {
                continue;
            }
            
            // Determine item status category
            $statusCategory = $this->categorizeStatus($item, $today);
            
            // Add metadata
            $item->status_category = $statusCategory;
            
            // Group by user
            if (!isset($itemsByUser[$user->id])) {
                $itemsByUser[$user->id] = [
                    'user' => $user,
                    'pending' => [],
                    'expired' => [],
                    'completed' => [],
                ];
            }
            
            $itemsByUser[$user->id][$statusCategory][] = $item;
        }

        // Send one email per user
        $count = 0;
        foreach ($itemsByUser as $data) {
            $totalTasks = count($data['pending']) + count($data['expired']) + count($data['completed']);
            
            // Skip if user has no tasks
            if ($totalTasks === 0) {
                continue;
            }
            
            Mail::to($data['user']->email)
                ->send(new DailyTaskSummary($data));
            
            $count++;
            $this->info("Daily summary sent to {$data['user']->name} ({$data['user']->email}) - P:{$data['pending']->count()} E:{$data['expired']->count()} C:{$data['completed']->count()}");
        }

        $this->info("Total daily summary emails sent: {$count}");
        
        return Command::SUCCESS;
    }
    
    protected function categorizeStatus($item, $today)
    {
        $status = strtolower($item->status ?? '');
        
        // Completed category
        if (in_array($status, ['done', 'completed', 'finished'])) {
            return 'completed';
        }
        
        // Expired category (deadline passed and not completed)
        if ($item->deadline && Carbon::parse($item->deadline)->lt($today)) {
            return 'expired';
        }
        
        // Pending category (default)
        return 'pending';
    }
    
    protected function resolveUser(string $text): ?User
    {
        $key = strtolower(trim($text));
        
        // Try email
        if (str_contains($key, '@')) {
            return User::whereRaw('LOWER(email) = ?', [$key])->first();
        }
        
        // Try exact name
        $user = User::whereRaw('LOWER(name) = ?', [$key])->first();
        if ($user) return $user;
        
        // Try initials
        $all = User::all(['id', 'name', 'email']);
        $byInitials = $all->filter(function($u) use ($key) {
            $parts = preg_split('/\s+/', strtolower(trim($u->name)));
            $initials = implode('', array_map(fn($p) => mb_substr($p, 0, 1), $parts));
            return $initials === $key;
        });
        
        if ($byInitials->count() === 1) {
            return $byInitials->first();
        }
        
        // Try partial match
        $cands = User::whereRaw('LOWER(name) LIKE ?', ["%{$key}%"])->get();
        return $cands->count() === 1 ? $cands->first() : null;
    }
}