<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BarChart3, Search, X, Calendar, Hash, ArrowUpFromLine, ArrowDownFromLine, Landmark, Tag } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/composables/useDate';
import bankTransactionAnalysisRoutes from '@/routes/bank-transaction-analysis';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Finanzas', href: '#' },
            { title: 'Análisis de Movimientos', href: '/bank-transaction-analysis' },
        ],
    },
});

interface BankAccount {
    id: number;
    bank_name: string;
}

interface Transaction {
    id: number;
    btr_type: 'inflow' | 'outflow';
    btr_amount: string;
    btr_balance: string;
    btr_reference: string;
    btr_description: string | null;
    btr_valorcam: string;
    btr_coin: string | null;
    created_at: string;
    bank_account?: {
        id: number;
        bank_name: string;
    };
    btr_transactionable_type: string;
    btr_transactionable?: Record<string, unknown> | null;
}

const friendlySourceName = (type: string): string => {
    const map: Record<string, string> = {
        'App\\Models\\Expense': 'Gasto',
        'App\\Models\\AccountsPayable': 'Cuenta por Pagar',
        'App\\Models\\Receipt': 'Recibo',
        'App\\Models\\Payroll': 'Nómina',
        'App\\Models\\AccountPayments': 'Pago',
    };

    return map[type] ?? type.split('\\').pop() ?? type;
};

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: any[];
    };
    filters: {
        bank_account_id?: string;
        date_from?: string;
        date_to?: string;
    };
    bankAccounts: BankAccount[];
}>();

const bankAccountId = ref(props.filters.bank_account_id || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

let timeoutId: ReturnType<typeof setTimeout>;

watch([bankAccountId, dateFrom, dateTo], () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(bankTransactionAnalysisRoutes.index.url({
            query: {
                bank_account_id: bankAccountId.value === 'all' ? null : bankAccountId.value,
                date_from: dateFrom.value || null,
                date_to: dateTo.value || null,
            }
        }), {}, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const clearFilters = () => {
    bankAccountId.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
};
</script>

<template>
    <Head title="Análisis de Movimientos Bancarios" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <BarChart3 class="h-8 w-8 text-primary" />
                    Análisis de Movimientos Bancarios
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Visualiza y analiza todos los movimientos de ingresos y egresos bancarios.
                </p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white dark:bg-gray-900 border border-sidebar-border p-4 rounded-xl shadow-sm">
            <div class="flex flex-wrap items-center gap-3">
                <Select v-model="bankAccountId">
                    <SelectTrigger class="w-[220px]">
                        <Landmark class="h-4 w-4 mr-2 text-gray-400" />
                        <SelectValue placeholder="Todos los bancos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos los bancos</SelectItem>
                        <SelectItem v-for="bank in bankAccounts" :key="bank.id" :value="String(bank.id)">
                            {{ bank.bank_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-gray-400 shrink-0" />
                    <Input
                        v-model="dateFrom"
                        type="date"
                        class="w-[160px]"
                        placeholder="Desde"
                    />
                    <span class="text-gray-400">—</span>
                    <Input
                        v-model="dateTo"
                        type="date"
                        class="w-[160px]"
                        placeholder="Hasta"
                    />
                </div>

                <Button v-if="bankAccountId !== 'all' || dateFrom || dateTo" variant="ghost" @click="clearFilters" class="text-gray-500 hover:text-red-600 transition-colors">
                    <X class="h-4 w-4 mr-2" />
                    Limpiar Filtros
                </Button>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Fecha</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Banco</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Tipo</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Monto</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Saldo</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Referencia</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Descripción</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Moneda</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="transactions.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <BarChart3 class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay movimientos registrados</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="txn in transactions.data" :key="txn.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span class="text-gray-900 dark:text-white">{{ formatDate(txn.created_at) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <Landmark class="h-4 w-4 text-gray-400 shrink-0" />
                                    {{ txn.bank_account?.bank_name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="txn.btr_type === 'inflow'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                >
                                    <ArrowUpFromLine v-if="txn.btr_type === 'inflow'" class="h-3 w-3" />
                                    <ArrowDownFromLine v-else class="h-3 w-3" />
                                    {{ txn.btr_type === 'inflow' ? 'Ingreso' : 'Egreso' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                ${{ parseFloat(txn.btr_amount).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                ${{ parseFloat(txn.btr_balance).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <Hash class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span class="font-mono text-xs">{{ txn.btr_reference }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 max-w-[200px] truncate" :title="txn.btr_description ?? ''">
                                <div class="flex items-center gap-2">
                                    <Tag class="h-4 w-4 text-gray-400 shrink-0" />
                                    <span>{{ txn.btr_description ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ txn.btr_coin ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ friendlySourceName(txn.btr_transactionable_type) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="transactions.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in transactions.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
