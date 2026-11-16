import axios from 'axios';

/**
 * Cliente HTTP do painel. A sessão e a proteção CSRF são as da aplicação web:
 * o axios envia o cookie XSRF-TOKEN automaticamente em requisições same-origin.
 */
const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

export function criarApi(rotas) {
    const reserva = (id, sufixo = '') => `${rotas.reservas}/${id}${sufixo}`;

    return {
        resumo: () => http.get(rotas.resumo).then((r) => r.data),
        programas: () => http.get(rotas.programas).then((r) => r.data.data),
        listar: (params, signal) => http.get(rotas.reservas, { params, signal }).then((r) => r.data),
        reservar: (programaId, quantidade) =>
            http.post(rotas.reservas, { programa_id: programaId, quantidade }).then((r) => r.data.data),
        naoAtendeu: (id) => http.post(reserva(id, '/nao-atendeu')).then((r) => r.data.data),
        remover: (id, dados) => http.delete(reserva(id), { data: dados }).then((r) => r.data.data),
        reabrir: (id) => http.post(reserva(id, '/reabrir')).then((r) => r.data.data),
    };
}

/** Extrai as mensagens de validação (422) por campo. */
export function errosDeValidacao(erro) {
    return erro?.response?.status === 422 ? erro.response.data.errors ?? {} : null;
}

export function mensagemDeErro(erro, padrao = 'Não foi possível concluir. Tente novamente.') {
    const erros = errosDeValidacao(erro);
    if (erros) return Object.values(erros).flat()[0] ?? padrao;
    if (erro?.response?.status === 419) return 'Sua sessão expirou. Recarregue a página.';
    if (erro?.response?.status === 403) return 'Você não tem permissão para esta ação.';
    if (!erro?.response) return 'Sem conexão com o servidor. Verifique a internet e tente de novo.';
    return padrao;
}
