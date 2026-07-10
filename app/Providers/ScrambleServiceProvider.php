<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ScrambleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $this->addSecuritySchemes($openApi);
            $routeMiddlewareMap = [];
            foreach (Route::getRoutes() as $route) {
                if (!str_starts_with($route->uri(), 'api/')) {
                    continue;
                }
                $action = $route->getAction();
                if (isset($action['controller'])) {
                    $parts = explode('@', $action['controller']);
                    if (count($parts) === 2) {
                        [$controller, $method] = $parts;
                        $controllerShortName = class_basename($controller);
                        $operationId = strtolower(str_replace('Controller', '', $controllerShortName)) . '.' . $method;
                        $middlewares = $route->gatherMiddleware();
                        $routeMiddlewareMap[$operationId] = $middlewares;
                    }
                }
            }

            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    $operationId = $operation->operationId ?? '';
                    $middlewares = $routeMiddlewareMap[$operationId] ?? [];

                    if (in_array('client', $middlewares)) {
                        $operation->security = [new SecurityRequirement(['passport' => []])];
                    } elseif (in_array('basicAuth', $middlewares)) {
                        $operation->security = [new SecurityRequirement(['basicAuth' => []])];
                    }
                }
            }
        });
    }

    private function addSecuritySchemes(OpenApi $openApi): void
    {
        // Add OAuth2 (Laravel Passport) security scheme
        $clientCredentialsFlow = new OAuthFlow();
        $clientCredentialsFlow->tokenUrl(config('app.url') . '/oauth/token');

        $oauth2Scheme = SecurityScheme::oauth2()
            ->as('passport')
            ->setDescription('OAuth2 client credentials flow for admin API access')
            ->flows(function ($flows) use ($clientCredentialsFlow) {
                $flows->clientCredentials($clientCredentialsFlow);
            });

        $openApi->components->addSecurityScheme('passport', $oauth2Scheme);

        $basicAuthScheme = SecurityScheme::http('basic')
            ->as('basicAuth')
            ->setDescription('Basic HTTP authentication for publishers sending messages');

        $openApi->components->addSecurityScheme('basicAuth', $basicAuthScheme);
    }
}
