const numero = new Intl.NumberFormat('pt-BR');
const relativo = new Intl.RelativeTimeFormat('pt-BR', { numeric: 'auto' });
const dataLongaFmt = new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const dataCurtaFmt = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
const horaFmt = new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' });

const DIA = 86_400_000;

function inicioDoDia(data) {
    const d = new Date(data);
    d.setHours(0, 0, 0, 0);
    return d;
}

/** "2026-11-20" (data sem hora) vira data local, sem deslocar o fuso. */
function dataLocal(valor) {
    if (/^\d{4}-\d{2}-\d{2}$/.test(valor)) {
        const [a, m, d] = valor.split('-').map(Number);
        return new Date(a, m - 1, d);
    }
    return new Date(valor);
}

function diasAte(valor) {
    return Math.round((inicioDoDia(dataLocal(valor)) - inicioDoDia(new Date())) / DIA);
}

export const formatarNumero = (n) => numero.format(n ?? 0);

export function dataLonga(data = new Date()) {
    const texto = dataLongaFmt.format(data);
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

export const dataCurta = (valor) => (valor ? dataCurtaFmt.format(dataLocal(valor)) : '');
export const hora = (valor) => (valor ? horaFmt.format(new Date(valor)) : '');

export function haQuantoTempo(valor) {
    if (!valor) return '';
    const dias = diasAte(valor);
    if (dias === 0) return 'hoje';
    if (Math.abs(dias) < 60) return relativo.format(dias, 'day');
    return relativo.format(Math.round(dias / 30), 'month');
}

/** Texto e tom do prazo de contato (SLA). */
export function prazo(valor) {
    if (!valor) return { texto: 'Sem prazo', tom: 'neutro' };
    const dias = diasAte(valor);
    if (dias < 0) return { texto: `Vencido há ${-dias} ${-dias === 1 ? 'dia' : 'dias'}`, tom: 'vencido' };
    if (dias === 0) return { texto: 'Vence hoje', tom: 'hoje' };
    return { texto: `Em ${dias} ${dias === 1 ? 'dia' : 'dias'}`, tom: 'neutro' };
}

export function plural(n, singular, pluralTexto) {
    return `${formatarNumero(n)} ${n === 1 ? singular : pluralTexto}`;
}
