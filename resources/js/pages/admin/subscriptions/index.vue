<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create as subscriptionsCreate,
    edit as subscriptionsEdit,
    index as subscriptionsIndex,
    renew as subscriptionsRenew,
    destroy as subscriptionsDestroy,
} from '@/routes/admin/subscriptions';
import type {
    SubscriptionsIndexProps,
    SubscriptionStatus,
} from '@/types/Tenancy';

const props = defineProps<SubscriptionsIndexProps>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function statusVariant(value: SubscriptionStatus) {
    if (value === 'active') {
        return 'default';
    }

    if (value === 'trialing') {
        return 'secondary';
    }

    if (value === 'suspended' || value === 'expired' || value === 'canceled') {
        return 'destructive';
    }

    return 'outline';
}

function applyFilters() {
    router.get(
        subscriptionsIndex.url(),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
}

function renew(id: number) {
    if (confirm('¿Renovar esta suscripción un ciclo más?')) {
        router.post(subscriptionsRenew.url({ subscription: id }));
    }
}

function remove(id: number) {
    if (confirm('¿Eliminar esta suscripción? Esta acción no se puede deshacer.')) {
        router.delete(subscriptionsDestroy.url({ subscription: id }));
    }
}

function visit(url: string | null) {
    if (url) {
        router.get(url, {}, { preserveState: true });
    }
}
</script>

<template>
    <div>
        <Head title="Suscripciones" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Suscripciones
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Asignaciones de plan a cada tenant
                    </p>
                </div>
                <Button as-child>
                    <Link :href="subscriptionsCreate()">Nueva Suscripción</Link>
                </Button>
            </div>

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
                                placeholder="Tenant o plan..."
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
                                <option
                                    v-for="item in statuses"
                                    :key="item"
                                    :value="item"
                                >
                                    {{ item }}
                                </option>
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
                                    <th class="p-4 text-left font-medium">#</th>
                                    <th class="p-4 text-left font-medium">
                                        Tenant
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Plan
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Estado
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Periodo actual
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="subscription in subscriptions.data"
                                    :key="subscription.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="p-4 font-mono">
                                        {{ subscription.id }}
                                    </td>
                                    <td class="p-4">
                                        {{ subscription.tenant_name ?? '—' }}
                                    </td>
                                    <td class="p-4">
                                        {{ subscription.plan_name ?? 'Sin plan' }}
                                    </td>
                                    <td class="p-4">
                                        <Badge
                                            :variant="
                                                statusVariant(
                                                    subscription.status,
                                                )
                                            "
                                        >
                                            {{ subscription.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 text-muted-foreground">
                                        {{ subscription.current_period_start }} →
                                        {{ subscription.current_period_end }}
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                as-child
                                            >
                                                <Link
                                                    :href="
                                                        subscriptionsEdit({
                                                            subscription:
                                                                subscription.id,
                                                        })
                                                    "
                                                >
                                                    Editar
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                @click="renew(subscription.id)"
                                            >
                                                Renovar
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="remove(subscription.id)"
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="subscriptions.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="p-8 text-center text-muted-foreground"
                                    >
                                        No hay suscripciones registradas.
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
                    :disabled="!subscriptions.prev_page_url"
                    @click="visit(subscriptions.prev_page_url)"
                >
                    Anterior
                </Button>
                <span class="text-sm text-muted-foreground">
                    Página {{ subscriptions.current_page }} de
                    {{ subscriptions.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!subscriptions.next_page_url"
                    @click="visit(subscriptions.next_page_url)"
                >
                    Siguiente
                </Button>
            </div>
        </div>
    </div>
</template>
