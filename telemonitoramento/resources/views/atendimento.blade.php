<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atendimento · Telemonitoramento</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    @vite(['resources/css/painel.css'])
</head>
<body>
<header class="cabecalho">
    <div class="cabecalho__inicio">
        <span class="cabecalho__sistema">Telemonitoramento</span>
    </div>
</header>
<main class="atendimento">
    <a class="link-voltar" href="{{ route('painel') }}">← Voltar ao painel</a>
    <section class="card">
        <div class="card__titulo"><h1>Atendimento de {{ $reserva->paciente->nome }}</h1></div>
        <div class="card__corpo atendimento__corpo">
            <p class="texto-apoio">
                Ponto de integração: aqui entra o formulário de atendimento (SOAP) que o sistema já usa.
                Esta tela mínima só registra o resultado da ligação para fechar o ciclo no painel.
            </p>
            <form method="post" action="{{ route('atendimentos.store', $reserva) }}" class="atendimento__acoes">
                @csrf
                <button class="btn btn--primario" name="resultado" value="realizada">Registrar ligação realizada</button>
                <button class="btn btn--contorno" name="resultado" value="realizada_alta">Registrar com alta do programa</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
