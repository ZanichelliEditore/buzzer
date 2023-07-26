<?php

namespace Tests\Unit;

use Exception;
use Tests\TestCaseWithoutMiddleware;
use ReflectionClass;
use Illuminate\Support\Str;
use App\Http\Services\Mailer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class ServiceEmailTest extends TestCaseWithoutMiddleware
{
    protected $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = app()->make(Mailer::class);
    }

    public function testGetOauthTokenSuccess(): void
    {
        $token = Str::random();
        Http::fake([
            'oauth/token' . "*" => Http::response(["access_token" => $token], 200, [])
        ]);

        $mailerServiceReflection = new ReflectionClass(Mailer::class);
        $method = $mailerServiceReflection->getMethod("retrieveToken");
        $method->setAccessible(true);
        $response = $method->invokeArgs($this->mailer, []);

        $this->assertEquals($response, $token);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), env('URL_SENDY') . 'oauth/token');
        });
    }

    public function testGetOauthTokenException(): void
    {
        Http::fake([
            'oauth/token' . "*" => Http::response("mock", 500)
        ]);

        $this->expectException(Exception::class);

        $mailerServiceReflection = new \ReflectionClass(Mailer::class);
        $method = $mailerServiceReflection->getMethod("retrieveToken");
        $method->setAccessible(true);
        $method->invokeArgs($this->mailer, []);
    }

    public function testSendEmail(): void
    {
        Http::fake([
            'oauth/token' . "*" => Http::response(["access_token" => Str::random()], 200, []),
            'api/v1/emails' => Http::response(),
        ]);
        $body = "test";
        $this->mailer->dispatchEmail($body);

        Http::assertSentCount(2);

        Http::assertSent(function (Request $request) use ($body) {
            return $request->url() == env('URL_SENDY') . 'api/v1/emails' &&
                $request->data()['from'] == "buzzer@zanichelli.it" &&
                $request->data()['subject'] == "Alert from BUZZER" &&
                $request->data()['body'] == $body;
        });
    }
}
