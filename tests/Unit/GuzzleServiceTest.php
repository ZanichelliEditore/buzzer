<?php

namespace Tests\Unit;

use App\Events\SendMessageEvent;
use App\Http\Services\GuzzleService;
use App\Models\ChannelSubscribe;
use App\Models\Message;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use ReflectionProperty;
use Tests\TestCaseWithoutMiddleware;

class GuzzleServiceTest extends TestCaseWithoutMiddleware
{
    private const HOST = 'https://subscriber.test/';
    private const CLIENT_ID = 'client-id';
    private const CLIENT_SECRET = 'client-secret';

    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->history = [];
    }

    private function serviceWithResponses(array $responses): GuzzleService
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $service = new GuzzleService();
        (new ReflectionProperty(GuzzleService::class, 'client'))->setValue($service, new Client(['handler' => $stack]));

        return $service;
    }

    private function oauth2Event(): SendMessageEvent
    {
        $channelSubscribe = ChannelSubscribe::factory()->make([
            'subscriber_id' => 1,
            'channel_id' => 1,
            'authentication' => 'OAUTH2',
            'endpoint' => 'api/messages',
            'username' => self::CLIENT_ID,
            'password' => openssl_encrypt(self::CLIENT_SECRET, 'AES256', config('app.key'), 0, config('app.crypt_key')),
        ]);

        return new SendMessageEvent(new Message('hello'), self::HOST, $channelSubscribe, 'low', 'channel', 'subscriber');
    }

    private function tokenResponse(string $token, ?int $expiresIn = 3600): Response
    {
        $body = ['access_token' => $token];
        if ($expiresIn !== null) {
            $body['expires_in'] = $expiresIn;
        }
        return new Response(200, [], json_encode($body));
    }

    private function requestedPaths(): array
    {
        return array_map(fn($transaction) => $transaction['request']->getUri()->getPath(), $this->history);
    }

    public function testSingletonIsSharedInContainer(): void
    {
        $this->assertSame(app(GuzzleService::class), app(GuzzleService::class));
    }

    public function testOAuth2TokenIsRequestedOnceAndReused(): void
    {
        $service = $this->serviceWithResponses([
            $this->tokenResponse('token-1'),
            new Response(200),
            new Response(200),
        ]);

        $service->sendWithOAuth2($this->oauth2Event());
        $service->sendWithOAuth2($this->oauth2Event());

        $this->assertSame(['/oauth/token', '/api/messages', '/api/messages'], $this->requestedPaths());
        $this->assertSame('Bearer token-1', $this->history[1]['request']->getHeaderLine('Authorization'));
        $this->assertSame('Bearer token-1', $this->history[2]['request']->getHeaderLine('Authorization'));
    }

    public function testOAuth2TokenIsNotCachedWithoutExpiresIn(): void
    {
        $service = $this->serviceWithResponses([
            $this->tokenResponse('token-1', null),
            new Response(200),
            $this->tokenResponse('token-2', null),
            new Response(200),
        ]);

        $service->sendWithOAuth2($this->oauth2Event());
        $service->sendWithOAuth2($this->oauth2Event());

        $this->assertSame(['/oauth/token', '/api/messages', '/oauth/token', '/api/messages'], $this->requestedPaths());
    }

    public function testOAuth2TokenIsNotCachedWhenExpiresInIsShorterThanMargin(): void
    {
        $service = $this->serviceWithResponses([
            $this->tokenResponse('token-1', 30),
            new Response(200),
            $this->tokenResponse('token-2', 30),
            new Response(200),
        ]);

        $service->sendWithOAuth2($this->oauth2Event());
        $service->sendWithOAuth2($this->oauth2Event());

        $this->assertSame(['/oauth/token', '/api/messages', '/oauth/token', '/api/messages'], $this->requestedPaths());
    }

    public function testOAuth2RefreshesTokenAndRetriesOnceOn401(): void
    {
        $service = $this->serviceWithResponses([
            $this->tokenResponse('stale-token'),
            new Response(200),
            new Response(401),
            $this->tokenResponse('fresh-token'),
            new Response(200),
            new Response(200),
        ]);

        $service->sendWithOAuth2($this->oauth2Event());
        $service->sendWithOAuth2($this->oauth2Event());
        // The refreshed token is cached and reused
        $service->sendWithOAuth2($this->oauth2Event());

        $this->assertSame(
            ['/oauth/token', '/api/messages', '/api/messages', '/oauth/token', '/api/messages', '/api/messages'],
            $this->requestedPaths()
        );
        $this->assertSame('Bearer stale-token', $this->history[2]['request']->getHeaderLine('Authorization'));
        $this->assertSame('Bearer fresh-token', $this->history[4]['request']->getHeaderLine('Authorization'));
        $this->assertSame('Bearer fresh-token', $this->history[5]['request']->getHeaderLine('Authorization'));
    }

    public function testOAuth2ThrowsWhenRetryAfter401FailsAgain(): void
    {
        $service = $this->serviceWithResponses([
            $this->tokenResponse('token-1'),
            new Response(401),
            $this->tokenResponse('token-2'),
            new Response(401),
        ]);

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(401);

        try {
            $service->sendWithOAuth2($this->oauth2Event());
        } finally {
            $this->assertCount(4, $this->history);
        }
    }

    public function testOAuth2DoesNotRetryOnOtherClientErrors(): void
    {
        $service = $this->serviceWithResponses([
            $this->tokenResponse('token-1'),
            new Response(422),
        ]);

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(422);

        try {
            $service->sendWithOAuth2($this->oauth2Event());
        } finally {
            $this->assertCount(2, $this->history);
        }
    }
}
