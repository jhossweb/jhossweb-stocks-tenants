<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Loader2,
    Building2,
    Landmark,
    Calculator,
    AlertCircle,
    Repeat,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';
import Input from '@/components/ui/input/Input.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePayment } from '@/composables/usePayment';

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
    formatted_balance?: string;
    bank_account_number?: string;
}

const props = defineProps<{
    title: string;
    subtitle: string;
    backRoute: string;
    form: any;
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
    bankAccounts: BankAccount[];
    latestRate: any;
    currencies: string[];
    methods: string[];
    totalLabel: string;
    totalAmount: number;
    totalCurrency: string;
    originalTotal?: number;
    originalCurrency?: string;
    estimatedDiferencial?: number;
}>();

const emit = defineEmits<{
    submit: [];
}>();

const { resolveRate } = usePayment();

const filteredBankAccounts = computed(() =>
    props.bankAccounts.filter(
        (ba) => ba.bank_currency === props.form.payment_currency,
    ),
);

const selectedBankAccount = computed(() => {
    return filteredBankAccounts.value.find(
        (ba) => ba.id == props.form.bank_account_id,
    );
});

const isInsufficientFunds = computed(() => {
    const account = selectedBankAccount.value;

    if (!account) {
        return false;
    }

    const amount = Number(props.form.amount || 0);

    return amount > 0 && Number(account.bank_balance) < amount;
});

watch(
    () => props.form.payment_currency,
    () => {
        const isInFiltered = filteredBankAccounts.value.some(
            (ba) => ba.id == props.form.bank_account_id,
        );

        if (!isInFiltered) {
            props.form.bank_account_id = '';
        }

        updateExchangeRate();
    },
);

const updateExchangeRate = () => {
    if (!props.latestRate) {
        return;
    }

    props.form.payment_rate = resolveRate(
        props.form.payment_currency,
        props.latestRate,
    );
};

watch(
    () => props.form.payment_currency,
    () => updateExchangeRate(),
    { immediate: true },
);
</script>

