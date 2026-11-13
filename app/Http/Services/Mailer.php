<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class Mailer
{
    /**
     * Function to dispatch message to configured receiver
     *
     * @return string message received from server
     */
    public function dispatchEmail($body)
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->retrieveToken()
        ])
            ->post(config('services.sendy.url') . 'api/v1/emails', [
                'to' => explode(",", config('services.sendy.email_to')),
                'from' => config('services.sendy.email_from'),
                'subject' => 'Alert from BUZZER',
                'body' => $body
            ])
            ->throw()
            ->json('message');
    }

    /**
     * Function to retrieve authenticated token
     *
     * @return string token from oauth route
     */
    public function retrieveToken()
    {
        return Http::asForm()
            ->post(config('services.sendy.url') . 'oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.sendy.client_id'),
                'client_secret' => config('services.sendy.client_secret'),
                'scope' => '',
            ])
            ->throw()
            ->json('access_token');
    }
}
