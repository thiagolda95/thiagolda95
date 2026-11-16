<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import CabecalhoApp from './componentes/CabecalhoApp.vue';
import CardReserva from './componentes/CardReserva.vue';
import DialogoRemover from './componentes/DialogoRemover.vue';
import Icone from './componentes/Icone.vue';
import ListaLigacoes from './componentes/ListaLigacoes.vue';
import MenuLateral from './componentes/MenuLateral.vue';
import ProgressoDia from './componentes/ProgressoDia.vue';
import { criarApi, errosDeValidacao, mensagemDeErro } from './api.js';
import { dataLonga, hora, plural } from './formatos.js';

const props = defineProps({ config: { type: Object, required: true } });
const cfg = props.config;
const api = criarApi(cfg.rotas);

/* ---------- Estado ---------- */
const programas = ref([]);
const carregandoProgramas = ref(true);
const resumo = ref(null);
const atualizadoEm = ref(null);
const atualizando = ref(false);

const aba = ref('a_realizar');
const busca = ref('');
const resultado = ref(null);
const pagina = ref(1);
const porPagina = ref(10);
const lista = reactive({ itens: [], meta: null, carregando: true, erro: false });
const ocupados = reactive(new Set());

const reserva = reactive({ enviando: false, erros: {} });
const remocao = reactive({ alvo: null, enviando: false, erros: {} });
const gavetaAberta = ref(false);
const cardReserva = ref(null);

const contadores = computed(() => ({
    a_realizar: resumo.value?.a_realizar ?? 0,
    realizadas: (resumo.value?.concluidas ?? 0) + (resumo.value?.removidas ?? 0),
}));

/* ---------- Aviso (snackbar) ---------- */
const aviso = ref(null);
let temporizadorAviso;
function avisar(texto, { acao = null, erro = false, duracao = 6000 } = {}) {
    clearTimeout(temporizadorAviso);
    aviso.value = { texto, acao, erro };
    temporizadorAviso = setTimeout(() => (aviso.value = null), duracao);
}

/* ---------- Carregamento ---------- */
async function carregarResumo() {
    const r = await api.resumo();
    resumo.value = r.data;
    atualizadoEm.value = r.atualizado_em;
}

async function carregarProgramas() {
    try {
        programas.value = await api.programas();
    } finally {
        carregandoProgramas.value = false;
    }
}

let controladorLista;
async function carregarLista() {
    controladorLista?.abort();
    controladorLista = new AbortController();
    lista.carregando = true;
    lista.erro = false;
    try {
        const r = await api.listar(
            {
                aba: aba.value,
                busca: busca.value.trim() || undefined,
                resultado: aba.value === 'realizadas' ? resultado.value ?? undefined : undefined,
                page: pagina.value,
                por_pagina: porPagina.value,
            },
            controladorLista.signal,
        );
        // Se a página ficou vazia (ex.: último item resolvido), volta uma página.
        if (r.data.length === 0 && r.meta.current_page > 1) {
            pagina.value = r.meta.last_page;
            return;
        }
        lista.itens = r.data;
        lista.meta = r.meta;
        lista.carregando = false;
    } catch (e) {
        if (e?.code === 'ERR_CANCELED') return;
        lista.erro = true;
        lista.carregando = false;
    }
}

async function atualizarTudo() {
    atualizando.value = true;
    try {
        await Promise.all([carregarResumo(), carregarProgramas(), carregarLista()]);
    } catch (e) {
        avisar(mensagemDeErro(e, 'Não foi possível atualizar o painel.'), { erro: true });
    } finally {
        atualizando.value = false;
    }
}

watch([aba, resultado, porPagina], () => {
    if (pagina.value !== 1) pagina.value = 1;
    else carregarLista();
});
watch(pagina, carregarLista);

