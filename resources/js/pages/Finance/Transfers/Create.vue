<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { today } from '@/composables/useDate';

type BankAccount = {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
};

const props = defineProps<{
    bankAccounts: BankAccount[];
}>();

const form = useForm({
    bt_date: today(),
    bt_source_bank_account_id: '',
    bt_destination_bank_account_id: '',
    bt_amount: '',
    bt_reference: '',
    bt_description: '',
});

const formError = computed(
    () => (form.errors as Record<string, string>).error ?? null,
);

const sourceAccount = computed(() =>
    props.bankAccounts.find(
        (account) =>
            String(account.id) === String(form.bt_source_bank_account_id),
    ),
);

const destinationAccounts = computed(() =>
    props.bankAccounts.filter(
        (account) =>
            String(account.id) !== String(form.bt_source_bank_account_id) &&
            account.bank_currency === sourceAccount.value?.bank_currency,
    ),
);

watch(
    () => form.bt_source_bank_account_id,
    () => {
        const destination = props.bankAccounts.find(
            (account) =>
                String(account.id) ===
                String(form.bt_destination_bank_account_id),
        );

        if (
            destination &&
            destination.bank_currency !== sourceAccount.value?.bank_currency
        ) {
            form.bt_destination_bank_account_id = '';
        }
    },
);

function formatBalance(account: BankAccount): string {
    return `${Number(account.bank_balance).toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${account.bank_currency}`;
}

function submit() {
    form.post('/bank-transfers');
}
</script>

<template>
    <div>
        <Head title="Nueva transferencia bancaria" />

        <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
            <div class="flex items-center gap-3">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/bank-transfers">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Nueva transferencia
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Solo entre cuentas de la misma moneda.
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Datos de la transferencia</CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="bt_date">Fecha</Label>
                                <Input
                                    id="bt_date"
                                    v-model="form.bt_date"
                                    type="date"
                                    required
                                />
                                <p
                                    v-if="form.errors.bt_date"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.bt_date }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="bt_amount">Monto</Label>
                                <Input
                                    id="bt_amount"
                                    v-model="form.bt_amount"
                                    type="number"
                                    min="0.0001"
                                    step="0.0001"
                                    required
                                />
                                <p
                                    v-if="form.errors.bt_amount"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.bt_amount }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="bt_source_bank_account_id">
                                    Cuenta origen
                                </Label>
                                <select
                                    id="bt_source_bank_account_id"
                                    v-model="form.bt_source_bank_account_id"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                    required
                                >
                                    <option value="">
                                        Seleccione una cuenta
                                    </option>
                                    <option
                                        v-for="account in props.bankAccounts"
                                        :key="account.id"
                                        :value="account.id"
                                    >
                                        {{ account.bank_name }} ({{
                                            account.bank_currency
                                        }})
                                    </option>
                                </select>
                                <p
                                    v-if="sourceAccount"
                                    class="text-xs text-muted-foreground"
                                >
                                    Saldo disponible:
                                    {{ formatBalance(sourceAccount) }}
                                </p>
                                <p
                                    v-if="form.errors.bt_source_bank_account_id"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.bt_source_bank_account_id }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="bt_destination_bank_account_id">
                                    Cuenta destino
                                </Label>
                                <select
                                    id="bt_destination_bank_account_id"
                                    v-model="
                                        form.bt_destination_bank_account_id
                                    "
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                    required
                                >
                                    <option value="">
                                        Seleccione una cuenta
                                    </option>
                                    <option
                                        v-for="account in destinationAccounts"
                                        :key="account.id"
                                        :value="account.id"
                                    >
                                        {{ account.bank_name }} ({{
                                            account.bank_currency
                                        }})
                                    </option>
                                </select>
                                <p
                                    v-if="
                                        sourceAccount &&
                                        destinationAccounts.length === 0
                                    "
                                    class="text-xs text-destructive"
                                >
                                    No hay otras cuentas activas en
                                    {{ sourceAccount.bank_currency }}.
                                </p>
                                <p
                                    v-if="
                                        form.errors
                                            .bt_destination_bank_account_id
                                    "
                                    class="text-sm text-destructive"
                                >
                                    {{
                                        form.errors
                                            .bt_destination_bank_account_id
                                    }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="bt_reference">Referencia</Label>
                                <Input
                                    id="bt_reference"
                                    v-model="form.bt_reference"
                                    maxlength="50"
                                    placeholder="Opcional"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label for="bt_description">Descripción</Label>
                                <Input
                                    id="bt_description"
                                    v-model="form.bt_description"
                                    maxlength="500"
                                    placeholder="Opcional"
                                />
                            </div>
                        </div>

                        <p v-if="formError" class="text-sm text-destructive">
                            {{ formError }}
                        </p>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 size-4" />
                                Registrar transferencia
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
