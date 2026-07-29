<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { CashClosingRecord } from '@/types/Finance/CashClosing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cierre de Caja', href: '/cash-closing' },
            { title: 'Detalle', href: '' },
        ],
    },
});

const props = defineProps<{
    closing: CashClosingRecord;
}>();

function formatAmount(value: number): string {
    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

const statusLabel = computed(() => {
    return props.closing.cc_status === 'balanced' ? 'Cuadrado' : 'Con Diferencia';
});

const statusClass = computed(() => {
    return props.closing.cc_status === 'balanced'
        ? 'bg-green-900/30 text-green-400'
        : 'bg-red-900/30 text-red-400';
});

const movementsTotal = computed(() => {
    const inflows = props.closing.cc_total_manual_inflows;
    const outflows = props.closing.cc_total_manual_outflows;
    return { inflows, outflows };
});
</script>

<template>
    <Head :title="'Cierre de Caja - ' + closing.cc_date" />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" as-child>
                <Link href="/cash-closing">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
            </Button>
            <h1 class="text-foreground text-xl font-semibold">
                Cierre de Caja — {{ closing.cc_date }}
            </h1>
            <span
                class="inline-block rounded-full px-3 py-1 text-xs font-medium uppercase"
                :class="statusClass"
            >
                {{ statusLabel }}
            </span>
        </div>

        <!-- Resumen -->
        <Card>
            <CardHeader>
                <CardTitle>Resumen</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-zinc-800">
                            <tr>
                                <td class="px-4 py-3 font-medium">Saldo Inicial</td>
                                <td class="px-4 py-3 text-right font-mono text-xs">
                                    {{ formatAmount(closing.cc_opening_balance) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-green-400">+ Ventas de contado</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                    + {{ formatAmount(closing.cc_total_sales) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-green-400">+ Cobros en efectivo</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                    + {{ formatAmount(closing.cc_total_received) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-green-400">+ Ingresos manuales</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                    + {{ formatAmount(movementsTotal.inflows) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-red-400">- Egresos manuales</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-red-400">
                                    - {{ formatAmount(movementsTotal.outflows) }}
                                </td>
                            </tr>
                            <tr class="border-t-2 border-zinc-700 bg-muted/30">
                                <td class="px-4 py-3 font-bold">Esperado</td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-bold">
                                    {{ formatAmount(closing.cc_expected_closing) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium">Saldo Contado</td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-semibold">
                                    {{ formatAmount(closing.cc_counted_amount) }}
                                </td>
                            </tr>
                            <tr class="border-t-2 border-zinc-700 bg-muted/30">
                                <td class="px-4 py-3 font-bold">Diferencia</td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-sm font-bold"
                                    :class="closing.cc_difference === 0
                                        ? 'text-green-400'
                                        : closing.cc_difference > 0
                                            ? 'text-yellow-400'
                                            : 'text-red-400'"
                                >
                                    {{ closing.cc_difference >= 0 ? '+' : '' }}{{ formatAmount(closing.cc_difference) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="closing.cc_notes" class="mt-4 rounded-lg bg-zinc-800/50 p-4">
                    <p class="text-xs font-medium text-zinc-400 uppercase">Notas</p>
                    <p class="mt-1 text-sm text-zinc-300">{{ closing.cc_notes }}</p>
                </div>

                <div class="mt-4 flex gap-4 text-xs text-zinc-500">
                    <span>Cerrado por: <span class="text-zinc-300">{{ closing.closed_by?.name ?? '-' }}</span></span>
                    <span>Cerrado el: <span class="text-zinc-300">{{ closing.cc_closed_at }}</span></span>
                </div>
            </CardContent>
        </Card>

        <!-- Movimientos Manuales -->
        <Card v-if="closing.movements.length > 0">
            <CardHeader>
                <CardTitle>Movimientos Manuales</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left font-medium">Tipo</th>
                                <th class="px-4 py-3 text-right font-medium">Monto</th>
                                <th class="px-4 py-3 text-left font-medium">Descripción</th>
                                <th class="px-4 py-3 text-left font-medium">Referencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-for="mv in closing.movements" :key="mv.id" class="hover:bg-zinc-800/30">
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                        :class="mv.cm_type === 'inflow'
                                            ? 'bg-green-900/30 text-green-400'
                                            : 'bg-red-900/30 text-red-400'"
                                    >
                                        {{ mv.cm_type === 'inflow' ? 'Ingreso' : 'Egreso' }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-xs"
                                    :class="mv.cm_type === 'inflow' ? 'text-green-400' : 'text-red-400'"
                                >
                                    {{ mv.cm_type === 'inflow' ? '+' : '-' }}{{ formatAmount(mv.cm_amount) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-300">{{ mv.cm_description }}</td>
                                <td class="px-4 py-3 text-xs text-zinc-500">{{ mv.cm_reference ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
