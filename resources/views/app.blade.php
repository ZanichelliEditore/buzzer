<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content={{ csrf_token() }}>
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
        <link rel="icon" type="image/png" href='/favicon.ico'>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <title>Buzzer</title>

        @yield('js-localization.head')
    </head>
    <body class="bg-white">

        <div id="app">
            <app></app>
            @yield('body')
        </div>
    </body>
</html>
