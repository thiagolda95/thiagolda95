<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Painel do dia · Telemonitoramento</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    @vite(['resources/css/painel.css', 'resources/js/painel.js'])
</head>
<body>
    <script>window.__PAINEL__ = @json($config);</script>
    <div id="painel-app"></div>
    <noscript>
        <p class="noscript">O painel precisa de JavaScript ativado no navegador.</p>
    </noscript>
</body>
</html>
