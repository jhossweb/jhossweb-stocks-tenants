<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Banknote, Receipt } from 'lucide-vue-next';
import { computed } from 'vue';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import { useCurrencyMode } from '@/composables/Dashboard/useCurrencyMode';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { useExpenseBreakdown } from '@/composables/Dashboard/useExpenseBreakdown';
import { dashboard } from '@/routes';
import type { DashboardKpis, ExpenseBreakdown } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Gastos',
                href: '/dashboard/gastos',
            },
        ],
    },
});

const props = defineProps<{
    kpis: DashboardKpis;
    selectedMonth: string;
    /** diferida por el backend: llega en una request parcial posterior */
    expenseBreakdown?: ExpenseBreakdown;
}>();

const { monthOptions, goTo, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/dashboard/gastos',
);

const { mode, isUsd, setMode } = useCurrencyMode();
const { breakdown, isLoading, rowAmount, typeLabel } = useExpenseBreakdown(
    () => props.expenseBreakdown,
    isUsd,
);

const totalUsd = computed(() => breakdown.value?.usd ?? null);
</script>

<template>
    <Head title="Gastos - Dashboard" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Gastos"
            description="Desglose de gastos fijos y variables del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            :currency-mode="mode"
            :rate-date="breakdown?.rate_date ?? null"
            @change-month="changeMonth"
            @change-currency-mode="setMode"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <KpiCard
                label="Gastos Fijos"
                :amounts="kpis.expensesFijo.total"
                :usd="kpis.expensesFijo.usd"
                :trend="kpis.expensesFijo.trend"
                :is-usd-mode="isUsd"
                variant="danger"
                :icon="Receipt"
                @click="goTo('/analytics/expenses-fijo')"
            />
            <KpiCard
                label="Gastos Variables"
                :amounts="kpis.expensesVariable.total"
                :usd="kpis.expensesVariable.usd"
                :trend="kpis.expensesVariable.trend"
                :is-usd-mode="isUsd"
                variant="danger"
                :icon="Banknote"
                @click="goTo('/analytics/expenses-variable')"
            />
        </div>

        <section
            class="rounded-xl border border-border bg-card text-card-foreground shadow-sm"
        >
            <header
                class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-3"
            >
                <h2 class="text-sm font-semibold tracking-wide uppercase">
                    Gastos por categoría
                </h2>
                <p
                    v-if="breakdown && !isUsd"
                    class="text-xs text-muted-foreground tabular-nums"
                >
                    Total:
                    {{
                        breakdown.usd !== null
                            ? `$ ${breakdown.usd.toFixed(2)}`
                            : '—'
                    }}
                </p>
            </header>

            <!-- La prop llega diferida: se avisa en vez de mostrar una tabla vacía. -->
            <div
                v-if="isLoading"
                class="space-y-3 p-4"
                aria-busy="true"
                aria-live="polite"
            >
                <div
                    v-for="row in 4"
                    :key="row"
                    class="h-9 animate-pulse rounded-md bg-muted"
                />
                <span class="sr-only">Cargando desglose de gastos</span>
            </div>

            <p
                v-else-if="!breakdown || breakdown.categories.length === 0"
                class="px-4 py-8 text-center text-sm text-muted-foreground"
            >
                No hay gastos registrados en este período
            </p>

            <table v-else class="w-full text-sm">
                <caption class="sr-only">
                    Gastos del período agrupados por categoría, con el
                    porcentaje de participación sobre el total
                </caption>
                <thead>
                    <tr
                        class="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        <th scope="col" class="px-4 py-2 font-medium">
                            Categoría
                        </th>
                        <th scope="col" class="px-4 py-2 font-medium">Tipo</th>
                        <th
                            scope="col"
                            class="px-4 py-2 text-right font-medium"
                        >
                            Monto
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-2 text-right font-medium"
                        >
                            Participación
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="category in breakdown.categories"
                        :key="category.category_id"
                        class="border-b border-border/60 last:border-0"
                    >
                        <th
                            scope="row"
                            class="px-4 py-2.5 text-left font-medium"
                        >
                            {{ category.exc_name }}
                        </th>
                        <td class="px-4 py-2.5 text-muted-foreground">
                            {{ typeLabel(category.exc_type) }}
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{ rowAmount(category) }}
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center justify-end gap-2">
                                <div
                                    class="h-1.5 w-20 overflow-hidden rounded-full bg-muted"
                                    aria-hidden="true"
                                >
                                    <div
                                        class="h-full rounded-full bg-red-500/70"
                                        :style="{
                                            width: `${category.share_pct}%`,
                                        }"
                                    />
                                </div>
                                <span class="w-12 text-right tabular-nums">
                                    {{ category.share_pct.toFixed(1) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-border font-semibold">
                        <th scope="row" class="px-4 py-2.5 text-left">Total</th>
                        <td />
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{
                                totalUsd !== null
                                    ? `$ ${totalUsd.toFixed(2)}`
                                    : '—'
                            }}
                        </td>
                        <td class="px-4 py-2.5" />
                    </tr>
                </tfoot>
            </table>
        </section>
    </div>
</template>
