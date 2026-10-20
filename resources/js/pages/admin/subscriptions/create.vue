<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AlertError from '@/components/AlertError.vue';
import FormActions from '@/components/Form/FormActions.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as subscriptionsIndex,
    store as subscriptionsStore,
} from '@/routes/admin/subscriptions';
import type { SubscriptionsFormProps } from '@/types/Tenancy';

const props = defineProps<SubscriptionsFormProps>();

const form = useForm({
    tenant_id: props.selectedTenantId ?? '',
    plan_id: '',
    started_at: '',
    current_period_end: '',
    grace_days: 0,
});

const errorsList = () => Object.values(form.errors).filter(Boolean);

function submit() {
    form.post(subscriptionsStore.url());
}
</script>

<template>
    <div>
        <Head title="Nueva Suscripción" />

        <div class="space-y-6">
            <FormActions
                title="Nueva Suscripción"
                subtitle="Asigna un plan a un tenant (crea la suscripción y lo activa)"
                :back-url="subscriptionsIndex.url()"
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
                    <CardTitle>Datos de la suscripción</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="tenant_id">Tenant</Label>
                            <select
                                id="tenant_id"
                                v-model="form.tenant_id"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                required
                            >
                                <option value="">Seleccione un tenant</option>
                                <option
                                    v-for="tenant in tenants"
                                    :key="tenant.id"
                                    :value="tenant.id"
                                >
                                    {{ tenant.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.tenant_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="plan_id">Plan</Label>
                            <select
                                id="plan_id"
                                v-model="form.plan_id"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                required
                            >
                                <option value="">Seleccione un plan</option>
                                <option
                                    v-for="plan in plans"
                                    :key="plan.id"
                                    :value="plan.id"
                                >
                                    {{ plan.name }} ({{ plan.billing_cycle }}) — ${{ plan.price_usd }}
                                </option>
                            </select>
                            <InputError :message="form.errors.plan_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="started_at">Inicio</Label>
                            <Input
                                id="started_at"
                                v-model="form.started_at"
                                type="date"
                            />
                            <InputError :message="form.errors.started_at" />
                        </div>

                        <div class="space-y-2">
                            <Label for="current_period_end">Fin del periodo</Label>
                            <Input
                                id="current_period_end"
                                v-model="form.current_period_end"
                                type="date"
                            />
                            <InputError
                                :message="form.errors.current_period_end"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="grace_days">Días de gracia</Label>
                            <Input
                                id="grace_days"
                                v-model.number="form.grace_days"
                                type="number"
                                min="0"
                            />
                            <InputError :message="form.errors.grace_days" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child>
                    <a :href="subscriptionsIndex.url()">Cancelar</a>
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    Guardar
                </Button>
            </div>
        </div>
    </div>
</template>
