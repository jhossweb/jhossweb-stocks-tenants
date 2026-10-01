<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CreditCard,
    FileText,
    Search,
    X,
    Eye,
    Pencil,
    Trash2,
    Calendar,
    Hash,
    Download,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/composables/useDate';
import accountsReceivableRoutes from '@/routes/accounts-receivable';
import receiptRoutes from '@/routes/receipts';

interface AccountsReceivable {
    id: number;
    acr_sls_number: string;
    acr_total: string;
    acr_balance: string;
    acr_status: string;
    acr_total_usd: string | null;
    acr_balance_usd: string | null;
    sale_order?: {
        sls_number: string;
        sls_coin: string;
        sls_date: string | null;
        sls_credit_days: number;
        sls_due_date: string | null;
        days_remaining: number;
        transaction_type?: {
            trans_type_description: string;
        };
        customer?: {
            id: number;
            clt_code: string | null;
            clt_cedula: string | null;
            clt_name: string;
        };
    };
    payments?: {
        id: number;
        rec_amount: string;
        rec_diferencial: string;
        created_at: string;
        bank_transaction?: {
            btr_reference: string;
            btr_description: string;
            btr_amount: string;
            btr_type: string;
            btr_coin: string;
            created_at: string;
            btr_valorcam: string;
            btr_diferencial: string;
        };
    }[];
    created_at: string;
}

const props = defineProps<{
    accountsReceivable: {
        data: AccountsReceivable[];
        links: any[];
    };
    filters: {
        search?: string;
        due?: string;
    };
}>();

const search = ref(props.filters.search || '');
const due = ref(props.filters.due || 'all');

let timeoutId: ReturnType<typeof setTimeout>;

