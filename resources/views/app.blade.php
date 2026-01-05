<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>B2B Ecommerce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div id="app"></div>
</body>
</html>
