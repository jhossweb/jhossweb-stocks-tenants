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
import receiptRoutes from '@/routes/receipts';
import type {
    ReceiptBankTransaction,
    ReceiptHistoryEntry,
} from '@/types/Finance/Receipt';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cobranza', href: receiptRoutes.index.url() },
            { title: 'Detalle', href: '' },
        ],
    },
});

const props = defineProps<{
    receipt: ReceiptHistoryEntry;
    vueltos?: ReceiptBankTransaction[];
}>();

const isReturnCredit = props.receipt.rec_type === 'RETURN_CREDIT';

const typeBadge = isReturnCredit
    ? { label: 'Crédito devolución', class: 'bg-orange-900/30 text-orange-400' }
    : { label: 'Cobro', class: 'bg-blue-900/30 text-blue-400' };

const deleteReceipt = () => {
    if (window.confirm('¿Eliminar este cobro?')) {
        router.delete(receiptRoutes.destroy.url(props.receipt.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="`Cobro #${receipt.id}`" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="receiptRoutes.index.url()">
                        <ArrowLeft class="size-4" aria-hidden="true" />
                    </Link>
                </Button>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-foreground">
                        Cobro #{{ receipt.id }}
                    </h1>
                    <Badge variant="outline" :class="typeBadge.class">
                        {{ typeBadge.label }}
                    </Badge>
                </div>
            </div>

            <div v-if="!isReturnCredit" class="flex items-center gap-3">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="receiptRoutes.edit.url(props.receipt.id)">
                        <Pencil class="mr-2 size-4" aria-hidden="true" />
                        Editar
                    </Link>
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="deleteReceipt"
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
                            Cliente
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p class="font-semibold">
                            {{
                                receipt.accounts_receivable?.sale_order
                                    ?.customer?.clt_name ?? 'Desconocido'
                            }}
                        </p>
                        <p class="text-muted-foreground">
                            {{
                                receipt.accounts_receivable?.sale_order
                                    ?.customer?.clt_cedula ?? '—'
                            }}
                        </p>
                        <p class="text-muted-foreground">
                            {{
                                receipt.accounts_receivable?.sale_order
                                    ?.customer?.clt_address ?? '—'
                            }}
                        </p>
                        <p class="text-muted-foreground">
                            {{
                                receipt.accounts_receivable?.sale_order
                                    ?.customer?.clt_phone ?? '—'
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
                                    receipt.accounts_receivable?.sale_order
                                        ?.sls_number ?? '—'
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Fecha</span>
                            <span>
                                {{
                                    formatDate(
                                        receipt.accounts_receivable?.sale_order
                                            ?.sls_date,
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
                                            receipt.accounts_receivable
                                                ?.sale_order?.sls_total ?? 0,
                                        ),
                                        receipt.accounts_receivable?.sale_order
                                            ?.sls_coin ?? 'USD',
                                    )
                                }}
                            </span>
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
                                >Monto cobrado</span
                            >
                            <span class="text-3xl font-black tabular-nums">
                                {{
                                    formatCoinValue(
                                        Number(
                                            receipt.bank_transaction
                                                ?.btr_amount ??
                                                receipt.rec_amount,
                                        ),
                                        receipt.bank_transaction?.btr_coin ??
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
                                receipt.bank_transaction?.btr_coin ?? 'USD'
                            }}</span>
                        </div>
                        <div
                            v-if="
                                receipt.bank_transaction?.btr_diferencial &&
                                Number(
                                    receipt.bank_transaction.btr_diferencial,
                                ) !== 0
                            "
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Diferencial</span>
                            <span>{{
                                formatCoinValue(
                                    Number(
                                        receipt.bank_transaction
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
                                >Fecha del cobro</span
                            >
                            <span>
                                {{
                                    formatDate(
                                        receipt.bank_transaction
                                            ?.btr_transaction_date ??
                                            receipt.created_at,
                                    )
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Banco</span>
                            <span>
                                {{
                                    receipt.bank_transaction?.bank_account
                                        ?.bank_name ?? '—'
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Método</span>
                            <span>{{
                                receipt.bank_transaction?.btr_method ?? '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Referencia</span
                            >
                            <span class="font-mono">{{
                                receipt.bank_transaction?.btr_reference ?? '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Tasa de cambio</span
                            >
                            <span class="font-mono">{{
                                receipt.bank_transaction?.btr_valorcam ?? '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nota</span>
                            <span>{{
                                receipt.bank_transaction?.btr_description ?? '—'
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
                                        v-if="receipt.bank_transaction"
                                        class="transition-colors hover:bg-zinc-800/20"
                                    >
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full bg-green-900/30 px-3 py-1 text-xs font-semibold text-green-400 uppercase"
                                            >
                                                Ingreso
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-mono text-base font-semibold text-green-400 tabular-nums"
                                        >
                                            +
                                            {{
                                                formatCoinValue(
                                                    Number(
                                                        receipt.bank_transaction
                                                            .btr_amount,
                                                    ),
                                                    receipt.bank_transaction
                                                        .btr_coin ?? 'USD',
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm text-zinc-300"
                                        >
                                            {{
                                                receipt.bank_transaction
                                                    .bank_account?.bank_name ??
                                                '—'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-sm text-zinc-500"
                                        >
                                            {{
                                                receipt.bank_transaction
                                                    .btr_reference ?? '—'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm text-zinc-300"
                                        >
                                            {{
                                                receipt.bank_transaction
                                                    .btr_description ?? '—'
                                            }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="vuelto in vueltos"
                                        :key="vuelto.id"
                                        class="bg-amber-900/10 transition-colors hover:bg-amber-900/20"
                                    >
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full bg-amber-900/40 px-3 py-1 text-xs font-semibold text-amber-400 uppercase"
                                            >
                                                Vuelto
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-mono text-base font-semibold text-amber-400 tabular-nums"
                                        >
                                            -
                                            {{
                                                formatCoinValue(
                                                    Number(vuelto.btr_amount),
                                                    vuelto.btr_coin ?? 'USD',
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm text-zinc-300"
                                        >
                                            {{
                                                vuelto.bank_account
                                                    ?.bank_name ?? '—'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-sm text-zinc-500"
                                        >
                                            {{ vuelto.btr_reference ?? '—' }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm text-zinc-300"
                                        >
                                            {{
                                                vuelto.btr_description ??
                                                'Vuelto a cliente'
                                            }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !receipt.bank_transaction &&
                                            !(vueltos && vueltos.length > 0)
                                        "
                                    >
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
