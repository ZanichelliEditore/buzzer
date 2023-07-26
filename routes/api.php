<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::middleware(['basicAuth'])->group(function () {
    Route::post('/sendMessage', 'ChannelController@SendMessage');
    Route::post('/sendMessage/{channelName}', 'ChannelController@SendMessageToChannel');
});

Route::post('/logout-idp', 'Auth\LoginController@logoutIdp')->name('logoutIdp');

Route::middleware('client')->group(function () {
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
        Route::get('{id}', 'SubscriberController@getSubscriber')->where('id', '[0-9]+');
        Route::delete('{id}', 'SubscriberController@destroy')->where('id', '[0-9]+');

        Route::get('{id}/channels', 'ChannelSubscribeController@getChannelSubscribe')->where('id', '[0-9]+');
        Route::post('{id}/channels', 'ChannelSubscribeController@store')->where('id', '[0-9]+');
    });

    Route::delete('channel-subscriber/{id}', 'ChannelSubscribeController@destroy')->where('id', '[0-9]+');

    Route::prefix('publishers')->group(function () {
        Route::get('/', 'PublisherController@getList');
        Route::post('/', 'PublisherController@store');
        Route::get('{id}', 'PublisherController@getPublisher')->where('id', '[0-9]+');
        Route::delete('{id}', 'PublisherController@destroy')->where('id', '[0-9]+');

        Route::get('{id}/channels', 'ChannelPublishController@getChannelPublish')->where('id', '[0-9]+');
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


Route::post('/oauth', function () {
    Log::info('BUZZ!! AUTH2');
})->middleware("client");

Route::post('/basic', function () {
    Log::info('BUZZ!! BASIC');
})->middleware("basicAuthMock");

Route::post('/none', function () {
    Log::info('BUZZ!! NONE');
});

Route::post('/debug', function (Request $request) {
    Log::channel('debug')->info(json_encode($request->all()));
});
