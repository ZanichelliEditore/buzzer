<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ScrambleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            // Cache route information
            $routeCache = [];
            foreach (Route::getRoutes() as $route) {
                $action = $route->getAction();
                if (isset($action['controller'])) {
                    [$controller, $method] = explode('@', $action['controller']);
                    $key = $controller . '@' . $method;

                    try {
                        $reflection = new \ReflectionMethod($controller, $method);
                        $filename = $reflection->getFileName();
                        $startLine = $reflection->getStartLine();
                        $endLine = $reflection->getEndLine();

                        if ($filename && $startLine && $endLine) {
                            $lines = file($filename);
                            $source = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
                            $routeCache[$key] = $source;
                        }
                    } catch (\Throwable $e) {
                        // Skip if reflection fails
                    }
                }
            }

            // Define response schemas using Type objects
            $responseSchemas = [
                'error404' => [
                    'code' => 404,
                    'description' => 'Resource not found',
                    'schema' => $this->createErrorSchema('Resource not found'),
                ],
                'error500' => [
                    'code' => 500,
                    'description' => 'Internal server error',
                    'schema' => $this->createErrorSchema('An error occurred while processing your request'),
                ],
                'success200' => [
                    'code' => 200,
                    'description' => 'Success',
                    'schema' => $this->createSuccessSchema('Operation completed successfully'),
                ],
                'success201' => [
                    'code' => 201,
                    'description' => 'Resource created',
                    'schema' => $this->createSuccessSchema('Resource created successfully'),
                ],
                'error422' => [
                    'code' => 422,
                    'description' => 'Validation error',
                    'schema' => $this->createValidationErrorSchema(),
                ],
            ];

            // Add responses to operations based on their source code
            foreach ($openApi->paths as $pathKey => $path) {
                foreach ($path->operations as $operation) {
                    $existingCodes = collect($operation->responses)->pluck('code')->toArray();

                    // Try to find matching route source code
                    $source = null;
                    $operationId = $operation->operationId ?? '';

                    foreach ($routeCache as $routeKey => $routeSource) {
                        // Extract controller and method from route key (e.g., "App\Http\Controllers\PublisherController@destroy")
                        $parts = explode('@', $routeKey);
                        if (count($parts) !== 2) {
                            continue;
                        }

                        [$controllerClass, $method] = $parts;
                        $controllerShortName = class_basename($controllerClass);

                        // Try to match with operationId patterns like "publisher.destroy"
                        $expectedOperationId = strtolower(str_replace('Controller', '', $controllerShortName)) . '.' . $method;

                        if ($operationId === $expectedOperationId) {
                            $source = $routeSource;
                            break;
                        }
                    }

                    if (!$source) {
                        continue;
                    }

                    // Check if source code contains response macro calls and add/replace corresponding responses
                    foreach ($responseSchemas as $macroName => $responseInfo) {
                        if (str_contains($source, "->$macroName(")) {
                            // Remove existing response with same code if present
                            // Handle both Response objects and Reference objects
                            $operation->responses = collect($operation->responses)
                                ->reject(function($r) use ($responseInfo) {
                                    // Check if it's a Response object with a code property
                                    if (property_exists($r, 'code')) {
                                        return $r->code === $responseInfo['code'];
                                    }
                                    return false;
                                })
                                ->values()
                                ->all();

                            // Add the correct response
                            $response = Response::make($responseInfo['code'])
                                ->description($responseInfo['description'])
                                ->setContent('application/json', $responseInfo['schema']);

                            $operation->addResponse($response);
                        }
                    }
                }
            }
        });
    }

    /**
     * Create a simple error response schema
     */
    private function createErrorSchema(string $exampleMessage): Schema
    {
        $objectType = new ObjectType();
        $messageType = (new StringType())->example($exampleMessage);
        $objectType->addProperty('message', $messageType);
        $objectType->setRequired(['message']);

        return Schema::fromType($objectType);
    }

    /**
     * Create a simple success response schema
     */
    private function createSuccessSchema(string $exampleMessage): Schema
    {
        $objectType = new ObjectType();
        $messageType = (new StringType())->example($exampleMessage);
        $objectType->addProperty('message', $messageType);

        return Schema::fromType($objectType);
    }

    /**
     * Create a validation error response schema
     */
    private function createValidationErrorSchema(): Schema
    {
        $objectType = new ObjectType();

        $messageType = (new StringType())->example('Data is invalid');
        $objectType->addProperty('message', $messageType);

        // errors is an object with arbitrary string array properties
        $errorsObjectType = new ObjectType();
        $errorsObjectType->additionalProperties = new ArrayType();
        $objectType->addProperty('errors', $errorsObjectType);

        $objectType->setRequired(['message', 'errors']);

        return Schema::fromType($objectType);
    }
}