let temporizadorBusca;
watch(busca, () => {
    clearTimeout(temporizadorBusca);
    temporizadorBusca = setTimeout(() => {
        if (pagina.value !== 1) pagina.value = 1;
        else carregarLista();
    }, 300);
});

/* ---------- Ações ---------- */
async function reservar({ programaId, quantidade }) {
    reserva.enviando = true;
    reserva.erros = {};
    try {
        const r = await api.reservar(programaId, quantidade);
        if (r.reservados === 0) {
            avisar('Não há pacientes aguardando neste programa agora.', { erro: true });
        } else if (r.limitado_por === 'fila') {
            avisar(`Só havia ${plural(r.reservados, 'paciente disponível', 'pacientes disponíveis')}. Todos foram adicionados à sua lista.`);
        } else if (r.limitado_por === 'limite') {
            avisar(`${plural(r.reservados, 'paciente adicionado', 'pacientes adicionados')}. Você chegou ao limite de ligações a realizar.`);
        } else {
            avisar(`${plural(r.reservados, 'paciente adicionado', 'pacientes adicionados')} à sua lista.`);
        }
        busca.value = '';
        if (aba.value !== 'a_realizar') aba.value = 'a_realizar';
        await Promise.all([carregarResumo(), carregarProgramas(), carregarLista()]);
    } catch (e) {
        reserva.erros = errosDeValidacao(e) ?? {};
        if (!Object.keys(reserva.erros).length) avisar(mensagemDeErro(e), { erro: true });
    } finally {
        reserva.enviando = false;
    }
}

async function executar(item, acao, mensagem) {
    ocupados.add(item.id);
    try {
        await acao();
        avisar(mensagem, { acao: { rotulo: 'Desfazer', executar: () => desfazer(item) } });
        await Promise.all([carregarResumo(), carregarLista()]);
    } catch (e) {
        avisar(mensagemDeErro(e), { erro: true });
        await carregarLista();
    } finally {
        ocupados.delete(item.id);
    }
}

function naoAtendeu(item) {
    executar(item, () => api.naoAtendeu(item.id), `${nomeDo(item)}: registrado como não atendeu.`);
}

function abrirRemocao(item) {
    remocao.erros = {};
    remocao.alvo = item;
}

async function confirmarRemocao(dados) {
    const item = remocao.alvo;
    remocao.enviando = true;
    remocao.erros = {};
    try {
        await api.remover(item.id, dados);
        remocao.alvo = null;
        avisar(`${nomeDo(item)} foi removido da reserva.`, { acao: { rotulo: 'Desfazer', executar: () => desfazer(item) } });
        await Promise.all([carregarResumo(), carregarProgramas(), carregarLista()]);
    } catch (e) {
        remocao.erros = errosDeValidacao(e) ?? {};
        if (!Object.keys(remocao.erros).length) avisar(mensagemDeErro(e), { erro: true });
    } finally {
        remocao.enviando = false;
    }
}

async function desfazer(item) {
    aviso.value = null;
    ocupados.add(item.id);
    try {
        await api.reabrir(item.id);
        avisar(`${nomeDo(item)} voltou para suas ligações a realizar.`);
        await Promise.all([carregarResumo(), carregarProgramas(), carregarLista()]);
    } catch (e) {
        avisar(mensagemDeErro(e), { erro: true });
    } finally {
        ocupados.delete(item.id);
    }
}

