<?php

namespace Tests\Feature;

use Tests\TestCaseWithoutMiddleware;

class ScrambleTest extends TestCaseWithoutMiddleware
{
    public function testScrambleRoute()
    {
        $response = $this->withMiddleware()->get('api/documentation');
        $response->assertOk();
    }

    public function testScrambleJsonRoute(): void
    {
        $response = $this->withMiddleware()->get('docs/api.json');
        $response->assertOk();
    }
}
