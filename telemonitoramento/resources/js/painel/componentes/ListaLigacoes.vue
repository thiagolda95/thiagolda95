<script setup>
import { computed, ref } from 'vue';
import Icone from './Icone.vue';
import { dataCurta, formatarNumero, haQuantoTempo, hora, prazo } from '../formatos.js';

const props = defineProps({
    aba: { type: String, required: true },
    itens: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    carregando: { type: Boolean, default: false },
    erro: { type: Boolean, default: false },
    busca: { type: String, default: '' },
    resultado: { type: String, default: null },
    porPagina: { type: Number, default: 10 },
    contadores: { type: Object, default: () => ({ a_realizar: 0, realizadas: 0 }) },
    ocupados: { type: Object, default: () => new Set() },
});

const emit = defineEmits([
    'update:aba', 'update:busca', 'update:resultado', 'update:pagina', 'update:porPagina',
    'nao-atendeu', 'remover', 'desfazer', 'recarregar', 'ir-reservar',
]);

const abas = [
    { chave: 'a_realizar', rotulo: 'Ligações a realizar' },
    { chave: 'realizadas', rotulo: 'Realizadas hoje' },
];
const filtros = [
    { valor: null, rotulo: 'Todas' },
    { valor: 'realizada', rotulo: 'Realizadas' },
    { valor: 'realizada_alta', rotulo: 'Com alta' },
    { valor: 'nao_atendida', rotulo: 'Não atenderam' },
    { valor: 'removida', rotulo: 'Removidos' },
];

const botoesAba = ref([]);
function moverAba(evento, indice) {
    const destino = evento.key === 'ArrowRight' ? indice + 1 : evento.key === 'ArrowLeft' ? indice - 1 : null;
    if (destino === null) return;
    const i = (destino + abas.length) % abas.length;
    emit('update:aba', abas[i].chave);
    botoesAba.value[i]?.focus();
}

const intervalo = computed(() => {
    const m = props.meta;
    if (!m || !m.total) return 'Nenhum registro';
    return `Mostrando ${m.from}–${m.to} de ${formatarNumero(m.total)}`;
});

const vazio = computed(() => !props.carregando && !props.erro && props.itens.length === 0);
const filtrando = computed(() => props.busca.trim() !== '' || !!props.resultado);
</script>

