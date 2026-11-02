<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    assignPlan as adminTenantsAssignPlan,
    edit as adminTenantsEdit,
    index as adminTenantsIndex,
    updateStatus as adminTenantsUpdateStatus,
} from '@/routes/admin/tenants';
import { update as adminTenantsLimitOverridesUpdate } from '@/routes/admin/tenants/limit-overrides';
import type { TenantShowProps, TenantStatus } from '@/types/Tenancy';

const props = defineProps<TenantShowProps>();

const overrideForm = useForm({
    overrides: Object.fromEntries(
        props.limit_groups.flatMap((group) =>
            group.limits.map((limit) => [
                limit.key,
                props.limit_overrides[limit.key] ?? null,
            ]),
        ),
    ) as Record<string, number | null>,
});

function formatLimit(value: number | null | undefined): string {
    return value === null || value === undefined ? 'Sin límite' : String(value);
}

function setOverride(key: string, event: Event) {
    const value = (event.target as HTMLInputElement).value;
    overrideForm.overrides[key] = value === '' ? null : Number(value);
}

function saveOverrides() {
    overrideForm.put(
        adminTenantsLimitOverridesUpdate.url({ tenant: props.tenant.id }),
        { preserveScroll: true },
    );
}

const showSuspend = ref(false);
const reason = ref('');

const showAssign = ref(false);
const selectedPlan = ref<string>('');

function statusVariant(status: TenantStatus) {
    if (status === 'active') {
        return 'default';
    }

    if (status === 'suspended') {
        return 'destructive';
    }

    return 'secondary';
}

function activate() {
    router.patch(adminTenantsUpdateStatus.url({ tenant: props.tenant.id }), {
        status: 'active',
    });
}

function suspend() {
    router.patch(
        adminTenantsUpdateStatus.url({ tenant: props.tenant.id }),
        {
            status: 'suspended',
            suspended_reason: reason.value,
        },
        {
            onSuccess: () => {
                showSuspend.value = false;
                reason.value = '';
            },
        },
    );
}

function assignPlan() {
    router.post(
        adminTenantsAssignPlan.url({ tenant: props.tenant.id }),
        { plan_id: selectedPlan.value },
        {
            onSuccess: () => {
                showAssign.value = false;
                selectedPlan.value = '';
            },
        },
    );
}
</script>

<template>
    <div>
        <Head :title="'Tenant - ' + tenant.name" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ tenant.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        ID: {{ tenant.id }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="adminTenantsEdit({ tenant: tenant.id })">
                            Editar
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="adminTenantsIndex.url()">Volver</Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Información de la empresa</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                >
                    <div>
                        <span class="text-muted-foreground">Nombre:</span>
                        {{ tenant.name }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">RIF:</span>
                        {{ tenant.rif }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Teléfono:</span>
                        {{ tenant.phone }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Dominio:</span>
                        {{ tenant.domain || '-' }}
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-muted-foreground">Dirección:</span>
                        {{ tenant.address }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Contacto administrador</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                >
                    <div>
                        <span class="text-muted-foreground">Nombre:</span>
                        {{ tenant.admin_name }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Email:</span>
                        {{ tenant.admin_email }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Estado y plan</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                >
                    <div>
                        <span class="text-muted-foreground">Estado:</span>
                        <Badge
                            :variant="statusVariant(tenant.status)"
                            class="ml-2"
                        >
                            {{ tenant.status }}
                        </Badge>
                    </div>
                    <div>
                        <span class="text-muted-foreground"
                            >Origen estado:</span
                        >
                        {{ tenant.status_source || '-' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Plan:</span>
                        {{ tenant.plan || 'Sin plan' }}
                    </div>
                    <div v-if="tenant.suspended_reason">
                        <span class="text-muted-foreground"
                            >Motivo suspensión:</span
                        >
                        {{ tenant.suspended_reason }}
                    </div>
                    <div v-if="tenant.suspended_at">
                        <span class="text-muted-foreground"
                            >Suspendido el:</span
                        >
                        {{ tenant.suspended_at }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Límites (override por tenant)</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <p class="text-sm text-muted-foreground">
                        Vacío = usa el límite del plan. Usa 0 para bloquear por
                        completo.
                    </p>
                    <div
                        v-for="group in props.limit_groups"
                        :key="group.key"
                        class="space-y-4"
                    >
                        <p class="text-sm font-medium">{{ group.label }}</p>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div
                                v-for="limit in group.limits"
                                :key="limit.key"
                                class="space-y-2"
                            >
                                <Label :for="'override-' + limit.key">
                                    {{ limit.label }}
                                </Label>
                                <Input
                                    :id="'override-' + limit.key"
                                    type="number"
                                    min="0"
                                    :model-value="
                                        overrideForm.overrides[limit.key] ?? ''
                                    "
                                    placeholder="Usar límite del plan"
                                    @input="setOverride(limit.key, $event)"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Plan:
                                    {{
                                        formatLimit(
                                            props.plan_limits[limit.key],
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <Button
                        :disabled="overrideForm.processing"
                        @click="saveOverrides"
                    >
                        Guardar overrides
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Acciones</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-2">
                    <Dialog v-model:open="showAssign">
                        <DialogTrigger as-child>
                            <Button>Asignar Plan</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Asignar plan</DialogTitle>
                                <DialogDescription>
                                    Crea la suscripción del tenant y lo activa.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-2">
                                <Label for="plan">Plan</Label>
                                <select
                                    id="plan"
                                    v-model="selectedPlan"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                >
                                    <option value="">Seleccione un plan</option>
                                    <option
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        :value="plan.id"
                                    >
                                        {{ plan.name }} ({{
                                            plan.billing_cycle
                                        }}) — ${{ plan.price_usd }}
                                    </option>
                                </select>
                            </div>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    @click="showAssign = false"
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    :disabled="!selectedPlan"
                                    @click="assignPlan"
                                >
                                    Asignar
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog
                        v-if="tenant.status !== 'suspended'"
                        v-model:open="showSuspend"
                    >
                        <DialogTrigger as-child>
                            <Button variant="destructive">
                                Suspender Tenant
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Suspender Tenant</DialogTitle>
                                <DialogDescription>
                                    Indica el motivo de la suspensión
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-2">
                                <Label for="reason">Motivo</Label>
                                <Input
                                    id="reason"
                                    v-model="reason"
                                    placeholder="Motivo de suspensión..."
                                    required
                                />
                            </div>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    @click="showSuspend = false"
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    variant="destructive"
                                    :disabled="!reason"
                                    @click="suspend"
                                >
                                    Suspender
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Button
                        v-if="tenant.status === 'suspended'"
                        @click="activate"
                    >
                        Activar Tenant
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
