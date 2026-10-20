<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AlertError from '@/components/AlertError.vue';
import FormActions from '@/components/Form/FormActions.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as ratesIndex, update as ratesUpdate } from '@/routes/admin/rates';
import type { BillingRateRow } from '@/types/Tenancy';

const props = defineProps<{ rate: BillingRateRow }>();

const form = useForm({
    rate_date: props.rate.rate_date,
    rate_usd: props.rate.rate_usd,
    rate_cop: props.rate.rate_cop,
    rate_eur: props.rate.rate_eur,
    rate_cop_bs: props.rate.rate_cop_bs,
});

const errorsList = () => Object.values(form.errors).filter(Boolean);

function submit() {
    form.put(ratesUpdate.url({ rate: props.rate.id }));
}
</script>

<template>
    <div>
        <Head :title="`Tasa ${rate.rate_date}`" />

        <div class="space-y-6">
            <FormActions
                :title="`Tasa ${rate.rate_date}`"
                subtitle="Actualiza los valores de la tasa"
                :back-url="ratesIndex.url()"
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
                    <CardTitle>Tasas</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="rate_date">Fecha</Label>
                            <Input
                                id="rate_date"
                                v-model="form.rate_date"
                                type="date"
                                required
                            />
                            <InputError :message="form.errors.rate_date" />
                        </div>

                        <div class="space-y-2">
                            <Label for="rate_usd">USD (referencia)</Label>
                            <Input
                                id="rate_usd"
                                v-model="form.rate_usd"
                                type="number"
                                step="0.0001"
                                min="0"
                                required
                            />
                            <InputError :message="form.errors.rate_usd" />
                        </div>

                        <div class="space-y-2">
                            <Label for="rate_cop">COP por USD</Label>
                            <Input
                                id="rate_cop"
                                v-model="form.rate_cop"
                                type="number"
                                step="0.0001"
                                min="0"
                                required
                            />
                            <InputError :message="form.errors.rate_cop" />
                        </div>

                        <div class="space-y-2">
                            <Label for="rate_eur">EUR</Label>
                            <Input
                                id="rate_eur"
                                v-model="form.rate_eur"
                                type="number"
                                step="0.0001"
                                min="0"
                                required
                            />
                            <InputError :message="form.errors.rate_eur" />
                        </div>

                        <div class="space-y-2">
                            <Label for="rate_cop_bs">COP (Bs)</Label>
                            <Input
                                id="rate_cop_bs"
                                v-model="form.rate_cop_bs"
                                type="number"
                                step="0.0001"
                                min="0"
                                required
                            />
                            <InputError :message="form.errors.rate_cop_bs" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link :href="ratesIndex.url()">Cancelar</Link>
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    Guardar cambios
                </Button>
            </div>
        </div>
    </div>
</template>
