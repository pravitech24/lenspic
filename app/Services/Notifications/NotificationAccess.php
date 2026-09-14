<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Throwable;

class NotificationAccess
{
    public function query(User $user): MorphMany
    {
        $studioIds = $user->studioMemberships()->where('status', 'active')->pluck('studio_owner_id')->push($user->id)->unique()->values();
        return $user->notifications()->where(function ($query) use ($studioIds) {
            $query->whereNull('data->studio_id')->orWhereIn('data->studio_id', $studioIds);
        });
    }

    public function find(User $user, string $id): DatabaseNotification
    {
        return $this->query($user)->whereKey($id)->firstOrFail();
    }

    public function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $url = null;
        $route = $data['action_route'] ?? null;
        if (in_array($route, NotificationService::ACTIONS, true) && Route::has($route)) {
            try { $url = route($route, $data['action_parameters'] ?? []); } catch (Throwable) { $url = null; }
        }
        return [
            'id' => $notification->id,
            'category' => $data['category'] ?? 'system',
            'title' => $data['title'] ?? 'LensPic notification',
            'message' => $data['message'] ?? 'Account activity is available.',
            'context' => $data['context'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'action_url' => $url,
            'action_label' => $url ? ($data['action_label'] ?? 'View details') : null,
        ];
    }
}
