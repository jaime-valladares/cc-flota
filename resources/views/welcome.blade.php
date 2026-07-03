<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CC-Flota System</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/cc-flota/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/cc-flota/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/cc-flota/favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">CC-Flota</span>
        </div>
    </nav>

    <div class="container mt-4">
        @yield('content')
    </div>

</body>
</html>