<?php
namespace App\Mail;
use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class TeamInvitationMail extends Mailable implements ShouldQueue {
 use Queueable,SerializesModels;
 public function __construct(public TeamInvitation$invitation,public string$acceptUrl){}
 public function build():self{return$this->subject("You're invited to join {$this->invitation->owner->studio_name} on LensPic")->view('emails.team-invitation');}
}
