<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class LensPicNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload) { $this->id=(string)Str::uuid(); }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array { return $this->payload; }
}
