<?php
namespace App\Contracts;
interface OtpSender { public function send(string $mobileE164, string $code): void; }
