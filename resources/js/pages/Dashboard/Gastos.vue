<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import { dashboard } from '@/routes';
import type { DashboardKpis } from '@/types/Dashboard';

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
}>();

function goTo(path: string) {
    router.get(path, { month: props.selectedMonth }, { preserveScroll: true });
}

function changeMonth(month: string) {
    router.get('/dashboard/gastos', { month }, { preserveState: true, preserveScroll: true });
}

const monthOptions = computed(() => {
    const options: { value: string; label: string }[] = [];
    const now = new Date();

    for (let i = 0; i < 12; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        const label = d.toLocaleDateString('es', { year: 'numeric', month: 'long' });
        options.push({ value, label });
    }

    return options;
});
</script>

<template>
    <Head title="Gastos - Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-foreground text-xl font-semibold">Gastos</h1>
            <select
                :value="selectedMonth"
                class="bg-background border-input rounded-lg border px-3 py-1.5 text-sm"
                @change="changeMonth(($event.target as HTMLSelectElement).value)"
            >
                <option
                    v-for="opt in monthOptions"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </select>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <KpiCard
                label="Gastos Fijos"
                :amounts="kpis.expensesFijo.total"
                :trend="kpis.expensesFijo.trend"
                variant="danger"
                @click="goTo('/analytics/expenses')"
            />
            <KpiCard
                label="Gastos Variables"
                :amounts="kpis.expensesVariable.total"
                :trend="kpis.expensesVariable.trend"
                variant="danger"
                @click="goTo('/analytics/expenses')"
            />
        </div>
    </div>
</template>