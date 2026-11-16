<script setup>
import { computed } from 'vue';
import { formatarNumero } from '../formatos.js';

const props = defineProps({
    resumo: { type: Object, default: null },
});
defineEmits(['filtrar']);

const segmentos = computed(() => {
    const r = props.resumo;
    if (!r || r.total === 0) return [];
    return [
        { chave: 'realizada', valor: r.realizadas },
        { chave: 'realizada_alta', valor: r.realizadas_alta },
        { chave: 'nao_atendida', valor: r.nao_atendidas },
    ]
        .filter((s) => s.valor > 0)
        .map((s) => ({ ...s, largura: `${(s.valor / r.total) * 100}%` }));
});

const percentual = computed(() => {
    const r = props.resumo;
    return r && r.total > 0 ? Math.round((r.concluidas / r.total) * 100) : 0;
});

const itens = computed(() => {
    const r = props.resumo ?? {};
    return [
        { chave: 'a_realizar', rotulo: 'A realizar', valor: r.a_realizar ?? 0 },
        { chave: 'realizada', rotulo: 'Realizadas', valor: r.realizadas ?? 0 },
        { chave: 'realizada_alta', rotulo: 'Realizadas com alta', valor: r.realizadas_alta ?? 0 },
        { chave: 'nao_atendida', rotulo: 'Não atenderam', valor: r.nao_atendidas ?? 0 },
        { chave: 'removida', rotulo: 'Removidos', valor: r.removidas ?? 0 },
    ];
});
</script>

<template>
    <section class="card" aria-labelledby="titulo-progresso">
        <div class="card__titulo">
            <h2 id="titulo-progresso">Meu progresso hoje</h2>
        </div>
        <div class="card__corpo">
            <template v-if="resumo">
                <div class="progresso__cabecalho">
                    <p class="progresso__numero" style="margin: 0">
                        <strong>{{ formatarNumero(resumo.concluidas) }}</strong>
                        de {{ formatarNumero(resumo.total) }} ligações concluídas
                    </p>
                    <span class="progresso__pct">{{ percentual }}%</span>
                </div>
                <div
                    class="barra"
                    role="progressbar"
                    aria-label="Ligações concluídas hoje"
                    :aria-valuenow="percentual"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-valuetext="`${resumo.concluidas} de ${resumo.total} ligações concluídas`"
                >
                    <span
                        v-for="s in segmentos"
                        :key="s.chave"
                        class="barra__seg"
                        :class="`barra__seg--${s.chave}`"
                        :style="{ width: s.largura }"
                    />
                </div>
                <p v-if="resumo.total === 0" class="progresso__vazio">
                    Nenhuma ligação reservada hoje. Reserve pacientes para começar.
                </p>

                <div class="legenda">
                    <button
                        v-for="item in itens"
                        :key="item.chave"
                        type="button"
                        class="legenda__item"
                        :aria-label="`Ver ${item.rotulo.toLowerCase()}: ${item.valor}`"
                        @click="$emit('filtrar', item.chave)"
                    >
                        <span class="legenda__rotulo"><span class="ponto" :class="`ponto--${item.chave}`" /> {{ item.rotulo }}</span>
                        <span class="legenda__valor">{{ formatarNumero(item.valor) }}</span>
                    </button>
                </div>
            </template>
            <template v-else>
                <div class="esqueleto" style="width: 40%; height: 24px; margin-bottom: 14px" />
                <div class="esqueleto" style="height: 14px" />
            </template>
        </div>
    </section>
</template>
