<?php

namespace App\Support\Notifications;

use App\Models\Item;
use App\Models\User;
use App\Models\RecipientMapping;

class RecipientResolver
{
    /**
     * @return User[]
     */
    public function resolveForItem(Item $item, string $event, array $ctx = []): array
    {
        $users = [];

        if ($item->assign_to_id) {
            if ($u = $this->resolveTextToUser($item->assign_to_id)) {
                $users[$u->id] = $u;
            }
        }

        if ($event === ItemEvent::ASSIGNEE_CHANGED && !empty($ctx['old_assign_to'])) {
            if ($old = $this->resolveTextToUser($ctx['old_assign_to'])) {
                $users[$old->id] = $old;
            }
        }

        if (empty($users)) {
            if (!empty($item->created_by) && $creator = User::find($item->created_by, ['*'])) {
                $users[$creator->id] = $creator;
            }
            foreach ($this->adminUsers() as $admin) {
                $users[$admin->id] = $admin;
            }
        }

        return array_values($users);
    }

    protected function resolveTextToUser(string $raw): ?User
    {
        $key = $this->normalize($raw);

        // 1. Exact email match
        if (str_contains($key, '@')) {
            return User::whereRaw('LOWER(email) = ?', [$key])->first();
        }

        // // 2. Recipient mapping match
        // $map = RecipientMapping::where('key', $key)->first();
        // if ($map && $u = User::find($map->user_id, ['*'])) {
        //     return $u;
        // }

        // 3. Exact name match
        $u = User::whereRaw('LOWER(name) = ?', [$key])->first();
        if ($u) {
            return $u;
        }

        // 4. NEW: Initials match (only if unique)
        $all = User::all(['id', 'name', 'email']);
        $byInitials = $all->filter(fn($usr) => $this->initials($usr->name) === $key);
        if ($byInitials->count() === 1) {
            return $byInitials->first();
        }

        // 5. Partial name match (only if unique)
        $cands = User::whereRaw('LOWER(name) LIKE ?', ["%{$key}%"])->get();
        return $cands->count() === 1 ? $cands->first() : null;
    }

    protected function normalize(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/\s+/', ' ', $s);
        $s = preg_replace('/[^a-z0-9@.\s]/', '', $s);
        return $s;
    }

    protected function initials(string $name): string
    {
        // "Gilang Eko" -> "ge"
        $parts = preg_split('/\s+/', strtolower(trim($name)));
        $letters = array_map(fn($p) => mb_substr($p, 0, 1), array_filter($parts));
        return implode('', $letters);
    }

    /**
     * @return User[]
     */
    protected function adminUsers(): array
    {
        return User::where('role', '=', 'admin')->get()->all();
    }
}
