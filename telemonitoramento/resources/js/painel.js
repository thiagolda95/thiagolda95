import { createApp } from 'vue';
import PainelApp from './painel/PainelApp.vue';

createApp(PainelApp, { config: window.__PAINEL__ }).mount('#painel-app');
