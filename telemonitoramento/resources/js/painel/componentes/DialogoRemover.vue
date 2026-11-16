<script setup>
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    reserva: { type: Object, default: null },
    motivos: { type: Array, required: true },
    enviando: { type: Boolean, default: false },
    erros: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['confirmar', 'cancelar']);

const dialogo = ref(null);
const motivo = ref('');
const observacao = ref('');
const tentou = ref(false);

watch(
    () => props.reserva,
    async (r) => {
        if (r) {
            motivo.value = '';
            observacao.value = '';
            tentou.value = false;
            await nextTick();
            dialogo.value?.showModal();
        } else {
            dialogo.value?.close();
        }
    },
);

const erroMotivo = computed(() => props.erros.motivo?.[0] ?? (tentou.value && !motivo.value ? 'Escolha o motivo da remoção.' : null));
const erroObservacao = computed(
    () => props.erros.observacao?.[0] ?? (tentou.value && motivo.value === 'outro' && !observacao.value.trim() ? 'Descreva o motivo.' : null),
);

function confirmar() {
    tentou.value = true;
    if (erroMotivo.value || erroObservacao.value) return;
    emit('confirmar', { motivo: motivo.value, observacao: observacao.value.trim() || null });
}
</script>

<template>
    <dialog ref="dialogo" class="dialogo" aria-labelledby="dialogo-titulo" @cancel.prevent="emit('cancelar')">
        <form v-if="reserva" method="dialog" novalidate @submit.prevent="confirmar">
            <h2 id="dialogo-titulo" class="dialogo__titulo">Remover da reserva</h2>
            <div class="dialogo__corpo">
                <p>{{ reserva.paciente.nome }} sai da sua lista de hoje. O motivo fica registrado no histórico do paciente.</p>

                <fieldset class="opcoes" :aria-describedby="erroMotivo ? 'motivo-erro' : undefined">
                    <legend>Motivo <span class="obrigatorio" aria-hidden="true">*</span></legend>
                    <label v-for="m in motivos" :key="m.valor" class="opcao">
                        <input v-model="motivo" type="radio" name="motivo" :value="m.valor">
                        {{ m.rotulo }}
                    </label>
                </fieldset>
                <p v-if="erroMotivo" id="motivo-erro" class="campo__erro">{{ erroMotivo }}</p>

                <div v-if="motivo === 'outro'" class="campo" :class="{ 'campo--erro': erroObservacao }" style="margin-top: 12px">
                    <label class="campo__rotulo" for="observacao">Descreva o motivo</label>
                    <textarea id="observacao" v-model="observacao" class="campo__controle" maxlength="500" />
                    <p v-if="erroObservacao" class="campo__erro">{{ erroObservacao }}</p>
                </div>
            </div>
            <div class="dialogo__acoes">
                <button type="button" class="btn btn--texto" @click="emit('cancelar')">Cancelar</button>
                <button type="submit" class="btn btn--perigo" :disabled="enviando">
                    {{ enviando ? 'Removendo…' : 'Remover da reserva' }}
                </button>
            </div>
        </form>
    </dialog>
</template>
