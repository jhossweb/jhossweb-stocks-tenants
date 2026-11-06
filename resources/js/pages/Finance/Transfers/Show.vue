<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Ban } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/Auth/usePermissions';
import { formatDate } from '@/composables/useDate';

type Transaction = {
    id: number;
    btr_type: 'inflow' | 'outflow';
    btr_amount: string;
    btr_balance: string;
    btr_reference: string;
    btr_description: string | null;
    btr_coin: string | null;
    bank_account?: { id: number; bank_name: string } | null;
};

type Transfer = {
    id: number;
    bt_number: string | null;
    bt_date: string;
    bt_amount: string;
    bt_coin: string;
    bt_reference: string | null;
    bt_description: string | null;
    bt_voided_at: string | null;
    bt_void_reason: string | null;
    source_account?: { id: number; bank_name: string } | null;
    destination_account?: { id: number; bank_name: string } | null;
    created_by?: { id: number; name: string } | null;
    voided_by?: { id: number; name: string } | null;
    transactions: Transaction[];
};

const props = defineProps<{
    transfer: Transfer;
}>();

const { can } = usePermissions();
const showVoid = ref(false);
const voidForm = useForm({ reason: '' });

const voidError = computed(
    () => (voidForm.errors as Record<string, string>).error ?? null,
);

function money(value: string, coin: string): string {
    return `${Number(value).toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${coin}`;
}

function confirmVoid() {
    voidForm.post(`/bank-transfers/${props.transfer.id}/void`, {
        preserveScroll: true,
        onSuccess: () => {
            showVoid.value = false;
            voidForm.reset();
        },
    });
}
</script>

<template>
    <div>
        <Head :title="`Transferencia ${transfer.bt_number ?? transfer.id}`" />

        <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Button variant="outline" size="icon" as-child>
                        <Link href="/bank-transfers">
                            <ArrowLeft class="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Transferencia
                            {{ transfer.bt_number ?? `#${transfer.id}` }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDate(transfer.bt_date) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="
                            transfer.bt_voided_at ? 'secondary' : 'default'
                        "
                    >
                        {{ transfer.bt_voided_at ? 'Anulada' : 'Completada' }}
                    </Badge>
                    <Button
                        v-if="
                            !transfer.bt_voided_at &&
                            can('finance.transfers.void')
                        "
                        variant="destructive"
                        @click="showVoid = true"
                    >
                        <Ban class="mr-2 size-4" />
                        Anular
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Detalle</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                >
                    <div>
                        <span class="text-muted-foreground">Origen:</span>
                        {{ transfer.source_account?.bank_name ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Destino:</span>
                        {{ transfer.destination_account?.bank_name ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Monto:</span>
                        {{ money(transfer.bt_amount, transfer.bt_coin) }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Referencia:</span>
                        {{ transfer.bt_reference ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground"
                            >Registrada por:</span
                        >
                        {{ transfer.created_by?.name ?? '—' }}
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-muted-foreground">Descripción:</span>
                        {{ transfer.bt_description ?? '—' }}
                    </div>
                    <div
                        v-if="transfer.bt_voided_at"
                        class="rounded-md border border-destructive/40 bg-destructive/5 p-3 md:col-span-2"
                    >
                        <p class="font-medium text-destructive">
                            Transferencia anulada
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Motivo: {{ transfer.bt_void_reason ?? '—' }}
                            <span v-if="transfer.voided_by">
                                · Por {{ transfer.voided_by.name }}
                            </span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Movimientos bancarios</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b text-left">
                                <tr>
                                    <th class="py-2">Banco</th>
                                    <th class="py-2">Tipo</th>
                                    <th class="py-2 text-right">Monto</th>
                                    <th class="py-2 text-right">Saldo</th>
                                    <th class="py-2">Referencia</th>
                                    <th class="py-2">Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="txn in transfer.transactions"
                                    :key="txn.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2">
                                        {{ txn.bank_account?.bank_name ?? '—' }}
                                    </td>
                                    <td class="py-2">
                                        <Badge
                                            :variant="
                                                txn.btr_type === 'inflow'
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                        >
                                            {{
                                                txn.btr_type === 'inflow'
                                                    ? 'Ingreso'
                                                    : 'Egreso'
                                            }}
                                        </Badge>
                                    </td>
                                    <td class="py-2 text-right font-mono">
                                        {{
                                            money(
                                                txn.btr_amount,
                                                txn.btr_coin ??
                                                    transfer.bt_coin,
                                            )
                                        }}
                                    </td>
                                    <td class="py-2 text-right font-mono">
                                        {{
                                            money(
                                                txn.btr_balance,
                                                txn.btr_coin ??
                                                    transfer.bt_coin,
                                            )
                                        }}
                                    </td>
                                    <td class="py-2 font-mono text-xs">
                                        {{ txn.btr_reference }}
                                    </td>
                                    <td class="py-2">
                                        {{ txn.btr_description ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Dialog v-model:open="showVoid">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular transferencia</DialogTitle>
                        <DialogDescription>
                            Se generarán los movimientos inversos. Indica el
                            motivo.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-2">
                        <Label for="void-reason">Motivo</Label>
                        <Input
                            id="void-reason"
                            v-model="voidForm.reason"
                            maxlength="500"
                        />
                        <p
                            v-if="voidForm.errors.reason || voidError"
                            class="text-sm text-destructive"
                        >
                            {{ voidForm.errors.reason || voidError }}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showVoid = false">
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            :disabled="
                                voidForm.processing || !voidForm.reason.trim()
                            "
                            @click="confirmVoid"
                        >
                            Anular
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
