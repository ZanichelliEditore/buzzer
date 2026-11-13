<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use App\Events\SendMessageEvent;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class GuzzleService
{
    /**
     * Seconds subtracted from the token expires_in when caching it
     */
    private const TOKEN_EXPIRY_MARGIN = 60;

    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => config("queue.timeout"),
            'connect_timeout' => config("queue.timeout"),
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
        $password = openssl_decrypt($event->channelSubscribe->password, 'AES256', config('app.key'), $options = 0, config('app.crypt_key'));
        $clientId = $event->channelSubscribe->username;

        try {
            $this->postWithBearer($event, $this->retrieveToken($event->host, $clientId, $password));
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() !== 401) {
                throw $e;
            }
            // The cached token may have been revoked or expired early: get a new one and retry once
            Cache::forget($this->tokenCacheKey($event->host, $clientId, $password));
            $this->postWithBearer($event, $this->retrieveToken($event->host, $clientId, $password));
        }
    }

    private function postWithBearer(SendMessageEvent $event, string $token): void
    {
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
     * Retrieve the Bearer token, reusing a cached one until it expires
     *
     * @param string $host host url to call to retrieve access token
     * @param string $clientId Client_id to retrieve access token
     * @param string $clientSecret Client_secret to retrieve access token
     * @return string Access token
     */
    public function retrieveToken($host, $clientId, $clientSecret)
    {
        $cacheKey = $this->tokenCacheKey($host, $clientId, $clientSecret);
        $token = Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        $data = $this->requestToken($host, $clientId, $clientSecret);

        // Expire the cached token a bit earlier than the real one; without expires_in we do not cache it
        $ttl = isset($data['expires_in']) ? (int) $data['expires_in'] - self::TOKEN_EXPIRY_MARGIN : 0;
        if ($ttl > 0) {
            Cache::put($cacheKey, $data['access_token'], $ttl);
        }

        return $data['access_token'];
    }

    /**
     * The secret is part of the key so a password change on the subscriber invalidates the cached token
     */
    private function tokenCacheKey(string $host, $clientId, $clientSecret): string
    {
        return config('cache.oauth_token_key_prefix') . hash('sha256', $host . '|' . $clientId . '|' . $clientSecret);
    }

    /**
     * Send http request POST to the subscriber token endpoint
     *
     * @return array decoded token response
     */
    private function requestToken($host, $clientId, $clientSecret): array
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
        return json_decode((string) $response->getBody(), true);
    }
}
