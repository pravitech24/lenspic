<?php

namespace App\Mail;

use App\Models\Group;
use App\Models\GroupAccessInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GroupInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Group $group,
        public GroupAccessInvite $invite,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("You're invited to {$this->group->name}")
            ->view('emails.group-invitation');
    }
}
