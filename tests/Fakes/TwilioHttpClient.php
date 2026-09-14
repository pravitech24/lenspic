<?php

namespace Tests\Fakes;

use Twilio\AuthStrategy\AuthStrategy;
use Twilio\Http\{Client, Response};

class TwilioHttpClient implements Client
{
    public array $requests = [];
    public array $responses = [];

    public function request(string $method, string $url, array $params = [], array $data = [], array $headers = [], ?string $user = null, ?string $password = null, ?int $timeout = null, ?AuthStrategy $authStrategy = null): Response
    {
        $this->requests[] = compact('method','url','data');
        $response = array_shift($this->responses);
        if (is_callable($response)) $response=$response();
        if ($response instanceof \Throwable) throw $response;
        if (!$response) throw new \LogicException('No fake Twilio response was queued.');
        return new Response($response[0], json_encode($response[1]));
    }

    public function queue(string $status): void
    {
        $this->responses[] = [200, ['sid'=>'VE'.str_repeat('c',32),'status'=>$status,'channel'=>'sms']];
    }
}
