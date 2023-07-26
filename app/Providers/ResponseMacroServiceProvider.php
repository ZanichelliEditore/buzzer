<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register the application's response macros.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success200', function ($value = "", $params = []) {
            $params['content'] = $value;
            Log::info('200* ' . json_encode($params));
            if ($value) {
                return Response::make(['message' => $value], 200);
            }
            return Response::make('', 200);
        });

        Response::macro('success201', function ($value, $type, $object) {
            $response = [
                'message' => $value,
                $type => $object
            ];
            Log::info('201* ' . json_encode($response));
            return Response::make($response, 201);
        });

        Response::macro('success204', function () {
            return Response::make('', 204);
        });

        Response::macro('error401', function ($value = '') {
            $message = $value;
            Log::error('401* ' . json_encode(['content' => $message]));
            return Response::make(['message' => $message], 401);
        });

        Response::macro('error403', function ($value = '', $details = []) {
            $message = ($value ? $value : __('messages.Unauthorized'));
            Log::error('403* ' . json_encode([
                'content' => $message,
                'details' => $details
            ]));
            return Response::make(['message' => $message], 403);
        });

        Response::macro('error404', function ($value = '') {
            $message = ($value ? $value : __('messages.Object')) . __('messages.NotFound');
            Log::error('404* ' . json_encode(['content' => $message]));
            return Response::make(['message' => $message], 404);
        });

        Response::macro('error409', function ($value = "", $params = []) {
            $params['content'] = $value;
            Log::error('409* ' . json_encode($params));
            return Response::make(['message' => $value], 409);
        });

        Response::macro('error422', function ($field, $error) {

            if (!$field) {
                return Response::make(
                    [
                        'message' => 'Data is invalid',
                        'errors' => $error
                    ],
                    422
                );
            }

            return Response::make(
                [
                    'message' => 'Data is invalid',
                    'errors' => [
                        $field =>  [$error]
                    ]
                ],
                422
            );
        });

        Response::macro('error500', function ($value = '') {
            $message = $value ? $value : __('messages.SystemError');
            Log::error('500* ' . json_encode(['content' => $message]));
            return Response::make(['message' => $message], 500);
        });
    }
}
