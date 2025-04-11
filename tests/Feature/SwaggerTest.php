<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCaseWithoutMiddleware;

class SwaggerTest extends TestCaseWithoutMiddleware
{
    public function testSwaggerGenerate()
    {
        $json = storage_path('api-docs/api-docs.json');
        if (file_exists($json)) unlink($json);

        Artisan::call("l5-swagger:generate");

        $response = $this->withMiddleware()->get('/docs?api-docs.json');
        $response->assertStatus(200);
    }

    public function testSwaggerRoute()
    {
        $response = $this->withMiddleware()->get('/api/documentation');
        $response->assertStatus(200);
    }
}
