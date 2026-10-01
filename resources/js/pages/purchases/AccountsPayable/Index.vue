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
    Undo2,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/composables/useDate';
import accountsPayableRoutes from '@/routes/accounts-payable';
import paymentRoutes from '@/routes/payments';

interface AccountsPayable {
    id: number;
    acp_purchase_number: string;
    acp_total: string;
    acp_balance: string;
    acp_status: string;
    acp_total_usd: string | null;
    acp_balance_usd: string | null;
    purchase_order?: {
        invoice_number: string;
        invoice_coin?: string;
        supplier?: {
            supplier_name: string;
        };
    };
    payments?: {
        id: number;
        apy_amount: string;
        apy_type: string;
        apy_invoice_reference: string | null;
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
    accountsPayable: {
        data: AccountsPayable[];
        links: any[];
    };
    filters: {
        search?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

let timeoutId: ReturnType<typeof setTimeout>;

watch([search, status], () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(
            accountsPayableRoutes.index.url({
                query: {
                    search: search.value,
                    status: status.value === 'all' ? null : status.value,
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
    status.value = 'all';
};

const isPaymentsModalOpen = ref(false);
const selectedAccount = ref<AccountsPayable | null>(null);

const viewPayments = (account: AccountsPayable) => {
    selectedAccount.value = account;
    isPaymentsModalOpen.value = true;
};

const editPayment = (paymentId: number) => {
    router.get(`/payments/${paymentId}/edit`);
};

const deletePayment = (paymentId: number) => {
    if (
        confirm(
            '¿Estás seguro de que deseas eliminar este pago? Esta acción restaurará el balance de la deuda original.',
        )
    ) {
        router.delete(`/payments/${paymentId}`, {
            onSuccess: () => {
                isPaymentsModalOpen.value = false;
            },
        });
    }
};
</script>

<template>
    <Head title="Cuentas por Pagar" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/purchases"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Cuentas por Pagar</h1>
                        <p class="text-xs text-zinc-500">
                            Visualiza y gestiona las deudas pendientes con
                            proveedores.
                        </p>
                    </div>
                </div>
                <Link
                    :href="paymentRoutes.create.url()"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <CreditCard class="mr-2 h-4 w-4" /> Registrar Pago
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
                            placeholder="Buscar por factura o proveedor..."
                            class="border-zinc-700 bg-zinc-800 pl-10 text-zinc-100 placeholder:text-zinc-500"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Select v-model="status">
                            <SelectTrigger
                                class="w-[180px] border-zinc-700 bg-zinc-800 text-zinc-100"
                            >
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >Todos los estados</SelectItem
                                >
                                <SelectItem value="pending"
                                    >Pendientes</SelectItem
                                >
                                <SelectItem value="partial"
                                    >Parciales</SelectItem
                                >
                                <SelectItem value="paid">Pagados</SelectItem>
                            </SelectContent>
                        </Select>

                        <button
                            v-if="search || status !== 'all'"
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
                                <th class="px-4 py-4 text-left">Factura</th>
                                <th class="px-4 py-4 text-left">Proveedor</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="w-12 px-4 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="accountsPayable.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <CreditCard
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay cuentas por pagar pendientes</p>
                                </td>
                            </tr>
                            <tr
                                v-for="account in accountsPayable.data"
                                :key="account.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-4 font-mono text-xs">
                                    #{{ account.acp_purchase_number }}
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        account.purchase_order?.supplier
                                            ?.supplier_name ?? 'Desconocido'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-right font-mono">
                                    {{
                                        parseFloat(account.acp_total).toFixed(4)
                                    }}
                                    <span
                                        v-if="
                                            account.purchase_order?.invoice_coin
                                        "
                                        class="ml-1 text-[10px] text-zinc-500"
                                        >{{
                                            account.purchase_order.invoice_coin
                                        }}</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono"
                                    :class="
                                        parseFloat(account.acp_balance) > 0
                                            ? 'text-red-400'
                                            : 'text-green-400'
                                    "
                                >
                                    {{
                                        parseFloat(account.acp_balance).toFixed(
                                            4,
                                        )
                                    }}
                                    <span
                                        v-if="
                                            account.purchase_order?.invoice_coin
                                        "
                                        class="ml-1 text-[10px] opacity-60"
                                        >{{
                                            account.purchase_order.invoice_coin
                                        }}</span
                                    >
                                    <div
                                        v-if="
                                            account.acp_balance_usd &&
                                            account.purchase_order
                                                ?.invoice_coin !== 'USD'
                                        "
                                        class="text-[10px] font-normal text-zinc-500"
                                    >
                                        ${{
                                            parseFloat(
                                                account.acp_balance_usd,
                                            ).toFixed(4)
                                        }}
                                        USD
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs"
                                        :class="{
                                            'bg-red-900/30 text-red-400':
                                                account.acp_status ===
                                                'pending',
                                            'bg-blue-900/30 text-blue-400':
                                                account.acp_status ===
                                                'partial',
                                            'bg-green-900/30 text-green-400':
                                                account.acp_status === 'paid',
                                        }"
                                    >
                                        {{ account.acp_status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button
                                        @click="viewPayments(account)"
                                        class="inline-flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300"
                                    >
                                        <Eye class="h-3.5 w-3.5" /> Pagos
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="accountsPayable.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template
                            v-for="(link, key) in accountsPayable.links"
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

            <!-- Payments Modal -->
            <Dialog
                :open="isPaymentsModalOpen"
                @update:open="isPaymentsModalOpen = $event"
            >
                <DialogContent
                    class="max-h-[85vh] overflow-y-auto sm:max-w-2xl"
                >
                    <DialogHeader>
                        <DialogTitle>Historial de Pagos</DialogTitle>
                        <DialogDescription>
                            Visualizando los pagos de la factura #{{
                                selectedAccount?.acp_purchase_number
                            }}
                            del proveedor
                            {{
                                selectedAccount?.purchase_order?.supplier
                                    ?.supplier_name || 'Desconocido'
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
                            No hay pagos registrados para esta factura.
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="payment in selectedAccount.payments"
                                :key="payment.id"
                                class="rounded-lg border bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800"
                                :class="{
                                    'border-orange-300 dark:border-orange-700':
                                        payment.apy_type === 'RETURN_CREDIT',
                                }"
                            >
                                <div
                                    class="mb-2 flex items-start justify-between"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4
                                                class="text-lg font-semibold text-gray-900 dark:text-white"
                                            >
                                                ${{
                                                    parseFloat(
                                                        payment.apy_amount,
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
                                            <span
                                                v-if="
                                                    payment.apy_type ===
                                                    'RETURN_CREDIT'
                                                "
                                                class="inline-flex items-center rounded bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900/30 dark:text-orange-400"
                                            >
                                                Crédito por devolución
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                                            >
                                                Pago
                                            </span>
                                        </div>
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
                                            v-if="payment.apy_invoice_reference"
                                        >
                                            <Undo2 class="h-4 w-4" />
                                            Dev: #{{
                                                payment.apy_invoice_reference
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

                                    <div
                                        class="flex space-x-2"
                                        v-if="
                                            payment.apy_type !== 'RETURN_CREDIT'
                                        "
                                    >
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