<template>
    <section class="card" aria-labelledby="titulo-lista">
        <div class="card__titulo">
            <Icone nome="telefone" />
            <h2 id="titulo-lista">Minhas ligações</h2>
        </div>

        <div class="abas" role="tablist" aria-label="Situação das ligações">
            <button
                v-for="(a, i) in abas"
                :id="`aba-${a.chave}`"
                :key="a.chave"
                :ref="(el) => (botoesAba[i] = el)"
                type="button"
                role="tab"
                class="aba"
                :aria-selected="aba === a.chave"
                :tabindex="aba === a.chave ? 0 : -1"
                aria-controls="painel-ligacoes"
                @click="emit('update:aba', a.chave)"
                @keydown="moverAba($event, i)"
            >
                {{ a.rotulo }}
                <span class="contador">{{ formatarNumero(contadores[a.chave]) }}</span>
            </button>
        </div>

        <div id="painel-ligacoes" role="tabpanel" :aria-labelledby="`aba-${aba}`">
            <div class="barra-ferramentas">
                <div class="busca">
                    <Icone nome="busca" :tamanho="20" />
                    <label for="busca" class="sr-only">Buscar paciente</label>
                    <input
                        id="busca"
                        class="campo__controle"
                        type="search"
                        :value="busca"
                        placeholder="Buscar por nome ou CPF"
                        autocomplete="off"
                        @input="emit('update:busca', $event.target.value)"
                    >
                    <button v-if="busca" type="button" class="btn-icone" aria-label="Limpar busca" @click="emit('update:busca', '')">
                        <Icone nome="fechar" :tamanho="18" />
                    </button>
                </div>
                <div v-if="aba === 'realizadas'" class="filtros" role="group" aria-label="Filtrar por resultado">
                    <button
                        v-for="f in filtros"
                        :key="f.rotulo"
                        type="button"
                        class="chip-opcao"
                        :aria-pressed="resultado === f.valor"
                        @click="emit('update:resultado', f.valor)"
                    >
                        {{ f.rotulo }}
                    </button>
                </div>
            </div>

            <div v-if="erro" class="alerta-erro" role="alert">
                Não foi possível carregar as ligações.
                <button type="button" class="btn btn--contorno btn--compacto" @click="emit('recarregar')">Tentar de novo</button>
            </div>

            <!-- Estado vazio -->
            <div v-if="vazio" class="estado-vazio">
                <Icone :nome="aba === 'a_realizar' && !filtrando ? 'caixa_vazia' : 'busca'" :tamanho="48" />
                <template v-if="busca.trim()">
                    <h3>Nenhum paciente encontrado</h3>
                    <p>Não há ligações para “{{ busca }}” nesta aba.</p>
                    <button type="button" class="btn btn--contorno" @click="emit('update:busca', '')">Limpar busca</button>
                </template>
                <template v-else-if="aba === 'a_realizar'">
                    <h3>Nenhuma ligação a realizar</h3>
                    <p>Escolha o programa e a quantidade em “Reservar pacientes” para montar sua lista do dia.</p>
                    <button type="button" class="btn btn--primario" @click="emit('ir-reservar')">Reservar pacientes</button>
                </template>
                <template v-else-if="resultado">
                    <h3>Nada com este resultado hoje</h3>
                    <button type="button" class="btn btn--contorno" @click="emit('update:resultado', null)">Ver todas</button>
                </template>
                <template v-else>
                    <h3>Nenhuma ligação concluída hoje</h3>
                    <p>As ligações que você registrar aparecem aqui, com o resultado e o horário.</p>
                </template>
            </div>

            <!-- Tabela -->
            <div v-else class="tabela-caixa" :aria-busy="carregando">
                <table class="tabela">
                    <caption class="sr-only">
                        {{ aba === 'a_realizar' ? 'Ligações a realizar, na ordem da fila' : 'Ligações concluídas hoje' }}
                    </caption>
                    <thead v-if="aba === 'a_realizar'">
                        <tr>
                            <th scope="col">Prioridade</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Telefone</th>
                            <th scope="col">Programa</th>
                            <th scope="col">Último acompanhamento</th>
                            <th scope="col">Prazo</th>
                            <th scope="col" class="tabela__acoes">Ações</th>
                        </tr>
                    </thead>
                    <thead v-else>
                        <tr>
                            <th scope="col">Paciente</th>
                            <th scope="col">Telefone</th>
                            <th scope="col">Programa</th>
                            <th scope="col">Resultado</th>
                            <th scope="col">Horário</th>
                            <th scope="col" class="tabela__acoes">Ações</th>
                        </tr>
                    </thead>

                    <tbody v-if="carregando && itens.length === 0">
                        <tr v-for="n in 4" :key="n">
                            <td :colspan="aba === 'a_realizar' ? 7 : 6"><div class="esqueleto" :style="{ width: `${90 - n * 12}%` }" /></td>
                        </tr>
                    </tbody>

                    <tbody v-else-if="aba === 'a_realizar'">
                        <tr v-for="r in itens" :key="r.id">
                            <td data-rotulo="Prioridade">
                                <span class="etiqueta" :class="`etiqueta--${r.paciente.prioridade}`">{{ r.paciente.prioridade_rotulo }}</span>
                            </td>
                            <td class="td-paciente">
                                <div class="paciente__nome">{{ r.paciente.nome }}</div>
                                <div class="paciente__detalhe">{{ r.paciente.idade }} anos · CPF {{ r.paciente.cpf }}</div>
                            </td>
                            <td data-rotulo="Telefone">
                                <a class="telefone" :href="r.paciente.telefone_link">
                                    <Icone nome="telefone" :tamanho="16" />{{ r.paciente.telefone }}
                                </a>
                            </td>
                            <td data-rotulo="Programa">{{ r.paciente.programa }}</td>
                            <td data-rotulo="Último acompanhamento">
                                <template v-if="r.paciente.ultimo_acompanhamento_em">
                                    {{ dataCurta(r.paciente.ultimo_acompanhamento_em) }}
                                    <div class="apoio">{{ haQuantoTempo(r.paciente.ultimo_acompanhamento_em) }}</div>
                                </template>
                                <span v-else class="apoio">Primeiro contato</span>
                            </td>
                            <td data-rotulo="Prazo">
                                <span :class="`prazo--${prazo(r.paciente.proximo_contato_em).tom}`">
                                    {{ prazo(r.paciente.proximo_contato_em).texto }}
                                </span>
                            </td>
                            <td class="tabela__acoes">
                                <div class="acoes">
                                    <a class="btn btn--primario btn--compacto" :href="r.url_atendimento">
                                        <Icone nome="telefone" :tamanho="16" /> Atender
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn--contorno btn--compacto"
                                        :disabled="ocupados.has(r.id)"
                                        @click="emit('nao-atendeu', r)"
                                    >
                                        Não atendeu
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn--perigo-texto btn--compacto"
                                        :disabled="ocupados.has(r.id)"
                                        :aria-label="`Remover ${r.paciente.nome} da reserva`"
                                        @click="emit('remover', r)"
                                    >
                                        Remover
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr v-for="r in itens" :key="r.id">
                            <td class="td-paciente">
                                <div class="paciente__nome">{{ r.paciente.nome }}</div>
                                <div class="paciente__detalhe">{{ r.paciente.idade }} anos · CPF {{ r.paciente.cpf }}</div>
                            </td>
                            <td data-rotulo="Telefone">
                                <a class="telefone" :href="r.paciente.telefone_link">
                                    <Icone nome="telefone" :tamanho="16" />{{ r.paciente.telefone }}
                                </a>
                            </td>
                            <td data-rotulo="Programa">{{ r.paciente.programa }}</td>
                            <td data-rotulo="Resultado">
                                <span class="etiqueta" :class="`etiqueta--${r.status}`">{{ r.status_rotulo }}</span>
                                <div v-if="r.motivo_remocao" class="apoio">{{ r.motivo_remocao }}</div>
                            </td>
                            <td data-rotulo="Horário">{{ hora(r.finalizada_em) }}</td>
                            <td class="tabela__acoes">
                                <div class="acoes">
                                    <button
                                        v-if="r.pode_desfazer && ['nao_atendida', 'removida'].includes(r.status)"
                                        type="button"
                                        class="btn btn--texto btn--compacto"
                                        :disabled="ocupados.has(r.id)"
                                        @click="emit('desfazer', r)"
                                    >
                                        Desfazer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta && meta.total > 0" class="rodape-tabela">
                <span aria-live="polite">{{ intervalo }}</span>
                <div class="paginacao">
                    <label for="por-pagina">Por página</label>
                    <select id="por-pagina" :value="porPagina" @change="emit('update:porPagina', Number($event.target.value))">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <button
                        type="button"
                        class="btn-icone"
                        aria-label="Página anterior"
                        :disabled="meta.current_page <= 1"
                        @click="emit('update:pagina', meta.current_page - 1)"
                    >
                        <Icone nome="anterior" />
                    </button>
                    <span>{{ meta.current_page }} de {{ meta.last_page }}</span>
                    <button
                        type="button"
                        class="btn-icone"
                        aria-label="Próxima página"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="emit('update:pagina', meta.current_page + 1)"
                    >
                        <Icone nome="proximo" />
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
