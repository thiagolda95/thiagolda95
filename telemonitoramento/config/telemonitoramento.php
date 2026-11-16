<?php

return [

    /*
    | Versão exibida no rodapé do menu. Mantenha em sincronia com as notas de versão.
    */
    'versao' => env('TELEMONITORAMENTO_VERSAO', '3.43'),

    /*
    | URL das notas de versão ("Novidades"). Deixe nulo para ocultar o link.
    */
    'url_novidades' => env('TELEMONITORAMENTO_URL_NOVIDADES'),

    /*
    | Caminho público do logotipo do cabeçalho (ex.: "img/logo-prefeitura.png").
    | Quando nulo, o cabeçalho mostra apenas o nome do sistema.
    */
    'logo' => env('TELEMONITORAMENTO_LOGO'),

    /*
    | Quantas ligações "a realizar" um operador pode ter ao mesmo tempo.
    | Evita que pacientes fiquem presos na reserva de uma só pessoa.
    */
    'limite_reservas_ativas' => (int) env('TELEMONITORAMENTO_LIMITE_RESERVAS', 30),

    /*
    | Quantidade sugerida no formulário e os atalhos de quantidade.
    */
    'quantidade_padrao' => 10,
    'quantidades_rapidas' => [5, 10, 20],

    /*
    | Por quantos minutos o operador pode desfazer "Não atendeu".
    */
    'janela_desfazer_minutos' => 10,

    /*
    | Motivos para remover um paciente da reserva.
    */
    'motivos_remocao' => [
        'numero_invalido' => 'Telefone inexistente ou errado',
        'obito' => 'Óbito informado',
        'mudou_municipio' => 'Mudou de município',
        'atendido_outra_equipe' => 'Já acompanhado por outra equipe',
        'recusou' => 'Recusou o telemonitoramento',
        'outro' => 'Outro motivo',
    ],

    /*
    | Itens do menu lateral. "ambientes" limita o item a certos APP_ENV.
    */
    'menu' => [
        ['rotulo' => 'Painel do dia', 'icone' => 'painel', 'rota' => 'painel'],
        ['rotulo' => 'Monitoramento', 'icone' => 'monitoramento', 'url' => '/monitoramento'],
        ['rotulo' => 'Testes', 'icone' => 'testes', 'url' => '/testes', 'ambientes' => ['local', 'staging']],
        ['rotulo' => 'Administração', 'icone' => 'administracao', 'url' => '/administracao'],
    ],

];
