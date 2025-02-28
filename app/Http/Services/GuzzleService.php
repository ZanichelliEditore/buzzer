<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use App\Events\SendMessageEvent;
use Illuminate\Support\Facades\Config;

class GuzzleService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30, // Response timeout
            'connect_timeout' => 30, // Connection timeout
        ]);
    }

    /**
     * Send http request POST with Basic Authentication
     *
     * @param SendMessageEvent $event
     * @return void
     */
    public function sendWithBasicAuth(SendMessageEvent $event)
    {
        $auth = [$event->channelSubscribe->username, decrypt($event->channelSubscribe->password)];
        $this->client->post(
            $event->channelSubscribe->subscriber->host . $event->channelSubscribe->endpoint,
            [
                'auth' => $auth,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => "application/json",
                    'Origin' => Config::get("app.url")
                ],
                'json' => $event->message->getBody(),
            ]
        );
    }

    /**
     * Send http request POST without authentication
     *
     * @param SendMessageEvent $event
     * @return void
     */
    public function sendWithoutAuth(SendMessageEvent $event)
    {
        $this->client->post(
            $event->host . $event->channelSubscribe->endpoint,
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => "application/json",
                    'Origin' => Config::get("app.url")
                ],
                'json' => $event->message->getBody(),
            ]
        );
    }

    /**
     * Send http request POST with OAuth2 authentication
     *
     * @param SendMessageEvent $event
     * @return void
     */
    public function sendWithOAuth2(SendMessageEvent $event)
    {
        $password = openssl_decrypt($event->channelSubscribe->password, 'AES256', env('APP_KEY'), $options = 0, env('CRYPT_KEY'));
        $token = $this->retrieveToken($event->host, $event->channelSubscribe->username, $password);

        $this->client->post(
            $event->host . $event->channelSubscribe->endpoint,
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'Origin' => Config::get("app.url")
                ],
                'json' => $event->message->getBody(),
            ]
        );
    }

    /**
     * Send http request GET to retrieve Bearer token
     *
     * @param string $host host url to call to retrieve access token
     * @param string $clientId Client_id to retrieve access token
     * @param string $clientSecret Client_secret to retrieve access token
     * @return string Access token
     */
    public function retrieveToken($host, $clientId, $clientSecret)
    {
        $response = $this->client->post(
            $host . 'oauth/token',
            [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => '',
                ],
            ]
        );
        return json_decode((string) $response->getBody(), true)['access_token'];
    }
}
