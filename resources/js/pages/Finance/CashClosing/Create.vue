<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CashClosingPreview, CashMovementData, LastClosing } from '@/types/Finance/CashClosing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cierre de Caja', href: '/cash-closing' },
            { title: 'Nuevo Cierre', href: '/cash-closing/create' },
        ],
    },
});

const props = defineProps<{
    preview?: CashClosingPreview;
    lastClosing?: LastClosing | null;
    selectedDate: string;
}>();

const dateValue = ref(props.selectedDate);
const openingBalance = ref(0);
const countedAmount = ref(0);
const notes = ref('');
const movements = ref<CashMovementData[]>([]);

const newMovement = ref<{
    cm_type: 'inflow' | 'outflow';
    cm_amount: number | null;
    cm_description: string;
}>({
    cm_type: 'inflow',
    cm_amount: null,
    cm_description: '',
});

function useLastClosing() {
    if (props.lastClosing) {
        openingBalance.value = Number(props.lastClosing.cc_counted_amount);
    }
}

function loadPreview() {
    router.get('/cash-closing/create', {
        cc_date: dateValue.value,
        cc_opening_balance: openingBalance.value || 0,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function addMovement() {
    if (!newMovement.value.cm_amount || newMovement.value.cm_amount <= 0 || !newMovement.value.cm_description.trim()) {
        return;
    }

    movements.value.push({
        cm_type: newMovement.value.cm_type,
        cm_amount: newMovement.value.cm_amount,
        cm_description: newMovement.value.cm_description.trim(),
        cm_reference: null,
    });

    newMovement.value = { cm_type: 'inflow', cm_amount: null, cm_description: '' };
}

function removeMovement(index: number) {
    movements.value.splice(index, 1);
}

const totalManualInflows = computed(() => {
    return movements.value
        .filter((m) => m.cm_type === 'inflow')
        .reduce((sum, m) => sum + m.cm_amount, 0);
});

const totalManualOutflows = computed(() => {
    return movements.value
        .filter((m) => m.cm_type === 'outflow')
        .reduce((sum, m) => sum + m.cm_amount, 0);
});

const expectedClosing = computed(() => {
    if (!props.preview) {
        return 0;
    }
    return openingBalance.value
        + props.preview.cc_total_sales
        + props.preview.cc_total_received
        + totalManualInflows.value
        - totalManualOutflows.value;
});

const difference = computed(() => {
    return countedAmount.value - expectedClosing.value;
});

const form = useForm({
    cc_date: props.selectedDate,
    cc_opening_balance: 0,
    cc_counted_amount: 0,
    cc_notes: '',
    movements: [] as CashMovementData[],
});

function submitForm() {
    form.cc_date = dateValue.value;
    form.cc_opening_balance = openingBalance.value;
    form.cc_counted_amount = countedAmount.value;
    form.cc_notes = notes.value;
    form.movements = movements.value;

    form.post('/cash-closing', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            openingBalance.value = 0;
            countedAmount.value = 0;
            notes.value = '';
            movements.value = [];
        },
    });
}

function formatAmount(value: number): string {
    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}
</script>

<template>
    <Head title="Nuevo Cierre de Caja" />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/cash-closing">
                        <ChevronLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <h1 class="text-foreground text-xl font-semibold">
                    Nuevo Cierre de Caja
                </h1>
            </div>
        </div>

        <!-- Fecha -->
        <Card>
            <CardHeader>
                <CardTitle>Fecha del Cierre</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex items-end gap-4">
                    <div class="max-w-xs">
                        <Label for="date">Fecha</Label>
                        <Input
                            id="date"
                            v-model="dateValue"
                            type="date"
                            class="mt-1"
                            @change="loadPreview"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Saldo Inicial -->
        <Card>
            <CardHeader>
                <CardTitle>Saldo Inicial</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex items-end gap-4">
                    <div class="max-w-xs">
                        <Label for="opening">Saldo inicial en caja</Label>
                        <Input
                            id="opening"
                            v-model.number="openingBalance"
                            type="number"
                            step="0.0001"
                            min="0"
                            class="mt-1"
                            placeholder="0.00"
                        />
                    </div>
                    <Deferred data="lastClosing">
                        <template #fallback />
                        <Button
                            v-if="lastClosing"
                            variant="outline"
                            size="sm"
                            @click="useLastClosing"
                        >
                            Usar cierre anterior ({{ formatAmount(Number(lastClosing.cc_counted_amount)) }})
                        </Button>
                    </Deferred>
                </div>
            </CardContent>
        </Card>

        <!-- Preview automático -->
        <Deferred data="preview">
            <template #fallback>
                <Card>
                    <CardContent class="flex items-center justify-center py-12">
                        <div class="bg-muted-foreground/20 size-6 animate-pulse rounded" />
                    </CardContent>
                </Card>
            </template>

            <Card v-if="preview">
                <CardHeader>
                    <CardTitle>Resumen de Transacciones del Día</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 text-left font-medium">Concepto</th>
                                    <th class="px-4 py-3 text-right font-medium">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800">
                                <tr class="hover:bg-zinc-800/30">
                                    <td class="px-4 py-3">Ventas de contado</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                        + {{ formatAmount(preview.cc_total_sales) }}
                                    </td>
                                </tr>
                                <tr class="hover:bg-zinc-800/30">
                                    <td class="px-4 py-3">Cobros en efectivo recibidos</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                        + {{ formatAmount(preview.cc_total_received) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </Deferred>

        <!-- Movimientos Manuales -->
        <Card>
            <CardHeader>
                <CardTitle>Movimientos Manuales</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <Label for="mv_type">Tipo</Label>
                        <select
                            id="mv_type"
                            v-model="newMovement.cm_type"
                            class="bg-background border-input mt-1 block w-36 rounded-lg border px-3 py-1.5 text-sm"
                        >
                            <option value="inflow">Ingreso</option>
                            <option value="outflow">Egreso</option>
                        </select>
                    </div>
                    <div class="max-w-32">
                        <Label for="mv_amount">Monto</Label>
                        <Input
                            id="mv_amount"
                            v-model.number="newMovement.cm_amount"
                            type="number"
                            step="0.0001"
                            min="0"
                            class="mt-1"
                            placeholder="0.00"
                        />
                    </div>
                    <div class="max-w-64">
                        <Label for="mv_desc">Descripción</Label>
                        <Input
                            id="mv_desc"
                            v-model="newMovement.cm_description"
                            type="text"
                            class="mt-1"
                            placeholder="¿Para qué es?"
                        />
                    </div>
                    <Button variant="secondary" size="sm" class="mt-1" @click="addMovement">
                        <Plus class="mr-1 h-3 w-3" />
                        Agregar
                    </Button>
                </div>

                <div v-if="movements.length > 0" class="mt-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-3 py-2 text-left font-medium">Tipo</th>
                                <th class="px-3 py-2 text-right font-medium">Monto</th>
                                <th class="px-3 py-2 text-left font-medium">Descripción</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-for="(mv, idx) in movements" :key="idx" class="hover:bg-zinc-800/30">
                                <td class="px-3 py-2">
                                    <span
                                        class="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                        :class="mv.cm_type === 'inflow'
                                            ? 'bg-green-900/30 text-green-400'
                                            : 'bg-red-900/30 text-red-400'"
                                    >
                                        {{ mv.cm_type === 'inflow' ? 'Ingreso' : 'Egreso' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-xs"
                                    :class="mv.cm_type === 'inflow' ? 'text-green-400' : 'text-red-400'"
                                >
                                    {{ mv.cm_type === 'inflow' ? '+' : '-' }}{{ formatAmount(mv.cm_amount) }}
                                </td>
                                <td class="px-3 py-2 text-xs text-zinc-300">{{ mv.cm_description }}</td>
                                <td class="px-3 py-2">
                                    <Button variant="ghost" size="icon" class="size-6" @click="removeMovement(idx)">
                                        <Trash2 class="h-3 w-3 text-red-400" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Cuadre -->
        <Card>
            <CardHeader>
                <CardTitle>Cuadre de Caja</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-zinc-800">
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-3 font-medium">Saldo Inicial</td>
                                <td class="px-4 py-3 text-right font-mono text-xs">{{ formatAmount(openingBalance) }}</td>
                            </tr>
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-3 font-medium text-green-400">+ Ventas de contado</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                    + {{ formatAmount(preview?.cc_total_sales ?? 0) }}
                                </td>
                            </tr>
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-3 font-medium text-green-400">+ Cobros en efectivo</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                    + {{ formatAmount(preview?.cc_total_received ?? 0) }}
                                </td>
                            </tr>
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-3 font-medium text-green-400">+ Ingresos manuales</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-green-400">
                                    + {{ formatAmount(totalManualInflows) }}
                                </td>
                            </tr>
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-3 font-medium text-red-400">- Egresos manuales</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-red-400">
                                    - {{ formatAmount(totalManualOutflows) }}
                                </td>
                            </tr>
                            <tr class="border-t-2 border-zinc-700 bg-muted/30">
                                <td class="px-4 py-3 font-bold">Esperado</td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-bold">
                                    {{ formatAmount(expectedClosing) }}
                                </td>
                            </tr>
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-3 font-medium">Saldo Contado</td>
                                <td class="px-4 py-3">
                                    <Input
                                        v-model.number="countedAmount"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        class="h-8 w-40 text-right font-mono text-xs ml-auto"
                                        placeholder="0.00"
                                    />
                                </td>
                            </tr>
                            <tr class="border-t-2 border-zinc-700 bg-muted/30">
                                <td class="px-4 py-3 font-bold">Diferencia</td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-sm font-bold"
                                    :class="difference === 0
                                        ? 'text-green-400'
                                        : difference > 0
                                            ? 'text-yellow-400'
                                            : 'text-red-400'"
                                >
                                    {{ difference >= 0 ? '+' : '' }}{{ formatAmount(difference) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-col gap-4">
                    <div class="max-w-lg">
                        <Label for="notes">Notas (opcional)</Label>
                        <textarea
                            id="notes"
                            v-model="notes"
                            class="bg-background border-input mt-1 block w-full rounded-lg border px-3 py-2 text-sm"
                            rows="3"
                            placeholder="Observaciones del cierre..."
                        />
                    </div>
                    <div class="flex justify-end">
                        <Button
                            :disabled="form.processing"
                            @click="submitForm"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            Guardar Cierre de Caja
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
