<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
$middleware = [];
$middlewareCheckRole = [];
if (env("USE_ZANICHELLI_IDP")) {
    $middleware = ['middleware' => 'idp'];
    $middlewareCheckRole = ['middleware' => 'check.role'];
}

Route::group($middleware, function () use ($middlewareCheckRole) {
    Route::get('/unauthorized', function () {
        return view('unauthorized');
    })->name('unauthorized');

    Route::get('/logout', 'Auth\LoginController@logout')->name('logout');
   
    Route::group($middlewareCheckRole, function () {
        Route::prefix('admin')->group(function () {
            Route::prefix('channels')->group(function () {
                Route::get('/', 'ChannelController@getList');
                Route::post('/', 'ChannelController@store');
                Route::get('{id}', 'ChannelController@getChannel')->where('id', '[0-9]+');
                Route::delete('{id}', 'ChannelController@destroy')->where('id', '[0-9]+');

                Route::get('{id}/publishers', 'ChannelController@getChannelPublishers')->where('id', '[0-9]+');
                Route::get('{id}/subscribers', 'ChannelController@getChannelSubscribers')->where('id', '[0-9]+');
            });

            Route::prefix('subscribers')->group(function () {
                Route::get('/', 'SubscriberController@getList');
                Route::post('/', 'SubscriberController@store');
                Route::delete('{id}', 'SubscriberController@destroy')->where('id', '[0-9]+');

                Route::post('{id}/channels', 'ChannelSubscribeController@store')->where('id', '[0-9]+');
            });

            Route::delete('channel-subscriber/{id}', 'ChannelSubscribeController@destroy')->where('id', '[0-9]+');

            Route::prefix('publishers')->group(function () {
                Route::get('/', 'PublisherController@getList');
                Route::post('/', 'PublisherController@store');
                Route::delete('{id}', 'PublisherController@destroy')->where('id', '[0-9]+');

                Route::post('{id}/channels', 'ChannelPublishController@store')->where('id', '[0-9]+');
                Route::delete('{publisher_id}/channels/{channel_id}', 'ChannelPublishController@destroy')->where('publisher_id', '[0-9]+')->where('channel_id', '[0-9]+');

            });

            Route::prefix('failedJobs')->group(function () {
                Route::get('/', 'FailedJobController@getList');
                Route::delete('{id}', 'FailedJobController@destroy')->where('id', '[0-9]+');
                Route::delete('all', 'FailedJobController@destroyAll');

                Route::get('retry/{id}', 'FailedJobController@retryJob')->where('id', '[0-9]+');
                Route::get('retry/all', 'FailedJobController@retryAll');
            });
        });

        Route::get('/{any}', function () {
            return view('app');
        })->where("any", ".*");
    });
});

Route::get('/login', function () {
    return redirect(env('IDP_URL', 'https://idp.zanichelli.it') . '?' . http_build_query([
        'redirect' => config('app.url')
    ]));
})->name('login');
