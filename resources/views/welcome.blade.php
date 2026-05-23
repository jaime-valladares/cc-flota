<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CC-Flota System</title>

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