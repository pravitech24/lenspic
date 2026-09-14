<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\LensPicNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class NotificationService
{
    public const CATEGORIES = ['groups','uploads','face_recognition','team','billing','storage','system'];
    public const SEVERITIES = ['info','success','warning','error'];
    public const ACTIONS = [
        'groups.show', 'groups.operations', 'groups.members', 'biometric.results-page',
        'settings.team-login', 'settings.profile', 'settings.wallet', 'settings.transactions',
        'settings.subscription', 'exports.download',
    ];

    public function send(User $recipient, string $idempotencyKey, array $payload): ?DatabaseNotification
    {
        $idempotencyKey = Str::limit($idempotencyKey, 191, '');
        $payload = $this->validate($payload);
        if (! $this->enabled($recipient, $payload)) return null;

        return DB::transaction(function () use ($recipient, $idempotencyKey, $payload) {
            $existing = DB::table('notification_deliveries')->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing?->notification_id && ($notification = $recipient->notifications()->find($existing->notification_id))) return $notification;

            $notification = new LensPicNotification($payload);
            $recipient->notifyNow($notification);
            $stored = $recipient->notifications()->findOrFail($notification->id);
            DB::table('notification_deliveries')->insertOrIgnore([
                'notification_id' => $stored->id,
                'recipient_id' => $recipient->id,
                'studio_id' => $payload['studio_id'],
                'channel' => 'database',
                'status' => 'delivered',
                'idempotency_key' => $idempotencyKey,
                'attempted_at' => now(),
                'delivered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $winner = DB::table('notification_deliveries')->where('idempotency_key', $idempotencyKey)->first();
            if ($winner && $winner->notification_id !== $stored->id) {
                $stored->delete();
                return $recipient->notifications()->findOrFail($winner->notification_id);
            }
            return $stored;
        });
    }

    private function enabled(User $recipient, array $payload): bool
    {
        if ($payload['mandatory']) return true;
        $preferences = $recipient->meta['preferences'] ?? [];
        if (($preferences['in_app_notifications'] ?? true) === false) return false;
        $key = match ($payload['category']) {
            'groups'=>'notify_group_activity','uploads'=>'notify_upload_completion','face_recognition'=>'notify_face_recognition',
            'team'=>'notify_team_activity','storage'=>'notify_storage_warnings','billing'=>'notify_billing_wallet','system'=>'notify_product_updates',default=>null,
        };
        return $key === null || ($preferences[$key] ?? true) !== false;
    }

    private function validate(array $payload): array
    {
        $category = $payload['category'] ?? null;
        $severity = $payload['severity'] ?? 'info';
        $action = $payload['action_route'] ?? null;
        if (! in_array($category, self::CATEGORIES, true)) throw new InvalidArgumentException('Unsupported notification category.');
        if (! in_array($severity, self::SEVERITIES, true)) throw new InvalidArgumentException('Unsupported notification severity.');
        if ($action !== null && ! in_array($action, self::ACTIONS, true)) throw new InvalidArgumentException('Unsupported notification action.');
        foreach (['title','message'] as $field) if (! is_string($payload[$field] ?? null) || trim($payload[$field]) === '') throw new InvalidArgumentException("Notification {$field} is required.");
        return [
            'schema_version' => 1,
            'category' => $category,
            'title' => Str::limit(strip_tags($payload['title']), 160),
            'message' => Str::limit(strip_tags($payload['message']), 500),
            'context' => isset($payload['context']) ? Str::limit(strip_tags((string) $payload['context']), 160) : null,
            'studio_id' => isset($payload['studio_id']) ? (int) $payload['studio_id'] : null,
            'actor_id' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
            'subject_type' => isset($payload['subject_type']) ? Str::limit((string) $payload['subject_type'], 80) : null,
            'subject_id' => isset($payload['subject_id']) ? Str::limit((string) $payload['subject_id'], 100) : null,
            'action_route' => $action,
            'action_parameters' => is_array($payload['action_parameters'] ?? null) ? $payload['action_parameters'] : [],
            'action_label' => isset($payload['action_label']) ? Str::limit(strip_tags((string) $payload['action_label']), 80) : null,
            'severity' => $severity,
            'mandatory' => (bool) ($payload['mandatory'] ?? false),
        ];
    }
}
