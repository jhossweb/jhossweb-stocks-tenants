<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import paymentRoutes from '@/routes/admin/payments';
import type { PaymentsIndexProps } from '@/types/Tenancy';

const props = defineProps<PaymentsIndexProps>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function statusVariant(value: string) {
    if (value === 'paid') {
        return 'default';
    }

    if (value === 'voided') {
        return 'destructive';
    }

    return 'secondary';
}

function formatUsd(value: number) {
    return new Intl.NumberFormat('es-VE', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

function applyFilters() {
    router.get(
        paymentRoutes.index.url(),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function visit(url: string | null) {
    if (url) {
        router.get(url, {}, { preserveState: true });
    }
}
</script>

<template>
    <div>
        <Head title="Cobros" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Cobros</h1>
                    <p class="text-sm text-muted-foreground">
                        Pagos recibidos por suscripciones
                    </p>
                </div>
                <Button as-child>
                    <Link :href="paymentRoutes.create()">Registrar Cobro</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Total cobrado</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ formatUsd(totalPaidUsd) }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        class="grid grid-cols-1 gap-4 md:grid-cols-4"
                        @submit.prevent="applyFilters"
                    >
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <Input
                                id="search"
                                v-model="search"
                                placeholder="Recibo, referencia o tenant..."
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Estado</Label>
                            <select
                                id="status"
                                v-model="status"
                                class="h-10 w-full rounded-md border bg-background px-3"
                            >
                                <option value="">Todos</option>
                                <option value="paid">paid</option>
                                <option value="pending">pending</option>
                                <option value="voided">voided</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <Button type="submit">Filtrar</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="p-4 text-left font-medium">
                                        Recibo
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Tenant
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Plan
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Monto
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        USD
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Fecha
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Estado
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="payment in payments.data"
                                    :key="payment.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="p-4 font-mono">
                                        {{ payment.receipt_number }}
                                    </td>
                                    <td class="p-4">
                                        {{ payment.tenant_name ?? '—' }}
                                    </td>
                                    <td class="p-4">
                                        {{ payment.plan_name ?? '—' }}
                                    </td>
                                    <td class="p-4">
                                        {{ payment.amount }}
                                        {{ payment.currency }}
                                    </td>
                                    <td class="p-4">
                                        {{ formatUsd(Number(payment.amount_usd)) }}
                                    </td>
                                    <td class="p-4">{{ payment.paid_at }}</td>
                                    <td class="p-4">
                                        <Badge
                                            :variant="
                                                statusVariant(payment.status)
                                            "
                                        >
                                            {{ payment.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-4">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    paymentRoutes.show({
                                                        payment: payment.id,
                                                    })
                                                "
                                            >
                                                Ver
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="payments.data.length === 0">
                                    <td
                                        colspan="8"
                                        class="p-8 text-center text-muted-foreground"
                                    >
                                        No hay cobros registrados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center justify-between">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!payments.prev_page_url"
                    @click="visit(payments.prev_page_url)"
                >
                    Anterior
                </Button>
                <span class="text-sm text-muted-foreground">
                    Página {{ payments.current_page }} de {{ payments.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!payments.next_page_url"
                    @click="visit(payments.next_page_url)"
                >
                    Siguiente
                </Button>
            </div>
        </div>
    </div>
</template>