watch([search, due], () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(
            accountsReceivableRoutes.index.url({
                query: {
                    search: search.value,
                    due: due.value === 'all' ? null : due.value,
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
    search.value = '';
    due.value = 'all';
};

const dueChips = [
    { value: 'all', label: 'Todas' },
    { value: 'upcoming', label: 'Por vencer' },
    { value: 'overdue', label: 'Vencidas' },
];

const dayBadge = (days: number) => {
    if (days < 0) {
        return {
            class: 'bg-red-900/30 text-red-400',
            label: `${days} días vencido`,
        };
    }

    if (days === 0) {
        return {
            class: 'bg-amber-900/30 text-amber-400',
            label: 'Vence hoy',
        };
    }

    return {
        class: 'bg-emerald-900/30 text-emerald-400',
        label: `${days} días`,
    };
};

const statementUrl = (account: AccountsReceivable) =>
    `/accounts-receivable/client/${account.sale_order?.customer?.id}/pdf`;

const isPaymentsModalOpen = ref(false);
const selectedAccount = ref<AccountsReceivable | null>(null);

const decodeLabel = (label: string) => {
    const textarea = document.createElement('textarea');

    textarea.innerHTML = label;

    return textarea.value;
};

const viewPayments = (account: AccountsReceivable) => {
    selectedAccount.value = account;
    isPaymentsModalOpen.value = true;
};

const editPayment = (paymentId: number) => {
    router.get(`/receipts/${paymentId}/edit`);
};

const deletePayment = (paymentId: number) => {
    if (
        confirm(
            '¿Estás seguro de que deseas eliminar este cobro? Esta acción restaurará el balance de la deuda original.',
        )
    ) {
        router.delete(`/receipts/${paymentId}`, {
            onSuccess: () => {
                isPaymentsModalOpen.value = false;
            },
        });
    }
};
</script>

<template>
    <Head title="Cuentas por Cobrar - Análisis de Crédito" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/sales"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Cuentas por Cobrar</h1>
                        <p class="text-xs text-zinc-500">
                            Vencimiento de facturas emitidas a crédito (FACV ·
                            pendiente / parcial).
                        </p>
                    </div>
                </div>
                <Link
                    :href="receiptRoutes.create.url()"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <CreditCard class="mr-2 h-4 w-4" /> Registrar Cobro
                </Link>
            </div>

            <div
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-4 shadow-sm"
            >
                <div
                    class="flex flex-col items-stretch justify-between gap-4 md:flex-row md:items-center"
                >
                    <div class="relative max-w-sm flex-1">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400"
                        />
                        <Input
                            v-model="search"
                            placeholder="Buscar por factura, cliente o cédula..."
                            class="border-zinc-700 bg-zinc-800 pl-10 text-zinc-100 placeholder:text-zinc-500"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="flex items-center gap-1 rounded-lg border border-zinc-700 bg-zinc-800 p-1"
                        >
                            <button
                                v-for="chip in dueChips"
                                :key="chip.value"
                                @click="due = chip.value"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                                :class="
                                    due === chip.value
                                        ? 'bg-indigo-600 text-white'
                                        : 'text-zinc-400 hover:text-zinc-200'
                                "
                            >
                                {{ chip.label }}
                            </button>
                        </div>

                        <button
                            v-if="search || due !== 'all'"
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
                                <th class="px-4 py-4 text-left">Documento</th>
                                <th class="px-4 py-4 text-left">Tipo</th>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-left">Cliente</th>
                                <th class="px-4 py-4 text-right">
                                    Días Crédito
                                </th>
                                <th class="px-4 py-4 text-center">
                                    Días Restantes
                                </th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                                <th class="w-28 px-4 py-4 text-center">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="accountsReceivable.data.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <CreditCard
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay facturas a crédito pendientes</p>
                                </td>
                            </tr>
                            <tr
                                v-for="account in accountsReceivable.data"
                                :key="account.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-4 font-mono text-xs">
                                    #{{ account.acr_sls_number }}
                                </td>
                                <td class="px-4 py-4 text-zinc-400">
                                    {{
                                        account.sale_order?.transaction_type
                                            ?.trans_type_description ?? 'FACV'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-zinc-400">
                                    {{
                                        formatDate(account.sale_order?.sls_date)
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-medium text-zinc-200">
                                        {{
                                            account.sale_order?.customer
                                                ?.clt_name ?? 'Desconocido'
                                        }}
                                    </div>
                                    <div class="text-[10px] text-zinc-500">
                                        <span
                                            v-if="
                                                account.sale_order?.customer
                                                    ?.clt_code
                                            "
                                            class="mr-2"
                                            >Cód:
                                            {{
                                                account.sale_order.customer
                                                    .clt_code
                                            }}</span
                                        >
                                        <span
                                            v-if="
                                                account.sale_order?.customer
                                                    ?.clt_cedula
                                            "
                                            >C.I:
                                            {{
                                                account.sale_order.customer
                                                    .clt_cedula
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right text-zinc-400">
                                    {{
                                        account.sale_order?.sls_credit_days ?? 0
                                    }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        v-if="account.sale_order"
                                        class="inline-block rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap"
                                        :class="
                                            dayBadge(
                                                account.sale_order
                                                    .days_remaining,
                                            ).class
                                        "
                                    >
                                        {{
                                            dayBadge(
                                                account.sale_order
                                                    .days_remaining,
                                            ).label
                                        }}
                                    </span>
                                    <span v-else class="text-xs text-zinc-600"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono"
                                    :class="
                                        parseFloat(account.acr_balance) > 0
                                            ? 'text-red-400'
                                            : 'text-green-400'
                                    "
                                >
                                    {{
                                        parseFloat(account.acr_balance).toFixed(
                                            4,
                                        )
                                    }}
                                    <span
                                        v-if="account.sale_order?.sls_coin"
                                        class="ml-1 text-[10px] opacity-60"
                                        >{{ account.sale_order.sls_coin }}</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <a
                                            :href="statementUrl(account)"
                                            target="_blank"
                                            rel="noopener"
                                            title="Estado de cuenta del cliente"
                                            class="inline-flex items-center gap-1 text-xs text-zinc-400 hover:text-zinc-200"
                                        >
                                            <Download class="h-3.5 w-3.5" /> PDF
                                        </a>
                                        <button
                                            @click="viewPayments(account)"
                                            class="inline-flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300"
                                        >
                                            <Eye class="h-3.5 w-3.5" /> Cobros
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="accountsReceivable.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template
                            v-for="(link, key) in accountsReceivable.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                            >
                                {{ decodeLabel(link.label) }}
                            </div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                                >{{ decodeLabel(link.label) }}</Link
                            >
                        </template>
                    </div>
                </div>
            </div>

            <!-- Payments Modal -->
            <Dialog
                :open="isPaymentsModalOpen"
                @update:open="isPaymentsModalOpen = $event"
            >
                <DialogContent
                    class="max-h-[85vh] overflow-y-auto sm:max-w-2xl"
                >
                    <DialogHeader>
                        <DialogTitle>Historial de Cobros</DialogTitle>
                        <DialogDescription>
                            Visualizando los cobros de la factura #{{
                                selectedAccount?.acr_sls_number
                            }}
                            del cliente
                            {{
                                selectedAccount?.sale_order?.customer
                                    ?.clt_name || 'Desconocido'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="mt-4">
                        <div
                            v-if="
                                !selectedAccount?.payments ||
                                selectedAccount.payments.length === 0
                            "
                            class="py-8 text-center text-gray-500"
                        >
                            <FileText
                                class="mx-auto mb-2 h-8 w-8 text-gray-300"
                            />
                            No hay cobros registrados para esta factura.
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="payment in selectedAccount.payments"
                                :key="payment.id"
                                class="rounded-lg border bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800"
                            >
                                <div
                                    class="mb-2 flex items-start justify-between"
                                >
                                    <div>
                                        <h4
                                            class="text-lg font-semibold text-gray-900 dark:text-white"
                                        >
                                            ${{
                                                parseFloat(
                                                    payment.rec_amount,
                                                ).toFixed(4)
                                            }}
                                            <span
                                                v-if="
                                                    payment.bank_transaction
                                                        ?.btr_coin
                                                "
                                                class="ml-1 text-xs font-normal text-gray-400"
                                                >{{
                                                    payment.bank_transaction
                                                        .btr_coin
                                                }}</span
                                            >
                                        </h4>
                                        <div
                                            class="mt-1 flex items-center gap-2 text-sm text-gray-500"
                                        >
                                            <Calendar class="h-4 w-4" />
                                            {{
                                                formatDate(
                                                    payment.bank_transaction
                                                        ?.created_at ??
                                                        payment.created_at,
                                                )
                                            }}
                                        </div>
                                        <div
                                            class="mt-1 flex items-center gap-2 text-sm text-gray-500"
                                            v-if="
                                                payment.bank_transaction
                                                    ?.btr_reference
                                            "
                                        >
                                            <Hash class="h-4 w-4" />
                                            Ref:
                                            {{
                                                payment.bank_transaction
                                                    ?.btr_reference
                                            }}
                                        </div>
                                        <div
                                            class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                                            v-if="
                                                payment.bank_transaction
                                                    ?.btr_description
                                            "
                                        >
                                            Nota:
                                            {{
                                                payment.bank_transaction
                                                    .btr_description
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                payment.bank_transaction
                                                    ?.btr_diferencial &&
                                                parseFloat(
                                                    payment.bank_transaction
                                                        .btr_diferencial,
                                                ) !== 0
                                            "
                                            class="mt-2 text-xs font-semibold"
                                            :class="
                                                parseFloat(
                                                    payment.bank_transaction
                                                        .btr_diferencial,
                                                ) > 0
                                                    ? 'text-green-600 dark:text-green-400'
                                                    : 'text-orange-600 dark:text-orange-400'
                                            "
                                        >
                                            Diferencial:
                                            {{
                                                parseFloat(
                                                    payment.bank_transaction
                                                        .btr_diferencial,
                                                ) > 0
                                                    ? '+'
                                                    : ''
                                            }}${{
                                                Math.abs(
                                                    parseFloat(
                                                        payment.bank_transaction
                                                            .btr_diferencial,
                                                    ),
                                                ).toFixed(4)
                                            }}
                                            USD
                                            <span
                                                class="font-normal text-gray-400"
                                                >({{
                                                    parseFloat(
                                                        payment.bank_transaction
                                                            .btr_diferencial,
                                                    ) > 0
                                                        ? 'Ganancia'
                                                        : 'Pérdida'
                                                }})</span
                                            >
                                        </div>
                                    </div>

                                    <div class="flex space-x-2">
                                        <button
                                            @click="editPayment(payment.id)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-700 px-3 py-1.5 text-xs text-zinc-300 transition-colors hover:bg-zinc-800"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                            Editar
                                        </button>
                                        <button
                                            @click="deletePayment(payment.id)"
                                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs text-red-400 transition-colors hover:bg-red-900/20"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex justify-end border-t border-gray-200 pt-4 dark:border-zinc-800"
                    >
                        <button
                            @click="isPaymentsModalOpen = false"
                            class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                        >
                            Cerrar
                        </button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
