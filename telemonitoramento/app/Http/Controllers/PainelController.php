<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $menu = collect(config('telemonitoramento.menu'))
            ->filter(fn (array $item) => ! isset($item['ambientes']) || app()->environment($item['ambientes']))
            ->map(fn (array $item) => [
                'rotulo' => $item['rotulo'],
                'icone' => $item['icone'],
                'url' => isset($item['rota']) ? route($item['rota']) : url($item['url']),
                'ativo' => isset($item['rota']) && $request->routeIs($item['rota']),
            ])
            ->values();

        $nome = Str::title(Str::lower($user->name));
        $partes = explode(' ', $nome);

        return view('painel', [
            'config' => [
                'usuario' => [
                    'nome' => $nome,
                    'papel' => $user->papel ?? 'Telemonitor',
                    'iniciais' => Str::upper(Str::substr($partes[0], 0, 1).Str::substr(end($partes), 0, 1)),
                ],
                'menu' => $menu,
                'aviso' => session('aviso'),
                'versao' => config('telemonitoramento.versao'),
                'url_novidades' => config('telemonitoramento.url_novidades'),
                'logo' => config('telemonitoramento.logo') ? asset(config('telemonitoramento.logo')) : null,
                'quantidade_padrao' => config('telemonitoramento.quantidade_padrao'),
                'quantidades_rapidas' => config('telemonitoramento.quantidades_rapidas'),
                'motivos_remocao' => collect(config('telemonitoramento.motivos_remocao'))
                    ->map(fn (string $rotulo, string $valor) => ['valor' => $valor, 'rotulo' => $rotulo])
                    ->values(),
                'rotas' => [
                    'resumo' => route('api.resumo'),
                    'programas' => route('api.programas'),
                    'reservas' => route('api.reservas.index'),
                    'logout' => route('logout'),
                ],
            ],
        ]);
    }
}
