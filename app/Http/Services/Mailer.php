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
            ->post(env('URL_SENDY') . 'api/v1/emails', [
                'to' => explode(",", env('EMAIL_TO')),
                'from' => env('EMAIL_FROM'),
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
            ->post(env('URL_SENDY') . 'oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => env('CLIENT_ID_SENDY'),
                'client_secret' => env('CLIENT_SECRET_SENDY'),
                'scope' => '',
            ])
            ->throw()
            ->json('access_token');
    }
}
