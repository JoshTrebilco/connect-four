<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Connect Four</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=quicksand:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gradient-to-br from-sky-100 to-blue-200">
        <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-sky-100 via-blue-50 to-teal-100 w-full">
            {{ $slot }}
        </div>
    </body>
</html>
