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

const props = defineProps<{
    plan: any;
    module_keys: string[];
}>();

const form = useForm({
    code: props.plan.code,
    name: props.plan.name,
    description: props.plan.description || '',
    billing_cycle: props.plan.billing_cycle,
    price_usd: String(props.plan.price_usd),
    trial_days: props.plan.trial_days,
    is_active: props.plan.is_active,
    limits: props.plan.limits || {},
    module_keys: props.module_keys,
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
    form.put(adminPlansUpdate.url({ plan: props.plan.id }));
}
</script>

<template>
    <div>
        <Head title="Editar Plan" />

        <div class="space-y-6 p-6">
            <FormActions
                title="Editar Plan"
                subtitle="Actualiza los datos y módulos del plan"
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
                    <CardTitle>Módulos</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="space-y-3">
                        <p class="text-sm font-medium">
                            Core (siempre incluidos)
                        </p>
                        <div
                            v-for="m in core"
                            :key="m"
                            class="flex items-center space-x-2"
                        >
                            <input
                                type="checkbox"
                                checked
                                disabled
                                class="h-4 w-4"
                            />
                            <Label class="text-muted-foreground">{{ m }}</Label>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <p class="text-sm font-medium">Opt-in</p>
                        <div
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
                            <Label>{{ m }}</Label>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
