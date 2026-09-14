<?php

namespace App\Mail;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactEnquiryAcknowledgement extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public function __construct(public ContactEnquiry $enquiry) {}
    public function build(): self
    {
        return $this->subject('We received your LensPic enquiry')
            ->view('emails.contact-acknowledgement');
    }
}
