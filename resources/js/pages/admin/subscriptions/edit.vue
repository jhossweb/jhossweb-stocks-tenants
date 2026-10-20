<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AlertError from '@/components/AlertError.vue';
import FormActions from '@/components/Form/FormActions.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as subscriptionsIndex,
    update as subscriptionsUpdate,
} from '@/routes/admin/subscriptions';
import type { SubscriptionEditProps } from '@/types/Tenancy';

const props = defineProps<SubscriptionEditProps>();

const form = useForm({
    plan_id: props.subscription.plan_id ?? '',
    status: props.subscription.status,
    started_at: props.subscription.started_at ?? '',
    trial_ends_at: props.subscription.trial_ends_at ?? '',
    current_period_start: props.subscription.current_period_start ?? '',
    current_period_end: props.subscription.current_period_end ?? '',
    grace_days: props.subscription.grace_days ?? 0,
});

const statuses = [
    'trialing',
    'active',
    'past_due',
    'suspended',
    'canceled',
    'expired',
];

const errorsList = () => Object.values(form.errors).filter(Boolean);

function submit() {
    form.put(subscriptionsUpdate.url({ subscription: props.subscription.id }));
}
</script>

<template>
    <div>
        <Head :title="`Suscripción #${subscription.id}`" />

        <div class="space-y-6">
            <FormActions
                :title="`Suscripción #${subscription.id}`"
                :subtitle="`Tenant: ${subscription.tenant_name ?? '—'}`"
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
                            <Label for="status">Estado</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                required
                            >
                                <option
                                    v-for="item in statuses"
                                    :key="item"
                                    :value="item"
                                >
                                    {{ item }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
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
                            <Label for="trial_ends_at">Fin de prueba</Label>
                            <Input
                                id="trial_ends_at"
                                v-model="form.trial_ends_at"
                                type="date"
                            />
                            <InputError :message="form.errors.trial_ends_at" />
                        </div>

                        <div class="space-y-2">
                            <Label for="current_period_start">Inicio periodo</Label>
                            <Input
                                id="current_period_start"
                                v-model="form.current_period_start"
                                type="date"
                            />
                            <InputError
                                :message="form.errors.current_period_start"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="current_period_end">Fin periodo</Label>
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
                    <Link :href="subscriptionsIndex.url()">Cancelar</Link>
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    Guardar cambios
                </Button>
            </div>
        </div>
    </div>
</template>
