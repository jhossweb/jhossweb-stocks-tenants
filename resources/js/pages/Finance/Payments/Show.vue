<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calculator,
    FileText,
    Landmark,
    Pencil,
    ReceiptText,
    Trash2,
    User,
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCoinValue } from '@/composables/Finance/useCoinFormat';
import { formatDate } from '@/composables/useDate';
import type { AccountPaymentBankTransaction } from '@/types/Finance/PaymentHistory';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Pagos', href: '/payments' },
            { title: 'Detalle', href: '' },
        ],
    },
});

const props = defineProps<{
    payment: {
        id: number;
        apy_account_payable_id: number;
        apy_bank_transaction_id: number | null;
        apy_amount: string;
        apy_diferencial: string;
        apy_type: string;
        apy_invoice_reference: string | null;
        created_at: string;
        account_payable?: {
            id: number;
            acp_purchase_number: string;
            acp_total: string;
            acp_balance: string;
            acp_status: string;
            purchase_order?: {
                id: number;
                invoice_number: string;
                invoice_coin: string;
                invoice_total: string;
                invoice_date: string;
                supplier?: {
                    id: number;
                    supplier_name: string;
                } | null;
            } | null;
        } | null;
        bank_transaction?: AccountPaymentBankTransaction | null;
    };
}>();

const isReturnCredit = props.payment.apy_type === 'RETURN_CREDIT';

const typeBadge = isReturnCredit
    ? { label: 'Crédito devolución', class: 'bg-orange-900/30 text-orange-400' }
    : { label: 'Pago', class: 'bg-blue-900/30 text-blue-400' };

const deletePayment = () => {
    if (window.confirm('¿Eliminar este pago?')) {
        router.delete(`/payments/${props.payment.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="`Pago #${payment.id}`" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/payments">
                        <ArrowLeft class="size-4" aria-hidden="true" />
                    </Link>
                </Button>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-foreground">
                        Pago #{{ payment.id }}
                    </h1>
                    <Badge variant="outline" :class="typeBadge.class">
                        {{ typeBadge.label }}
                    </Badge>
                </div>
            </div>

            <div v-if="!isReturnCredit" class="flex items-center gap-3">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/payments/${payment.id}/edit`">
                        <Pencil class="mr-2 size-4" aria-hidden="true" />
                        Editar
                    </Link>
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="deletePayment"
                >
                    <Trash2 class="mr-2 size-4" aria-hidden="true" />
                    Eliminar
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="flex flex-col gap-4 lg:col-span-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <User
                                class="size-4 text-primary"
                                aria-hidden="true"
                            />
                            Proveedor
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p class="font-semibold">
                            {{
                                payment.account_payable?.purchase_order
                                    ?.supplier?.supplier_name ?? 'Desconocido'
                            }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <FileText
                                class="size-4 text-primary"
                                aria-hidden="true"
                            />
                            Factura
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Número</span>
                            <span class="font-mono">
                                #{{
                                    payment.account_payable?.purchase_order
                                        ?.invoice_number ?? '—'
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Fecha</span>
                            <span>
                                {{
                                    formatDate(
                                        payment.account_payable?.purchase_order
                                            ?.invoice_date,
                                    )
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Total factura</span
                            >
                            <span class="font-mono">
                                {{
                                    formatCoinValue(
                                        Number(
                                            payment.account_payable
                                                ?.purchase_order
                                                ?.invoice_total ?? 0,
                                        ),
                                        payment.account_payable?.purchase_order
                                            ?.invoice_coin ?? 'USD',
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            v-if="payment.apy_invoice_reference"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground">Dev.</span>
                            <span class="font-mono"
                                >#{{ payment.apy_invoice_reference }}</span
                            >
                        </div>
                    </CardContent>
                </Card>

                <div
                    class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg"
                >
                    <Calculator
                        class="absolute -right-4 -bottom-4 size-24 text-primary-foreground/10"
                        aria-hidden="true"
                    />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div class="flex items-end justify-between">
                            <span class="text-sm font-bold uppercase"
                                >Monto pagado</span
                            >
                            <span class="text-3xl font-black tabular-nums">
                                {{
                                    formatCoinValue(
                                        Number(
                                            payment.bank_transaction
                                                ?.btr_amount ??
                                                payment.apy_amount,
                                        ),
                                        payment.bank_transaction?.btr_coin ??
                                            'USD',
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Moneda</span>
                            <span>{{
                                payment.bank_transaction?.btr_coin ?? 'USD'
                            }}</span>
                        </div>
                        <div
                            v-if="
                                payment.bank_transaction?.btr_diferencial &&
                                Number(
                                    payment.bank_transaction.btr_diferencial,
                                ) !== 0
                            "
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Diferencial</span>
                            <span>{{
                                formatCoinValue(
                                    Number(
                                        payment.bank_transaction
                                            .btr_diferencial,
                                    ),
                                    'USD',
                                )
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 lg:col-span-8">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Landmark
                                class="size-4 text-primary"
                                aria-hidden="true"
                            />
                            Datos del pago
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Fecha del pago</span
                            >
                            <span>
                                {{
                                    formatDate(
                                        payment.bank_transaction
                                            ?.btr_transaction_date ??
                                            payment.created_at,
                                    )
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Banco</span>
                            <span>
                                {{
                                    payment.bank_transaction?.bank_account
                                        ?.bank_name ?? '—'
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Método</span>
                            <span>{{
                                payment.bank_transaction?.btr_method ?? '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Referencia</span
                            >
                            <span class="font-mono">{{
                                payment.bank_transaction?.btr_reference ?? '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Tasa de cambio</span
                            >
                            <span class="font-mono">{{
                                payment.bank_transaction?.btr_valorcam ?? '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nota</span>
                            <span>{{
                                payment.bank_transaction?.btr_description ?? '—'
                            }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <ReceiptText
                                class="size-4 text-primary"
                                aria-hidden="true"
                            />
                            Movimientos
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/40">
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                        >
                                            Tipo
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                                        >
                                            Monto
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                        >
                                            Banco
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                        >
                                            Referencia
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                        >
                                            Descripción
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-800">
                                    <tr
                                        v-if="payment.bank_transaction"
                                        class="transition-colors hover:bg-zinc-800/20"
                                    >
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-900/30 px-3 py-1 text-xs font-semibold text-red-400 uppercase"
                                            >
                                                Egreso
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-mono text-base font-semibold text-red-400 tabular-nums"
                                        >
                                            -
                                            {{
                                                formatCoinValue(
                                                    Number(
                                                        payment.bank_transaction
                                                            .btr_amount,
                                                    ),
                                                    payment.bank_transaction
                                                        .btr_coin ?? 'USD',
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm text-zinc-300"
                                        >
                                            {{
                                                payment.bank_transaction
                                                    .bank_account?.bank_name ??
                                                '—'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-sm text-zinc-500"
                                        >
                                            {{
                                                payment.bank_transaction
                                                    .btr_reference ?? '—'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm text-zinc-300"
                                        >
                                            {{
                                                payment.bank_transaction
                                                    .btr_description ?? '—'
                                            }}
                                        </td>
                                    </tr>
                                    <tr v-if="!payment.bank_transaction">
                                        <td
                                            colspan="5"
                                            class="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Sin movimientos bancarios asociados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
