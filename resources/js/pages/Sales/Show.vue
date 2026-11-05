<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calculator,
    FileDown,
    Landmark,
    User,
} from 'lucide-vue-next';
import DocumentLifecycleMenu from '@/components/DocumentLifecycleMenu.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermissions } from '@/composables/Auth/usePermissions';
import { formatCoinValue } from '@/composables/Finance/useCoinFormat';
import { formatDate } from '@/composables/useDate';

type Props = {
    sale: {
        id: number;
        sls_number: string;
        sls_date: string;
        sls_coin: string;
        sls_valorcam: string;
        sls_payment_condition: string;
        sls_credit_days?: number;
        sls_due_date?: string;
        sls_subtotal: string;
        sls_exento: string;
        sls_iva: string;
        sls_total: string;
        sls_status: string;
        customer?: {
            clt_name?: string;
            clt_address?: string;
            clt_phone?: string;
            clt_email?: string;
        } | null;
        movements?: Array<{
            id: number;
            mvs_pro_code: string;
            mvs_quantity: number;
            mvs_unit_price: number;
            mvs_pro_iva: number;
            product?: { pro_name?: string; pro_und?: string } | null;
        }>;
    };
};

const { can } = usePermissions();

const props = defineProps<Props>();

const paymentConditionLabel =
    props.sale.sls_payment_condition === 'credit' ? 'Crédito' : 'Contado';

const statusClass: Record<string, string> = {
    pending: 'bg-blue-900/30 text-blue-400',
    paid: 'bg-green-900/30 text-green-400',
    partial: 'bg-yellow-900/30 text-yellow-400',
    cancelled: 'bg-red-900/30 text-red-400',
};

const lineTotal = (quantity: number, unitPrice: number): number =>
    Number(quantity) * Number(unitPrice);
</script>

<template>
    <Head :title="`Venta #${props.sale.sls_number}`" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link
                    href="/sales"
                    class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">
                        Venta #{{ props.sale.sls_number }}
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        Detalle de la factura de venta
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="
                        statusClass[props.sale.sls_status] ??
                        'bg-zinc-800 text-zinc-400'
                    "
                >
                    {{ props.sale.sls_status }}
                </span>
                <a :href="`/sales/${props.sale.id}/pdf`">
                    <Button
                        type="button"
                        variant="outline"
                        class="inline-flex items-center gap-2"
                    >
                        <FileDown class="size-4" aria-hidden="true" />
                        Exportar PDF
                    </Button>
                </a>
                <DocumentLifecycleMenu
                    type="sales"
                    :record="props.sale"
                    :allow-deactivate="false"
                    :can-void="can('sales.invoices.void')"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-4">
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
                            {{ props.sale.customer?.clt_name ?? '—' }}
                        </p>
                        <p class="text-muted-foreground">
                            {{ props.sale.customer?.clt_address ?? '—' }}
                        </p>
                        <p class="text-muted-foreground">
                            {{ props.sale.customer?.clt_phone ?? '—' }}
                        </p>
                        <p class="text-muted-foreground">
                            {{ props.sale.customer?.clt_email ?? '—' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Landmark
                                class="size-4 text-primary"
                                aria-hidden="true"
                            />
                            Datos de la Factura
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Fecha</span>
                            <span>{{ formatDate(props.sale.sls_date) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Moneda</span>
                            <span>{{ props.sale.sls_coin }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Condición</span>
                            <span>
                                {{ paymentConditionLabel }}
                                <template
                                    v-if="
                                        props.sale.sls_payment_condition ===
                                        'credit'
                                    "
                                >
                                    ({{ props.sale.sls_credit_days }} días)
                                </template>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Vencimiento</span
                            >
                            <span>{{
                                formatDate(props.sale.sls_due_date)
                            }}</span>
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
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Subtotal</span>
                            <span>{{
                                formatCoinValue(
                                    Number(props.sale.sls_subtotal),
                                    props.sale.sls_coin,
                                )
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Exento</span>
                            <span>{{
                                formatCoinValue(
                                    Number(props.sale.sls_exento),
                                    props.sale.sls_coin,
                                )
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>IVA</span>
                            <span>{{
                                formatCoinValue(
                                    Number(props.sale.sls_iva),
                                    props.sale.sls_coin,
                                )
                            }}</span>
                        </div>
                        <div
                            class="flex items-end justify-between border-t border-primary-foreground/10 pt-3"
                        >
                            <span class="text-sm font-bold uppercase"
                                >Total</span
                            >
                            <span class="text-3xl font-black tabular-nums">{{
                                formatCoinValue(
                                    Number(props.sale.sls_total),
                                    props.sale.sls_coin,
                                )
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base"
                            >Detalle de ítems</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="overflow-x-auto p-0">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b border-border text-[10px] font-bold text-muted-foreground uppercase"
                            >
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        Producto
                                    </th>
                                    <th class="px-4 py-3 text-center">Und.</th>
                                    <th class="px-4 py-3 text-center">Cant.</th>
                                    <th class="px-4 py-3 text-right">
                                        Precio Unit.
                                    </th>
                                    <th class="px-4 py-3 text-right">IVA</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-if="
                                        !props.sale.movements ||
                                        props.sale.movements.length === 0
                                    "
                                >
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Sin ítems registrados
                                    </td>
                                </tr>
                                <tr
                                    v-for="item in props.sale.movements"
                                    :key="item.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-medium">
                                            {{
                                                item.product?.pro_name ??
                                                item.mvs_pro_code
                                            }}
                                        </div>
                                        <div
                                            class="font-mono text-xs text-muted-foreground"
                                        >
                                            {{ item.mvs_pro_code }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ item.product?.pro_und ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ Number(item.mvs_quantity) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{
                                            formatCoinValue(
                                                Number(item.mvs_unit_price),
                                                props.sale.sls_coin,
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        {{ Number(item.mvs_pro_iva) }}%
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-semibold tabular-nums"
                                    >
                                        {{
                                            formatCoinValue(
                                                lineTotal(
                                                    Number(item.mvs_quantity),
                                                    Number(item.mvs_unit_price),
                                                ),
                                                props.sale.sls_coin,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
