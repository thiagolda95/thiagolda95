<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Icone from './Icone.vue';

const props = defineProps({
    usuario: { type: Object, required: true },
    logo: { type: String, default: null },
    urlSair: { type: String, required: true },
});
defineEmits(['abrir-menu']);

const menuAberto = ref(false);
const raiz = ref(null);
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function fecharAoClicarFora(evento) {
    if (raiz.value && !raiz.value.contains(evento.target)) menuAberto.value = false;
}
function fecharComEsc(evento) {
    if (evento.key === 'Escape') menuAberto.value = false;
}

onMounted(() => document.addEventListener('click', fecharAoClicarFora));
onBeforeUnmount(() => document.removeEventListener('click', fecharAoClicarFora));
</script>

<template>
    <header class="cabecalho">
        <div class="cabecalho__inicio">
            <button type="button" class="btn-icone so-mobile" aria-label="Abrir menu" @click="$emit('abrir-menu')">
                <Icone nome="menu" />
            </button>
            <span class="cabecalho__sistema">Telemonitoramento</span>
        </div>

        <div class="cabecalho__centro">
            <img v-if="logo" :src="logo" alt="Prefeitura de Manaus" class="cabecalho__logo">
        </div>

        <div class="cabecalho__fim">
            <div ref="raiz" class="usuario" @keydown="fecharComEsc">
                <button
                    type="button"
                    class="usuario__botao"
                    :aria-expanded="menuAberto"
                    aria-haspopup="menu"
                    @click="menuAberto = !menuAberto"
                >
                    <span class="usuario__textos">
                        <span class="usuario__nome">{{ props.usuario.nome }}</span>
                        <span class="usuario__papel">{{ props.usuario.papel }}</span>
                    </span>
                    <span class="usuario__avatar" aria-hidden="true">{{ props.usuario.iniciais }}</span>
                    <span class="sr-only">Menu do usuário</span>
                    <Icone nome="seta_baixo" :tamanho="20" />
                </button>
                <div v-if="menuAberto" class="usuario__menu" role="menu">
                    <form method="post" :action="urlSair">
                        <input type="hidden" name="_token" :value="csrf">
                        <button type="submit" role="menuitem">
                            <Icone nome="sair" :tamanho="20" /> Sair do sistema
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
</template>
