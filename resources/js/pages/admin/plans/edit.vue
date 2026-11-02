<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AlertError from '@/components/AlertError.vue';
import FormActions from '@/components/Form/FormActions.vue';
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    update as adminPlansUpdate,
    index as adminPlansIndex,
} from '@/routes/admin/plans';

type FeatureGroup = {
    label: string;
    features: { key: string; label: string }[];
};

const props = defineProps<{
    plan: any;
    module_keys: string[];
    feature_groups: Record<string, FeatureGroup>;
}>();

const form = useForm({
    code: props.plan.code,
    name: props.plan.name,
    description: props.plan.description,
    billing_cycle: props.plan.billing_cycle,
    price_usd: props.plan.price_usd,
    trial_days: props.plan.trial_days,
    is_active: !!props.plan.is_active,
    limits: {} as Record<string, number | null>,
    features: [] as string[],
    module_keys: props.module_keys || [],
});

form.limits['sales.invoices_monthly'] =
    props.plan.limits?.['sales.invoices_monthly'] ?? null;
form.limits['purchases.returns_monthly'] =
    props.plan.limits?.['purchases.returns_monthly'] ?? null;
form.limits['customers.max_total'] =
    props.plan.limits?.['customers.max_total'] ?? null;
form.limits['suppliers.max_total'] =
    props.plan.limits?.['suppliers.max_total'] ?? null;

const enabledFeatures: string[] | null = Array.isArray(props.plan.features)
    ? props.plan.features
    : null;

Object.values(props.feature_groups).forEach((group) => {
    group.features.forEach((feature) => {
        if (enabledFeatures === null || enabledFeatures.includes(feature.key)) {
            form.features.push(feature.key);
        }
    });
});

const core = [
    'inventory',
    'sales',
    'purchases',
    'finance',
    'expenses',
    'payroll',
];
const optin = ['production', 'advanced_reports', 'electronic_invoice'];

const errorsList = () => Object.values(form.errors).filter(Boolean);

function submit() {
    form.put(adminPlansUpdate.url(props.plan.id));
}
</script>