function filtrarPelaLegenda(chave) {
    if (chave === 'a_realizar') {
        aba.value = 'a_realizar';
    } else {
        aba.value = 'realizadas';
        resultado.value = chave;
    }
    document.getElementById('titulo-lista')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function irParaReserva() {
    cardReserva.value?.focar();
    document.getElementById('titulo-reserva')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

const nomeDo = (item) => item.paciente.nome;

/* ---------- Ciclo de vida ---------- */
function aoVoltarParaAba() {
    if (document.visibilityState === 'visible') atualizarTudo();
}

onMounted(() => {
    atualizarTudo();
    if (cfg.aviso) avisar(cfg.aviso);
    document.addEventListener('visibilitychange', aoVoltarParaAba);
});
onBeforeUnmount(() => document.removeEventListener('visibilitychange', aoVoltarParaAba));
</script>

<template>
    <CabecalhoApp :usuario="cfg.usuario" :logo="cfg.logo" :url-sair="cfg.rotas.logout" @abrir-menu="gavetaAberta = true" />

    <!-- Menu em gaveta para telas estreitas -->
    <template v-if="gavetaAberta">
        <div class="gaveta-fundo" @click="gavetaAberta = false" />
        <aside class="gaveta" aria-label="Menu" @keydown.esc="gavetaAberta = false">
            <div class="gaveta__topo">
                Menu
                <button type="button" class="btn-icone" aria-label="Fechar menu" @click="gavetaAberta = false">
                    <Icone nome="fechar" />
                </button>
            </div>
            <MenuLateral :itens="cfg.menu" :versao="cfg.versao" :url-novidades="cfg.url_novidades" />
        </aside>
    </template>

    <div class="layout">
        <aside class="coluna-lateral">
            <CardReserva
                ref="cardReserva"
                :programas="programas"
                :carregando-programas="carregandoProgramas"
                :a-realizar="resumo?.a_realizar ?? 0"
                :limite="resumo?.limite ?? 30"
                :quantidade-padrao="cfg.quantidade_padrao"
                :atalhos="cfg.quantidades_rapidas"
                :enviando="reserva.enviando"
                :erros="reserva.erros"
                @reservar="reservar"
            />
            <MenuLateral :itens="cfg.menu" :versao="cfg.versao" :url-novidades="cfg.url_novidades" />
        </aside>

        <main class="conteudo">
            <div class="titulo-pagina">
                <div>
                    <h1>Painel do dia</h1>
                    <p class="titulo-pagina__data">{{ dataLonga() }}</p>
                </div>
                <div class="titulo-pagina__acoes">
                    <span v-if="atualizadoEm" aria-live="polite">Atualizado às {{ hora(atualizadoEm) }}</span>
                    <button type="button" class="btn btn--contorno btn--compacto" :disabled="atualizando" @click="atualizarTudo">
                        <Icone nome="atualizar" :tamanho="18" :class="{ girando: atualizando }" /> Atualizar
                    </button>
                </div>
            </div>

            <ProgressoDia :resumo="resumo" @filtrar="filtrarPelaLegenda" />

            <ListaLigacoes
                v-model:aba="aba"
                v-model:busca="busca"
                v-model:resultado="resultado"
                v-model:por-pagina="porPagina"
                :itens="lista.itens"
                :meta="lista.meta"
                :carregando="lista.carregando"
                :erro="lista.erro"
                :contadores="contadores"
                :ocupados="ocupados"
                @update:pagina="pagina = $event"
                @nao-atendeu="naoAtendeu"
                @remover="abrirRemocao"
                @desfazer="desfazer"
                @recarregar="carregarLista"
                @ir-reservar="irParaReserva"
            />
        </main>
    </div>

    <DialogoRemover
        :reserva="remocao.alvo"
        :motivos="cfg.motivos_remocao"
        :enviando="remocao.enviando"
        :erros="remocao.erros"
        @confirmar="confirmarRemocao"
        @cancelar="remocao.alvo = null"
    />

    <div role="status" aria-live="polite">
    <Transition name="aviso">
        <div v-if="aviso" class="aviso" :class="{ 'aviso--erro': aviso.erro }">
            <span>{{ aviso.texto }}</span>
            <button v-if="aviso.acao" type="button" @click="aviso.acao.executar()">{{ aviso.acao.rotulo }}</button>
            <button v-else type="button" aria-label="Fechar aviso" @click="aviso = null">OK</button>
        </div>
    </Transition>
    </div>
</template>
