<?php

namespace App\Services\Otp;

use Twilio\AuthStrategy\AuthStrategy;
use Twilio\Http\{CurlClient, Response};

class PrivateTwilioTransport extends CurlClient
{
    public function __construct()
    {
        parent::__construct([
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
    }

    public function request(string $method, string $url, array $params = [], #[\SensitiveParameter] array $data = [], array $headers = [], ?string $user = null, #[\SensitiveParameter] ?string $password = null, ?int $timeout = null, ?AuthStrategy $authStrategy = null): Response
    {
        if (app()->runningUnitTests()) throw new \Twilio\Exceptions\EnvironmentException('Real SMS requests are disabled in automated tests.');
        try {
            return parent::request($method, $url, $params, $data, $headers, $user, $password, $timeout, $authStrategy);
        } finally {
            // The SDK otherwise retains complete credential-bearing cURL options and response bodies.
            $this->lastRequest = null;
            $this->lastResponse = null;
        }
    }
}
