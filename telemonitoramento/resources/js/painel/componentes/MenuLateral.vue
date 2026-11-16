<script setup>
import Icone from './Icone.vue';

defineProps({
    itens: { type: Array, required: true },
    versao: { type: String, required: true },
    urlNovidades: { type: String, default: null },
});
</script>

<template>
    <section class="card menu-lateral">
        <nav aria-label="Menu principal">
            <a
                v-for="item in itens"
                :key="item.url"
                :href="item.url"
                class="menu-lateral__item"
                :class="{ 'menu-lateral__item--ativo': item.ativo }"
                :aria-current="item.ativo ? 'page' : undefined"
            >
                <Icone :nome="item.icone" />
                {{ item.rotulo }}
            </a>
        </nav>
        <div class="menu-lateral__versao">
            <span class="selo-versao"><Icone nome="info" :tamanho="16" /> Versão {{ versao }}</span>
            <a v-if="urlNovidades" :href="urlNovidades">O que mudou</a>
        </div>
    </section>
</template>
