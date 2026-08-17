<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ChevronLeft, Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CashClosingBreakdown from '@/components/CashClosing/CashClosingBreakdown.vue';
import CashClosingCountTable from '@/components/CashClosing/CashClosingCountTable.vue';
import CashClosingMovements from '@/components/CashClosing/CashClosingMovements.vue';
import CashClosingStatusBadge from '@/components/CashClosing/CashClosingStatusBadge.vue';
import CashClosingSummary from '@/components/CashClosing/CashClosingSummary.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCashCount } from '@/composables/Finance/useCashCount';
import {
    currencyLabels,
    formatAmount,
    formatCoinValue,
} from '@/composables/Finance/useCoinFormat';
import type {
    CashClosingPreview,
    CashMovementData,
    CountedDetail,
    LastClosing,
} from '@/types/Finance/CashClosing';

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
const notes = ref('');
const movements = ref<CashMovementData[]>([]);

const previewRef = computed(() => props.preview);
const {
    countedRows,
    totalsByCoin,
    expectedByCoin,
    differencesByCoin,
    hasDifferences,
    resetCount,
    buildCountPayload,
} = useCashCount(previewRef);

const form = useForm({
    cc_date: props.selectedDate,
    cc_opening_balance: 0,
    cc_notes: '',
    movements: [] as CashMovementData[],
    cc_counted_details: [] as CountedDetail[],
    cc_differences: {} as Record<string, number>,
});

watch(
    () => props.lastClosing,
    (last) => {
        if (last && openingBalance.value === 0) {
            openingBalance.value = Number(last.cc_counted_amount);
        }
    },
    { immediate: true },
);

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

const coinList = computed(() => Object.keys(expectedByCoin.value));

function diffToneClass(coin: string): string {
    const diff = differencesByCoin.value[coin] ?? 0;

    if (diff === 0) {
        return 'text-zinc-100';
    }

    return diff > 0 ? 'text-yellow-400' : 'text-red-400';
}

