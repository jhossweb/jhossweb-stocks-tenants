<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AlertError from '@/components/AlertError.vue';
import FormActions from '@/components/Form/FormActions.vue';
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    store as adminPlansStore,
    index as adminPlansIndex,
} from '@/routes/admin/plans';

type FeatureGroup = {
    label: string;
    features: { key: string; label: string }[];
};

type LimitGroup = {
    key: string;
    label: string;
    limits: { key: string; label: string; scope: string }[];
};

const props = defineProps<{
    feature_groups: Record<string, FeatureGroup>;
    limit_groups: LimitGroup[];
}>();

const initialLimits: Record<string, number | null> = {};

props.limit_groups.forEach((group) => {
    group.limits.forEach((limit) => {
        initialLimits[limit.key] = null;
    });
});

const form = useForm({
    code: '',
    name: '',
    description: '',
    billing_cycle: 'monthly',
    price_usd: '35.00',
    trial_days: 14,
    is_active: true,
    limits: initialLimits,
    features: [] as string[],
    module_keys: [
        'inventory',
        'sales',
        'purchases',
        'finance',
        'expenses',
        'payroll',
    ],
});

Object.values(props.feature_groups).forEach((group) => {
    group.features.forEach((feature) => {
        form.features.push(feature.key);
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

function setLimit(key: string, event: Event) {
    const value = (event.target as HTMLInputElement).value;
    form.limits[key] = value === '' ? null : Number(value);
}

const errorsList = () => Object.values(form.errors).filter(Boolean);

function submit() {
    form.post(adminPlansStore.url());
}
</script>

<template>
    <div>
        <Head title="Crear Plan" />

        <div class="space-y-6 p-6">
            <FormActions
                title="Crear Plan"
                subtitle="Configura los datos, límites y módulos del plan"
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
                <CardContent class="space-y-6">
                    <p class="text-sm text-muted-foreground">
                        Deja vacío para sin límite. Usa 0 para bloquear por
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
                                <Label :for="limit.key">{{
                                    limit.label
                                }}</Label>
                                <Input
                                    :id="limit.key"
                                    type="number"
                                    min="0"
                                    :model-value="form.limits[limit.key] ?? ''"
                                    placeholder="Sin límite"
                                    @input="setLimit(limit.key, $event)"
                                />
                            </div>
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
