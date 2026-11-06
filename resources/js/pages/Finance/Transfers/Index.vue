<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRightLeft, PlusCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/composables/useDate';

type BankTransfer = {
    id: number;
    bt_number: string | null;
    bt_date: string;
    bt_amount: string;
    bt_coin: string;
    bt_reference: string | null;
    bt_voided_at: string | null;
    source_account?: { id: number; bank_name: string } | null;
    destination_account?: { id: number; bank_name: string } | null;
};

const props = defineProps<{
    transfers: { data: BankTransfer[]; links: any[] };
    filters: {
        bank_account_id?: string;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
    bankAccounts: { id: number; bank_name: string; bank_currency: string }[];
}>();

const search = ref(props.filters.search ?? '');
const bankAccountId = ref(props.filters.bank_account_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

let timeout: ReturnType<typeof setTimeout>;

watch([search, bankAccountId, dateFrom, dateTo], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            '/bank-transfers',
            {
                search: search.value || undefined,
                bank_account_id: bankAccountId.value || undefined,
                date_from: dateFrom.value || undefined,
                date_to: dateTo.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function money(value: string, coin: string): string {
    return `${Number(value).toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${coin}`;
}
</script>

<template>
    <div>
        <Head title="Transferencias bancarias" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Button variant="outline" size="icon" as-child>
                        <Link href="/finance">
                            <ArrowLeft class="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Transferencias bancarias
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Movimientos entre cuentas de la misma moneda.
                        </p>
                    </div>
                </div>
                <Button as-child>
                    <Link href="/bank-transfers/create">
                        <PlusCircle class="mr-2 size-4" />
                        Nueva transferencia
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <Input
                            v-model="search"
                            placeholder="Buscar número/referencia"
                        />
                        <select
                            v-model="bankAccountId"
                            class="h-10 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">Todas las cuentas</option>
                            <option
                                v-for="account in props.bankAccounts"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.bank_name }} ({{
                                    account.bank_currency
                                }})
                            </option>
                        </select>
                        <Input v-model="dateFrom" type="date" />
                        <Input v-model="dateTo" type="date" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Historial</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b text-left">
                                <tr>
                                    <th class="py-2">Número</th>
                                    <th class="py-2">Fecha</th>
                                    <th class="py-2">Origen</th>
                                    <th class="py-2">Destino</th>
                                    <th class="py-2 text-right">Monto</th>
                                    <th class="py-2">Referencia</th>
                                    <th class="py-2">Estado</th>
                                    <th class="py-2 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="transfer in props.transfers.data"
                                    :key="transfer.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 font-mono text-xs">
                                        {{
                                            transfer.bt_number ??
                                            `#${transfer.id}`
                                        }}
                                    </td>
                                    <td class="py-2">
                                        {{ formatDate(transfer.bt_date) }}
                                    </td>
                                    <td class="py-2">
                                        {{
                                            transfer.source_account
                                                ?.bank_name ?? '—'
                                        }}
                                    </td>
                                    <td class="py-2">
                                        {{
                                            transfer.destination_account
                                                ?.bank_name ?? '—'
                                        }}
                                    </td>
                                    <td class="py-2 text-right font-mono">
                                        {{
                                            money(
                                                transfer.bt_amount,
                                                transfer.bt_coin,
                                            )
                                        }}
                                    </td>
                                    <td class="py-2">
                                        {{ transfer.bt_reference ?? '—' }}
                                    </td>
                                    <td class="py-2">
                                        <Badge
                                            :variant="
                                                transfer.bt_voided_at
                                                    ? 'secondary'
                                                    : 'default'
                                            "
                                        >
                                            {{
                                                transfer.bt_voided_at
                                                    ? 'Anulada'
                                                    : 'Completada'
                                            }}
                                        </Badge>
                                    </td>
                                    <td class="py-2 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="`/bank-transfers/${transfer.id}`"
                                            >
                                                <ArrowRightLeft
                                                    class="mr-1 size-3"
                                                />
                                                Ver
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="props.transfers.data.length === 0">
                                    <td
                                        colspan="8"
                                        class="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        No hay transferencias registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
