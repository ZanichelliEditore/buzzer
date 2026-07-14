<?php

namespace Tests\Feature;

use Tests\TestCaseWithoutMiddleware;

class ScrambleTest extends TestCaseWithoutMiddleware
{
    public function testScrambleRoute()
    {
        $response = $this->withMiddleware()->get('docs/api');
        $response->assertStatus(200);
    }

    public function testScrambleJsonRoute(): void
    {
        $response = $this->withMiddleware()->get('docs/api');
        $response->assertStatus(200);
    }
}
