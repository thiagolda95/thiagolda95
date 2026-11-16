<script setup>
import { computed, ref, watch } from 'vue';
import Icone from './Icone.vue';
import { formatarNumero } from '../formatos.js';

const props = defineProps({
    programas: { type: Array, default: () => [] },
    carregandoProgramas: { type: Boolean, default: false },
    aRealizar: { type: Number, default: 0 },
    limite: { type: Number, default: 30 },
    quantidadePadrao: { type: Number, default: 10 },
    atalhos: { type: Array, default: () => [5, 10, 20] },
    enviando: { type: Boolean, default: false },
    erros: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['reservar']);

const CHAVE_PROGRAMA = 'telemonitoramento.programa';
const programaId = ref(lerPreferencia());
const quantidade = ref(props.quantidadePadrao);
const tentouEnviar = ref(false);
const campoPrograma = ref(null);

function lerPreferencia() {
    try {
        return Number(localStorage.getItem(CHAVE_PROGRAMA)) || '';
    } catch {
        return '';
    }
}

// Seleciona automaticamente quando só há um programa, ou recupera o último usado.
watch(
    () => props.programas,
    (lista) => {
        if (lista.length === 1) programaId.value = lista[0].id;
        else if (programaId.value && !lista.some((p) => p.id === programaId.value)) programaId.value = '';
    },
    { immediate: true },
);

watch(programaId, (id) => {
    try {
        if (id) localStorage.setItem(CHAVE_PROGRAMA, String(id));
    } catch {
        /* armazenamento indisponível: segue sem lembrar */
    }
});

const programaEscolhido = computed(() => props.programas.find((p) => p.id === programaId.value));
const vagas = computed(() => Math.max(0, props.limite - props.aRealizar));
const maximo = computed(() => Math.max(1, Math.min(vagas.value, programaEscolhido.value?.disponiveis ?? vagas.value)));

watch(maximo, (m) => {
    if (quantidade.value > m) quantidade.value = m;
});

const erroPrograma = computed(
    () => props.erros.programa_id?.[0] ?? (tentouEnviar.value && !programaId.value ? 'Escolha o programa.' : null),
);
const erroQuantidade = computed(() => props.erros.quantidade?.[0] ?? null);
const semDisponiveis = computed(() => programaEscolhido.value && programaEscolhido.value.disponiveis === 0);
const bloqueado = computed(() => vagas.value === 0 || semDisponiveis.value || props.enviando);

const rotuloBotao = computed(() => {
    if (props.enviando) return 'Reservando…';
    return `Reservar ${quantidade.value} ${quantidade.value === 1 ? 'paciente' : 'pacientes'}`;
});

function ajustar(delta) {
    quantidade.value = Math.min(maximo.value, Math.max(1, (Number(quantidade.value) || 0) + delta));
}

function normalizar() {
    const n = Math.round(Number(quantidade.value));
    quantidade.value = Number.isFinite(n) ? Math.min(maximo.value, Math.max(1, n)) : props.quantidadePadrao;
}

function enviar() {
    tentouEnviar.value = true;
    normalizar();
    if (!programaId.value) {
        campoPrograma.value?.focus();
        return;
    }
    emit('reservar', { programaId: programaId.value, quantidade: quantidade.value });
}

defineExpose({ focar: () => campoPrograma.value?.focus() });
</script>

<template>
    <section class="card" aria-labelledby="titulo-reserva">
        <div class="card__titulo">
            <Icone nome="pessoas" />
            <h2 id="titulo-reserva">Reservar pacientes</h2>
        </div>

        <form class="card__corpo" novalidate @submit.prevent="enviar">
            <div class="campo" :class="{ 'campo--erro': erroPrograma }">
                <label class="campo__rotulo" for="programa">Programa <span class="obrigatorio" aria-hidden="true">*</span></label>
                <select
                    id="programa"
                    ref="campoPrograma"
                    v-model="programaId"
                    class="campo__controle"
                    required
                    :disabled="carregandoProgramas"
                    :aria-invalid="!!erroPrograma"
                    :aria-describedby="erroPrograma ? 'programa-erro' : 'programa-ajuda'"
                >
                    <option value="" disabled>{{ carregandoProgramas ? 'Carregando programas…' : 'Escolha o programa' }}</option>
                    <option v-for="p in programas" :key="p.id" :value="p.id">
                        {{ p.sigla }} · {{ formatarNumero(p.disponiveis) }} na fila
                    </option>
                </select>
                <p v-if="erroPrograma" id="programa-erro" class="campo__erro">{{ erroPrograma }}</p>
                <p v-else-if="semDisponiveis" id="programa-ajuda" class="campo__ajuda">Não há pacientes aguardando neste programa agora.</p>
                <p v-else-if="programaEscolhido" id="programa-ajuda" class="campo__ajuda">{{ programaEscolhido.nome }}</p>
                <p v-else id="programa-ajuda" class="campo__ajuda">O número mostra quantos pacientes aguardam ligação.</p>
            </div>

            <div class="campo" :class="{ 'campo--erro': erroQuantidade }">
                <label class="campo__rotulo" for="quantidade">Quantidade de pacientes <span class="obrigatorio" aria-hidden="true">*</span></label>
                <div class="quantidade">
                    <div class="stepper">
                        <button type="button" aria-label="Diminuir quantidade" :disabled="quantidade <= 1" @click="ajustar(-1)">
                            <Icone nome="menos" :tamanho="20" />
                        </button>
                        <input
                            id="quantidade"
                            v-model.number="quantidade"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            :max="maximo"
                            :aria-invalid="!!erroQuantidade"
                            aria-describedby="quantidade-ajuda"
                            @blur="normalizar"
                        >
                        <button type="button" aria-label="Aumentar quantidade" :disabled="quantidade >= maximo" @click="ajustar(1)">
                            <Icone nome="mais" :tamanho="20" />
                        </button>
                    </div>
                    <div class="atalhos" role="group" aria-label="Quantidades comuns">
                        <button
                            v-for="n in atalhos"
                            :key="n"
                            type="button"
                            class="chip-opcao"
                            :aria-pressed="quantidade === n"
                            :disabled="n > maximo"
                            @click="quantidade = n"
                        >
                            {{ n }}
                        </button>
                    </div>
                </div>
                <p v-if="erroQuantidade" class="campo__erro">{{ erroQuantidade }}</p>
                <p id="quantidade-ajuda" class="campo__ajuda">
                    <template v-if="vagas === 0">
                        Você chegou ao limite de {{ limite }} ligações a realizar. Conclua algumas para reservar mais.
                    </template>
                    <template v-else>
                        Você tem {{ aRealizar }} a realizar e pode reservar mais {{ vagas }} (limite {{ limite }}).
                    </template>
                </p>
            </div>

            <p class="nota">
                <Icone nome="info" :tamanho="18" />
                <span>Os pacientes vêm na ordem da fila: prioridade Alta, Média e Baixa, e depois o prazo de contato.</span>
            </p>

            <button type="submit" class="btn btn--primario btn--bloco" :disabled="bloqueado">
                <Icone :nome="enviando ? 'carregando' : 'mais'" :tamanho="20" :class="{ girando: enviando }" />
                {{ rotuloBotao }}
            </button>
        </form>
    </section>
</template>
