<?php

namespace App\Services;

use App\Mail\EmailOtpMail;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class EmailOtpSender
{
    public function send(string $email, string $code): void
    {
        try {
            Mail::to($email)->send(new EmailOtpMail($code));
        } catch (Throwable $exception) {
            throw new RuntimeException('The verification email could not be sent.', previous: $exception);
        }
    }
}
