<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import FormActions from '@/components/Form/FormActions.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import payments from '@/routes/admin/payments';
import type { CurrencyCode, PaymentsCreateProps } from '@/types/Tenancy';

const props = defineProps<PaymentsCreateProps>();

function suggestRate(currency: CurrencyCode): string {
    if (!props.latestRate) {
        return currency === 'USD' ? '1' : '';
    }

    switch (currency) {
        case 'COP':
            return props.latestRate.rate_cop;
        case 'EUR':
            return props.latestRate.rate_eur;
        case 'BS':
        case 'VES':
            return props.latestRate.rate_usd;
        default:
            return '1';
    }
}

const form = useForm({
    subscription_id: props.selectedSubscriptionId ?? '',
    amount: '',
    currency: 'USD' as CurrencyCode,
    exchange_rate: suggestRate('USD'),
    method: props.methods[0] ?? 'transfer',
    reference: '',
    paid_at: new Date().toISOString().slice(0, 10),
    notes: '',
});

watch(
    () => form.currency,
    (currency) => {
        form.exchange_rate = suggestRate(currency);
    },
);

const errorsList = () => Object.values(form.errors).filter(Boolean);

function submit() {
    form.post(payments.store.url());
}
</script>

<template>
    <div>
        <Head title="Registrar Cobro" />

        <div class="space-y-6">
            <FormActions
                title="Registrar Cobro"
                subtitle="Registra un pago de suscripción y genera su recibo"
                :back-url="payments.index.url()"
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
                    <CardTitle>Datos del cobro</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="subscription_id">Suscripción</Label>
                            <select
                                id="subscription_id"
                                v-model="form.subscription_id"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                required
                            >
                                <option value="">
                                    Seleccione una suscripción
                                </option>
                                <option
                                    v-for="subscription in subscriptions"
                                    :key="subscription.id"
                                    :value="subscription.id"
                                >
                                    {{ subscription.label }}
                                </option>
                            </select>
                            <InputError
                                :message="form.errors.subscription_id"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="amount">Monto</Label>
                            <Input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                            />
                            <InputError :message="form.errors.amount" />
                        </div>

                        <div class="space-y-2">
                            <Label for="currency">Moneda</Label>
                            <select
                                id="currency"
                                v-model="form.currency"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                required
                            >
                                <option
                                    v-for="currency in currencies"
                                    :key="currency"
                                    :value="currency"
                                >
                                    {{ currency }}
                                </option>
                            </select>
                            <InputError :message="form.errors.currency" />
                        </div>

                        <div class="space-y-2">
                            <Label for="exchange_rate">
                                Tasa aplicada (por USD)
                            </Label>
                            <Input
                                id="exchange_rate"
                                v-model="form.exchange_rate"
                                type="number"
                                step="0.0001"
                                min="0"
                                required
                            />
                            <p
                                v-if="latestRate"
                                class="text-xs text-muted-foreground"
                            >
                                Última tasa: USD {{ latestRate.rate_usd }},
                                COP {{ latestRate.rate_cop }},
                                EUR {{ latestRate.rate_eur }}
                            </p>
                            <InputError :message="form.errors.exchange_rate" />
                        </div>

                        <div class="space-y-2">
                            <Label for="method">Método</Label>
                            <select
                                id="method"
                                v-model="form.method"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                required
                            >
                                <option
                                    v-for="method in methods"
                                    :key="method"
                                    :value="method"
                                >
                                    {{ method }}
                                </option>
                            </select>
                            <InputError :message="form.errors.method" />
                        </div>

                        <div class="space-y-2">
                            <Label for="reference">Referencia</Label>
                            <Input
                                id="reference"
                                v-model="form.reference"
                                placeholder="Nro. de transferencia, etc."
                            />
                            <InputError :message="form.errors.reference" />
                        </div>

                        <div class="space-y-2">
                            <Label for="paid_at">Fecha de pago</Label>
                            <Input
                                id="paid_at"
                                v-model="form.paid_at"
                                type="date"
                            />
                            <InputError :message="form.errors.paid_at" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="notes">Notas</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                class="min-h-24 w-full rounded-md border bg-background px-3 py-2"
                            ></textarea>
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link :href="payments.index.url()">Cancelar</Link>
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    Registrar cobro
                </Button>
            </div>
        </div>
    </div>
</template>