function loadPreview() {
    router.get(
        '/cash-closing/create',
        {
            cc_date: dateValue.value,
            cc_opening_balance: openingBalance.value || 0,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function useLastClosing() {
    if (props.lastClosing) {
        openingBalance.value = Number(props.lastClosing.cc_counted_amount);
    }
}

function submitForm() {
    form.cc_date = dateValue.value;
    form.cc_opening_balance = openingBalance.value;
    form.cc_notes = notes.value;
    form.movements = movements.value;

    const payload = buildCountPayload();
    form.cc_counted_details = payload.cc_counted_details;
    form.cc_differences = payload.cc_differences;

    form.post('/cash-closing', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            openingBalance.value = 0;
            notes.value = '';
            movements.value = [];
            resetCount();
        },
    });
}
</script>

<template>
    <Head title="Nuevo Cierre de Caja" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" as-child>
                <Link href="/cash-closing">
                    <ChevronLeft class="size-4" aria-hidden="true" />
                </Link>
            </Button>
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Nuevo Cierre de Caja
                </h1>
                <p class="mt-0.5 text-sm text-zinc-500">
                    Registra el conteo físico del día y verifica el cuadre de
                    caja.
                </p>
            </div>
        </div>

        <div
            v-if="Object.keys(form.errors).length > 0"
            role="alert"
            class="motion-safe:animate-in motion-safe:duration-200 motion-safe:fade-in"
        >
            <Alert variant="destructive">
                <AlertCircle class="size-4" aria-hidden="true" />
                <AlertTitle>Hay problemas con el envío</AlertTitle>
                <AlertDescription>
                    <ul class="list-inside list-disc">
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </AlertDescription>
            </Alert>
        </div>

        <Card>
            <CardHeader>
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                        aria-hidden="true"
                    >
                        1
                    </span>
                    <CardTitle>Fecha y saldo inicial</CardTitle>
                </div>
            </CardHeader>
            <CardContent>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <Label for="date">Fecha del cierre</Label>
                        <Input
                            id="date"
                            v-model="dateValue"
                            type="date"
                            class="mt-1 h-10"
                            :class="{
                                'border-destructive': form.errors.cc_date,
                            }"
                            @change="loadPreview"
                        />
                        <p
                            v-if="form.errors.cc_date"
                            id="date-error"
                            class="mt-1 text-xs text-destructive"
                            role="alert"
                        >
                            {{ form.errors.cc_date }}
                        </p>
                    </div>

                    <div>
                        <Label for="opening">Saldo inicial en caja (USD)</Label>
                        <Input
                            id="opening"
                            v-model.number="openingBalance"
                            type="number"
                            step="0.0001"
                            min="0"
                            class="mt-1 h-10 font-mono"
                            :class="{
                                'border-destructive':
                                    form.errors.cc_opening_balance,
                            }"
                            placeholder="0.00"
                            @change="loadPreview"
                        />
                        <p
                            v-if="form.errors.cc_opening_balance"
                            id="opening-error"
                            class="mt-1 text-xs text-destructive"
                            role="alert"
                        >
                            {{ form.errors.cc_opening_balance }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <Deferred data="lastClosing">
                        <template #fallback />
                        <Button
                            v-if="lastClosing"
                            variant="outline"
                            size="sm"
                            @click="useLastClosing"
                        >
                            Usar cierre anterior ({{
                                formatAmount(
                                    Number(lastClosing.cc_counted_amount),
                                )
                            }})
                        </Button>
                    </Deferred>
                    <p
                        v-if="openingBalance === 0"
                        class="text-xs text-amber-400"
                    >
                        Saldo inicial en 0. Si había efectivo al inicio del día,
                        ingrésalo arriba.
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                        aria-hidden="true"
                    >
                        2
                    </span>
                    <CardTitle>Movimientos manuales</CardTitle>
                </div>
            </CardHeader>
            <CardContent>
                <CashClosingMovements v-model:movements="movements" />
            </CardContent>
        </Card>

        <Deferred data="preview">
            <template #fallback>
                <Card>
                    <CardContent
                        class="flex items-center justify-center gap-4 py-16"
                    >
                        <div
                            class="size-6 animate-pulse rounded-full bg-muted-foreground/20"
                        />
                        <p class="text-sm text-zinc-500">
                            Cargando resumen del día…
                        </p>
                    </CardContent>
                </Card>
            </template>

            <Card v-if="preview">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                            aria-hidden="true"
                        >
                            3
                        </span>
                        <CardTitle>Resumen del día</CardTitle>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="px-6">
                        <CashClosingSummary
                            :opening-balance="openingBalance"
                            :total-sales="preview.cc_total_sales"
                            :total-received="preview.cc_total_received"
                            :total-vueltos="preview.vuelto_total"
                            :inflows="totalManualInflows"
                            :outflows="totalManualOutflows"
                        />
                    </div>
                    <div class="mt-4 border-t border-zinc-800 px-6 pt-4 pb-2">
                        <h3 class="mb-1 text-sm font-semibold text-zinc-300">
                            Detalle de cobros / abonos
                        </h3>
                        <CashClosingBreakdown
                            :details="preview.receipts_details"
                        />
                        <p
                            v-if="preview.receipts_details.length === 0"
                            class="py-4 text-sm text-zinc-500"
                        >
                            No hay cobros ni abonos registrados para esta fecha.
                        </p>
                    </div>
                    <div class="mt-4 border-t border-zinc-800 px-6 pt-4 pb-2">
                        <h3 class="mb-1 text-sm font-semibold text-zinc-300">
                            Vuelto a clientes
                        </h3>
                        <CashClosingBreakdown
                            :details="preview.vuelto_details"
                            tone="out"
                        />
                        <p
                            v-if="preview.vuelto_details.length === 0"
                            class="py-4 text-sm text-zinc-500"
                        >
                            No se entregaron vueltos en esta fecha.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="preview">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                            aria-hidden="true"
                        >
                            4
                        </span>
                        <CardTitle>Conteo físico y cuadre</CardTitle>
                        <div class="ml-auto">
                            <CashClosingStatusBadge
                                :status="
                                    hasDifferences ? 'discrepancy' : 'balanced'
                                "
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div
                        role="status"
                        aria-live="polite"
                        class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="coin in coinList"
                            :key="coin"
                            class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4"
                        >
                            <p
                                class="text-xs font-semibold text-zinc-500 uppercase"
                            >
                                Total {{ currencyLabels[coin] ?? coin }}
                            </p>
                            <p
                                class="mt-1 font-mono text-xl font-bold text-green-400 tabular-nums"
                            >
                                {{
                                    formatCoinValue(expectedByCoin[coin], coin)
                                }}
                            </p>
                            <p class="mt-1 text-sm text-zinc-400">
                                Contado:
                                <span
                                    class="font-mono font-semibold tabular-nums"
                                    :class="diffToneClass(coin)"
                                >
                                    {{
                                        formatCoinValue(
                                            totalsByCoin[coin] ?? 0,
                                            coin,
                                        )
                                    }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <CashClosingCountTable
                        v-if="countedRows.length > 0"
                        v-model:rows="countedRows"
                        :grouped-receipts="preview.grouped_receipts"
                        :grouped-vueltos="preview.grouped_vueltos"
                        :expected-by-coin="expectedByCoin"
                        :differences-by-coin="differencesByCoin"
                    />
                    <p
                        v-else
                        class="rounded-xl border border-dashed border-zinc-700 p-6 text-center text-sm text-zinc-500"
                    >
                        No hay movimientos que contar para esta fecha. Puedes
                        guardar el cierre directamente.
                    </p>

                    <div class="mt-6 max-w-xl">
                        <Label for="notes">Notas (opcional)</Label>
                        <textarea
                            id="notes"
                            v-model="notes"
                            class="mt-1 block w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                            rows="3"
                            placeholder="Observaciones del cierre..."
                        />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <Button
                            size="lg"
                            class="h-11 px-6 text-base"
                            :disabled="form.processing"
                            @click="submitForm"
                        >
                            <Save class="mr-2 size-5" aria-hidden="true" />
                            Guardar Cierre de Caja
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </Deferred>
    </div>
</template>
