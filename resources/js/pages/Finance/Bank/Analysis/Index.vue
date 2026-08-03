<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3, X, Calendar, ArrowUpFromLine, ArrowDownFromLine, Landmark } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
    btr_transactionable_type: string | null;
    btr_transactionable?: Record<string, unknown> | null;
}

const friendlySourceName = (type: string | null | undefined): string => {
    const map: Record<string, string> = {
        'App\\Models\\Expense': 'Gasto',
        'App\\Models\\AccountsPayable': 'Cuenta por Pagar',
        'App\\Models\\Receipt': 'Recibo',
        'App\\Models\\Payroll': 'Nómina',
        'App\\Models\\AccountPayments': 'Pago',
    };

    if (!type) {
return '—';
}

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

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/finance" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Análisis de Movimientos Bancarios</h1>
                        <p class="text-xs text-zinc-500">Visualiza y analiza todos los movimientos de ingresos y egresos bancarios.</p>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <Select v-model="bankAccountId">
                            <SelectTrigger class="w-[220px] bg-zinc-800 border-zinc-700 text-zinc-100">
                                <Landmark class="h-4 w-4 mr-2 text-zinc-400" />
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
                            <Calendar class="h-4 w-4 text-zinc-400 shrink-0" />
                            <Input
                                v-model="dateFrom"
                                type="date"
                                class="w-[160px] bg-zinc-800 border-zinc-700 text-zinc-100"
                                placeholder="Desde"
                            />
                            <span class="text-zinc-500">—</span>
                            <Input
                                v-model="dateTo"
                                type="date"
                                class="w-[160px] bg-zinc-800 border-zinc-700 text-zinc-100"
                                placeholder="Hasta"
                            />
                        </div>

                        <button v-if="bankAccountId !== 'all' || dateFrom || dateTo" @click="clearFilters" class="inline-flex items-center gap-2 px-3 py-2 text-sm text-zinc-400 hover:text-red-400 transition-colors rounded-lg hover:bg-zinc-800">
                            <X class="h-4 w-4" />
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-left">Banco</th>
                                <th class="px-4 py-4 text-center">Tipo</th>
                                <th class="px-4 py-4 text-right">Monto</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                                <th class="px-4 py-4 text-left">Referencia</th>
                                <th class="px-4 py-4 text-left">Descripción</th>
                                <th class="px-4 py-4 text-center">Moneda</th>
                                <th class="px-4 py-4 text-left">Origen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="9" class="px-4 py-12 text-center text-zinc-500">
                                    <BarChart3 class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay movimientos registrados</p>
                                </td>
                            </tr>
                            <tr v-for="txn in transactions.data" :key="txn.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 whitespace-nowrap font-mono text-xs text-zinc-300">
                                    {{ formatDate(txn.created_at) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap font-medium text-zinc-200">
                                    {{ txn.bank_account?.bank_name ?? '—' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full inline-flex items-center gap-1"
                                        :class="txn.btr_type === 'inflow'
                                            ? 'bg-green-900/30 text-green-400'
                                            : 'bg-red-900/30 text-red-400'">
                                        <ArrowUpFromLine v-if="txn.btr_type === 'inflow'" class="h-3 w-3" />
                                        <ArrowDownFromLine v-else class="h-3 w-3" />
                                        {{ txn.btr_type === 'inflow' ? 'Ingreso' : 'Egreso' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-mono tabular-nums text-zinc-200">
                                    ${{ parseFloat(txn.btr_amount).toFixed(4) }}
                                </td>
                                <td class="px-4 py-4 text-right font-mono tabular-nums text-zinc-400">
                                    ${{ parseFloat(txn.btr_balance).toFixed(4) }}
                                </td>
                                <td class="px-4 py-4 font-mono text-xs text-zinc-300">
                                    {{ txn.btr_reference }}
                                </td>
                                <td class="px-4 py-4 max-w-[200px] truncate text-zinc-400" :title="txn.btr_description ?? ''">
                                    {{ txn.btr_description ?? '—' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-0.5 rounded bg-zinc-800 text-zinc-300">
                                        {{ txn.btr_coin ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs text-zinc-500">
                                    {{ friendlySourceName(txn.btr_transactionable_type) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="transactions.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in transactions.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