<template>
    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div
            class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm"
        >
            <div class="flex items-center gap-4">
                <Link
                    :href="backRoute"
                    class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">
                        {{ title }}
                    </h1>
                    <p v-if="subtitle" class="text-xs text-muted-foreground">
                        {{ subtitle }}
                    </p>
                </div>
            </div>
            <button
                type="button"
                :disabled="processing || isInsufficientFunds"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 disabled:opacity-50"
                @click="emit('submit')"
            >
                <Loader2 v-if="processing" class="size-4 animate-spin" />
                <Save v-else-if="!isInsufficientFunds" class="size-4" />
                <AlertCircle v-else class="size-4" />
                {{
                    isInsufficientFunds
                        ? 'Fondos Insuficientes'
                        : processing
                          ? 'Guardando...'
                          : submitLabel
                }}
            </button>
        </div>

        <div
            v-if="Object.keys(errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Por favor, corrige los siguientes errores:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li v-for="(error, key) in errors" :key="key">
                    {{ error }}
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-4">
                <div class="rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <Landmark class="size-4 text-primary" />
                        <span
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >Datos del Beneficiario</span
                        >
                    </div>
                    <div class="flex flex-col gap-5 p-6">
                        <slot name="entity-selector" />

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Fecha de Pago</label
                                >
                                <Input
                                    type="date"
                                    v-model="form.payment_date"
                                    :class="{
                                        'border-destructive':
                                            errors.payment_date,
                                    }"
                                />
                                <span
                                    v-if="errors.payment_date"
                                    class="mt-1 text-xs text-destructive"
                                    >{{ errors.payment_date }}</span
                                >
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Moneda de Pago</label
                                >
                                <Select v-model="form.payment_currency">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Moneda" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="c in currencies"
                                            :key="c"
                                            :value="c"
                                            >{{ c }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="errors.payment_currency"
                                    class="mt-1 text-xs text-destructive"
                                    >{{ errors.payment_currency }}</span
                                >
                            </div>
                        </div>

                        <div class="rounded-lg border bg-muted/30 p-4">
                            <label
                                class="mb-1.5 block text-xs font-bold text-muted-foreground uppercase"
                                >Tasa de Cambio</label
                            >
                            <Input
                                type="number"
                                step="0.0001"
                                v-model="form.payment_rate"
                                class="font-mono text-sm font-bold"
                            />
                            <span
                                v-if="errors.payment_rate"
                                class="mt-1 text-xs text-destructive"
                                >{{ errors.payment_rate }}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg"
                >
                    <Calculator
                        class="absolute -right-4 -bottom-4 size-24 text-primary-foreground/10"
                    />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>{{ totalLabel }}</span>
                            <span>{{
                                totalAmount.toLocaleString('en-US', {
                                    minimumFractionDigits: 4,
                                    maximumFractionDigits: 4,
                                })
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Tasa Aplicada</span>
                            <span class="font-mono">{{
                                Number(form.payment_rate || 1).toFixed(4)
                            }}</span>
                        </div>

                        <div
                            v-if="
                                originalTotal !== undefined &&
                                originalCurrency &&
                                originalCurrency !== form.payment_currency
                            "
                            class="flex flex-col gap-2 border-t border-primary-foreground/10 pt-3"
                        >
                            <div class="flex items-center gap-2 text-xs">
                                <Repeat class="size-3.5" /> Conversión de Moneda
                            </div>
                            <div class="flex justify-between">
                                <span>Total a pagar</span>
                                <span class="font-bold"
                                    >{{
                                        totalAmount.toLocaleString('en-US', {
                                            minimumFractionDigits: 4,
                                            maximumFractionDigits: 4,
                                        })
                                    }}
                                    {{ form.payment_currency }}</span
                                >
                            </div>
                            <div
                                class="text-right text-xs text-primary-foreground/70"
                            >
                                (equivalente a
                                {{
                                    originalTotal.toLocaleString('en-US', {
                                        minimumFractionDigits: 4,
                                    })
                                }}
                                {{ originalCurrency }})
                            </div>
                        </div>

                        <div
                            v-if="
                                estimatedDiferencial !== undefined &&
                                estimatedDiferencial !== 0 &&
                                form.payment_currency !== 'USD'
                            "
                            class="flex flex-col gap-2 border-t border-primary-foreground/10 pt-3"
                        >
                            <div class="flex justify-between">
                                <span class="text-xs"
                                    >Diferencial Cambiario</span
                                >
                                <span
                                    class="text-sm font-bold"
                                    :class="
                                        estimatedDiferencial > 0
                                            ? 'text-green-300'
                                            : 'text-orange-300'
                                    "
                                >
                                    {{ estimatedDiferencial > 0 ? '+' : '' }}${{
                                        Math.abs(estimatedDiferencial).toFixed(
                                            4,
                                        )
                                    }}
                                    USD
                                </span>
                            </div>
                            <div
                                class="text-right text-xs text-primary-foreground/70"
                            >
                                {{
                                    estimatedDiferencial > 0
                                        ? 'Ganancia por diferencia de tasas'
                                        : 'Pérdida por diferencia de tasas'
                                }}
                            </div>
                        </div>

                        <div
                            class="flex items-end justify-between border-t border-primary-foreground/10 pt-3"
                        >
                            <span class="text-sm font-bold uppercase"
                                >Total {{ totalCurrency }}</span
                            >
                            <span class="text-3xl font-black tabular-nums">{{
                                totalAmount.toLocaleString('en-US', {
                                    minimumFractionDigits: 4,
                                    maximumFractionDigits: 4,
                                })
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 lg:col-span-8">
                <slot name="items-table" />

                <div class="rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <Building2 class="size-4 text-primary" />
                        <span
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >Origen de Fondos</span
                        >
                    </div>
                    <div class="flex flex-col gap-5 p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="flex flex-col gap-5">
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                        >Banco y Cuenta</label
                                    >
                                    <Select v-model="form.bank_account_id">
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Seleccione cuenta bancaria"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="ba in filteredBankAccounts"
                                                :key="ba.id"
                                                :value="ba.id.toString()"
                                            >
                                                <div
                                                    class="flex flex-col items-start py-0.5"
                                                >
                                                    <span class="font-bold">{{
                                                        ba.bank_name
                                                    }}</span>
                                                    <span
                                                        class="font-mono text-[10px] text-muted-foreground"
                                                        >{{
                                                            ba.bank_account_number ||
                                                            ba.bank_currency
                                                        }}
                                                        ({{
                                                            ba.bank_currency
                                                        }})</span
                                                    >
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span
                                        v-if="errors.bank_account_id"
                                        class="mt-1 text-xs text-destructive"
                                        >{{ errors.bank_account_id }}</span
                                    >
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                        >Tipo de Transacción</label
                                    >
                                    <Select v-model="form.payment_method">
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Método de pago"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="m in methods"
                                                :key="m"
                                                :value="m"
                                                >{{ m }}</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <span
                                        v-if="errors.payment_method"
                                        class="mt-1 text-xs text-destructive"
                                        >{{ errors.payment_method }}</span
                                    >
                                </div>
                            </div>

                            <div class="flex flex-col gap-5">
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                        >Referencia / Voucher</label
                                    >
                                    <Input
                                        v-model="form.payment_reference"
                                        placeholder="N° de transacción o referencia"
                                        :class="{
                                            'border-destructive':
                                                errors.payment_reference,
                                        }"
                                    />
                                    <span
                                        v-if="errors.payment_reference"
                                        class="mt-1 text-xs text-destructive"
                                        >{{ errors.payment_reference }}</span
                                    >
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                        >Observaciones</label
                                    >
                                    <Input
                                        v-model="form.payment_description"
                                        placeholder="Opcional"
                                        :class="{
                                            'border-destructive':
                                                errors.payment_description,
                                        }"
                                    />
                                    <span
                                        v-if="errors.payment_description"
                                        class="mt-1 text-xs text-destructive"
                                        >{{ errors.payment_description }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="selectedBankAccount"
                            class="flex items-center gap-4 rounded-xl border p-4"
                            :class="
                                Number(selectedBankAccount.bank_balance) >=
                                Number(form.amount || 0)
                                    ? 'border-green-500/10 bg-green-500/5'
                                    : 'border-red-500/10 bg-red-500/5'
                            "
                        >
                            <div
                                class="rounded-lg p-2.5"
                                :class="
                                    Number(selectedBankAccount.bank_balance) >=
                                    Number(form.amount || 0)
                                        ? 'bg-green-500/20 text-green-500'
                                        : 'bg-red-500/20 text-red-500'
                                "
                            >
                                <Landmark class="size-5" />
                            </div>
                            <div class="flex-1">
                                <p
                                    class="text-[10px] font-bold text-muted-foreground uppercase"
                                >
                                    Saldo Disponible en Banco
                                </p>
                                <p class="text-sm font-bold">
                                    {{
                                        Number(
                                            selectedBankAccount.bank_balance,
                                        ).toLocaleString()
                                    }}
                                    {{ selectedBankAccount.bank_currency }}
                                </p>
                            </div>
                            <div
                                v-if="
                                    Number(selectedBankAccount.bank_balance) <
                                    Number(form.amount || 0)
                                "
                                class="flex items-center gap-1.5 rounded-full bg-red-500/10 px-3 py-1 text-[10px] font-bold text-red-500 uppercase"
                            >
                                <AlertCircle class="size-3" /> Fondos
                                Insuficientes
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    appearance: none;
    -webkit-appearance: none;
    margin: 0;
}
input[type='number'] {
    appearance: textfield;
    -moz-appearance: textfield;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