<template>
    <div>
        <Head title="Editar Plan" />

        <div class="space-y-6 p-6">
            <FormActions
                title="Editar Plan"
                subtitle="Actualiza los datos, límites y módulos del plan"
                :back-url="adminPlansIndex.url()"
                :processing="form.processing"
                @save="submit"
            />

            <AlertError
                v-if="Object.keys(form.errors).length"
                :errors="errorsList()"
                title="Errores de validación"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Datos del plan</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="code">Código</Label>
                            <Input id="code" v-model="form.code" required />
                            <InputError :message="form.errors.code" />
                        </div>
                        <div class="space-y-2">
                            <Label for="name">Nombre</Label>
                            <Input id="name" v-model="form.name" required />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="billing_cycle"
                                >Ciclo de facturación</Label
                            >
                            <select
                                v-model="form.billing_cycle"
                                class="h-10 w-full rounded-md border bg-background px-3"
                            >
                                <option value="monthly">Mensual</option>
                                <option value="quarterly">Trimestral</option>
                                <option value="yearly">Anual</option>
                            </select>
                            <InputError :message="form.errors.billing_cycle" />
                        </div>
                        <div class="space-y-2">
                            <Label for="price_usd">Precio USD</Label>
                            <Input
                                id="price_usd"
                                v-model="form.price_usd"
                                required
                            />
                            <InputError :message="form.errors.price_usd" />
                        </div>
                        <div class="space-y-2">
                            <Label for="trial_days">Días de prueba</Label>
                            <Input
                                id="trial_days"
                                v-model.number="form.trial_days"
                                type="number"
                                required
                            />
                            <InputError :message="form.errors.trial_days" />
                        </div>
                        <div class="flex items-center space-x-2 pt-8">
                            <input
                                id="is_active"
                                type="checkbox"
                                v-model="form.is_active"
                                class="h-4 w-4"
                            />
                            <Label for="is_active">Plan activo</Label>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Límites por uso</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Deja vacío para sin límite.
                    </p>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="invoices_monthly"
                                >Facturas de venta mensuales</Label
                            >
                            <input
                                id="invoices_monthly"
                                type="number"
                                min="0"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :value="
                                    form.limits['sales.invoices_monthly'] ?? ''
                                "
                                placeholder="Sin límite"
                                @input="
                                    form.limits['sales.invoices_monthly'] =
                                        ($event.target as HTMLInputElement)
                                            .value === ''
                                            ? null
                                            : Number(
                                                  (
                                                      $event.target as HTMLInputElement
                                                  ).value,
                                              )
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="returns_monthly"
                                >Devoluciones de compra mensuales</Label
                            >
                            <input
                                id="returns_monthly"
                                type="number"
                                min="0"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :value="
                                    form.limits['purchases.returns_monthly'] ??
                                    ''
                                "
                                placeholder="Sin límite"
                                @input="
                                    form.limits['purchases.returns_monthly'] =
                                        ($event.target as HTMLInputElement)
                                            .value === ''
                                            ? null
                                            : Number(
                                                  (
                                                      $event.target as HTMLInputElement
                                                  ).value,
                                              )
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="max_customers"
                                >Máximo de clientes</Label
                            >
                            <input
                                id="max_customers"
                                type="number"
                                min="0"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :value="
                                    form.limits['customers.max_total'] ?? ''
                                "
                                placeholder="Sin límite"
                                @input="
                                    form.limits['customers.max_total'] =
                                        ($event.target as HTMLInputElement)
                                            .value === ''
                                            ? null
                                            : Number(
                                                  (
                                                      $event.target as HTMLInputElement
                                                  ).value,
                                              )
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="max_suppliers"
                                >Máximo de proveedores</Label
                            >
                            <input
                                id="max_suppliers"
                                type="number"
                                min="0"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :value="
                                    form.limits['suppliers.max_total'] ?? ''
                                "
                                placeholder="Sin límite"
                                @input="
                                    form.limits['suppliers.max_total'] =
                                        ($event.target as HTMLInputElement)
                                            .value === ''
                                            ? null
                                            : Number(
                                                  (
                                                      $event.target as HTMLInputElement
                                                  ).value,
                                              )
                                "
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Funcionalidades por módulo</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <p class="text-sm text-muted-foreground">
                        Activa o desactiva submódulos dentro de los módulos
                        principales.
                    </p>
                    <div class="space-y-6">
                        <div
                            v-for="(group, moduleKey) in props.feature_groups"
                            :key="moduleKey"
                        >
                            <p class="mb-3 text-sm font-medium">
                                {{ group.label }}
                            </p>
                            <div
                                class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"
                            >
                                <label
                                    v-for="feature in group.features"
                                    :key="feature.key"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        :value="feature.key"
                                        v-model="form.features"
                                        class="h-4 w-4"
                                    />
                                    <span>{{ feature.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Módulos</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div>
                        <p class="mb-3 text-sm font-medium">
                            Módulos principales
                        </p>
                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"
                        >
                            <label
                                v-for="m in core"
                                :key="m"
                                class="flex items-center space-x-2"
                            >
                                <input
                                    type="checkbox"
                                    :value="m"
                                    v-model="form.module_keys"
                                    class="h-4 w-4"
                                />
                                <span class="capitalize">{{ m }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <p class="mb-3 text-sm font-medium">Opcionales</p>
                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"
                        >
                            <label
                                v-for="m in optin"
                                :key="m"
                                class="flex items-center space-x-2"
                            >
                                <input
                                    type="checkbox"
                                    :value="m"
                                    v-model="form.module_keys"
                                    class="h-4 w-4"
                                />
                                <span class="capitalize">{{
                                    m.replace('_', ' ')
                                }}</span>
                            </label>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
