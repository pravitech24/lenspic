<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogger
{
    public function log(string $action, Model|string|null $subject = null, ?Group $group = null, array $before = [], array $after = [], array $metadata = []): AuditLog
    {
        $request = request();
        return AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_type' => auth()->check() ? 'user' : 'system',
            'group_id' => $group?->id,
            'action' => $action,
            'subject_type' => $subject instanceof Model ? $subject::class : (is_string($subject) ? $subject : null),
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'request_id' => $request->attributes->get('request_id') ?? (string) Str::uuid(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'before' => $before ?: null,
            'after' => $after ?: null,
            'metadata' => $this->sanitize($metadata) ?: null,
        ]);
    }

    private function sanitize(array $metadata): array
    {
        foreach (['password', 'otp', 'token', 'selfie', 'secret', 'signature'] as $key) unset($metadata[$key]);
        return $metadata;
    }
}
