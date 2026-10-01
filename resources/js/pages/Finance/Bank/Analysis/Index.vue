<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BarChart3,
    X,
    Calendar,
    ArrowUpFromLine,
    ArrowDownFromLine,
    Landmark,
} from 'lucide-vue-next';
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
        router.get(
            bankTransactionAnalysisRoutes.index.url({
                query: {
                    bank_account_id:
                        bankAccountId.value === 'all'
                            ? null
                            : bankAccountId.value,
                    date_from: dateFrom.value || null,
                    date_to: dateTo.value || null,
                },
            }),
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
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

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/finance"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">
                            Análisis de Movimientos Bancarios
                        </h1>
                        <p class="text-xs text-zinc-500">
                            Visualiza y analiza todos los movimientos de
                            ingresos y egresos bancarios.
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-4 shadow-sm"
            >
                <div
                    class="flex flex-col items-stretch justify-between gap-4 md:flex-row md:items-center"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <Select v-model="bankAccountId">
                            <SelectTrigger
                                class="w-[220px] border-zinc-700 bg-zinc-800 text-zinc-100"
                            >
                                <Landmark class="mr-2 h-4 w-4 text-zinc-400" />
                                <SelectValue placeholder="Todos los bancos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >Todos los bancos</SelectItem
                                >
                                <SelectItem
                                    v-for="bank in bankAccounts"
                                    :key="bank.id"
                                    :value="String(bank.id)"
                                >
                                    {{ bank.bank_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="flex items-center gap-2">
                            <Calendar class="h-4 w-4 shrink-0 text-zinc-400" />
                            <Input
                                v-model="dateFrom"
                                type="date"
                                class="w-[160px] border-zinc-700 bg-zinc-800 text-zinc-100"
                                placeholder="Desde"
                            />
                            <span class="text-zinc-500">—</span>
                            <Input
                                v-model="dateTo"
                                type="date"
                                class="w-[160px] border-zinc-700 bg-zinc-800 text-zinc-100"
                                placeholder="Hasta"
                            />
                        </div>

                        <button
                            v-if="bankAccountId !== 'all' || dateFrom || dateTo"
                            @click="clearFilters"
                            class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-red-400"
                        >
                            <X class="h-4 w-4" />
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
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
                                <td
                                    colspan="9"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <BarChart3
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay movimientos registrados</p>
                                </td>
                            </tr>
                            <tr
                                v-for="txn in transactions.data"
                                :key="txn.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td
                                    class="px-4 py-4 font-mono text-xs whitespace-nowrap text-zinc-300"
                                >
                                    {{ formatDate(txn.created_at) }}
                                </td>
                                <td
                                    class="px-4 py-4 font-medium whitespace-nowrap text-zinc-200"
                                >
                                    {{ txn.bank_account?.bank_name ?? '—' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs"
                                        :class="
                                            txn.btr_type === 'inflow'
                                                ? 'bg-green-900/30 text-green-400'
                                                : 'bg-red-900/30 text-red-400'
                                        "
                                    >
                                        <ArrowUpFromLine
                                            v-if="txn.btr_type === 'inflow'"
                                            class="h-3 w-3"
                                        />
                                        <ArrowDownFromLine
                                            v-else
                                            class="h-3 w-3"
                                        />
                                        {{
                                            txn.btr_type === 'inflow'
                                                ? 'Ingreso'
                                                : 'Egreso'
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-zinc-200 tabular-nums"
                                >
                                    ${{ parseFloat(txn.btr_amount).toFixed(4) }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-zinc-400 tabular-nums"
                                >
                                    ${{
                                        parseFloat(txn.btr_balance).toFixed(4)
                                    }}
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-300"
                                >
                                    {{ txn.btr_reference }}
                                </td>
                                <td
                                    class="max-w-[200px] truncate px-4 py-4 text-zinc-400"
                                    :title="txn.btr_description ?? ''"
                                >
                                    {{ txn.btr_description ?? '—' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="rounded bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300"
                                    >
                                        {{ txn.btr_coin ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs text-zinc-500">
                                    {{
                                        friendlySourceName(
                                            txn.btr_transactionable_type,
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="transactions.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template
                            v-for="(link, key) in transactions.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                                v-html="link.label"
                            ></div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                                v-html="link.label"
                            ></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
