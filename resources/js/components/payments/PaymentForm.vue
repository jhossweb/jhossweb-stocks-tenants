<script setup lang="ts">
import {
    AlertCircle,
    Calendar,
    DollarSign,
    Landmark,
    Plus,
    Repeat,
} from 'lucide-vue-next';
import { watch, computed, ref } from 'vue';
import BankAccountCreateModal from '@/components/payments/BankAccountCreateModal.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
    formatted_balance?: string;
}

interface RateRecord {
    rate_date: string;
    rate_usd: number;
    rate_cop: number;
    rate_eur: number;
    rate_cop_bs?: number;
}

const props = defineProps<{
    bankAccounts: BankAccount[];
    latestRate: any;
    rates?: RateRecord[];
    currency: string;
    remainingBalance: number;
    form: any;
    errors: Record<string, string>;
    processing: boolean;
    submitLabel?: string;
    showRate?: boolean;
    showDate?: boolean;
    showMethod?: boolean;
    methods?: string[];
    currencies?: string[];
    disableFields?: boolean;
}>();

defineEmits<{
    submit: [];
}>();

const { convertCurrency } = useCurrencyConversion();

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

watch(
    () => props.form.payment_currency,
    () => {
        const isInFiltered = filteredBankAccounts.value.some(
            (ba) => ba.id == props.form.bank_account_id,
        );

        if (!isInFiltered) {
            props.form.bank_account_id = '';
        }
    },
);

const bankModalRef = ref<InstanceType<typeof BankAccountCreateModal> | null>(
    null,
);

const openBankModal = () => {
    bankModalRef.value?.open();
};

const onBankCreated = (bank: { id: number }) => {
    Object.assign(props.form, { bank_account_id: String(bank.id) });
};

const getBestRateForDate = (date: string): RateRecord | null => {
    if (!props.rates || props.rates.length === 0) {
        return props.latestRate || null;
    }

    const normalized = date.substring(0, 10);
    const exactRate = props.rates.find(
        (r) => r.rate_date.substring(0, 10) === normalized,
    );

    if (exactRate) {
        return exactRate;
    }

    const pastRates = props.rates
        .filter((r) => r.rate_date.substring(0, 10) < normalized)
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

    return pastRates.length > 0 ? pastRates[0] : props.rates[0];
};

const maxAmount = computed(() => {
    const balance = props.remainingBalance;

    if (!balance) {
        return 0;
    }

    const fromCurrency = props.currency;
    const toCurrency = props.form.payment_currency;

    if (!toCurrency || fromCurrency === toCurrency) {
        return balance;
    }

    const currentRate =
        getBestRateForDate(props.form.payment_date) || props.latestRate || {};

    return convertCurrency(balance, fromCurrency, toCurrency, currentRate);
});

const resolvePaymentRate = (
    expenseCoin: string,
    paymentCoin: string,
    rateRecord: any,
): number => {
    const e = expenseCoin?.toUpperCase() || '';
    const p = paymentCoin?.toUpperCase() || '';

    if (!p || e === p) {
        if (e === 'USD') {
            return Number(rateRecord.rate_usd) || 1;
        }

        if (e === 'COP') {
            return Number(rateRecord.rate_cop) || 1;
        }

        if (e === 'BS' || e === 'VES') {
            return Number(rateRecord.rate_usd) || 1;
        }

        if (e === 'EUR') {
            return Number(rateRecord.rate_eur) || 1;
        }

        return Number(rateRecord.rate_usd) || 1;
    }

    const isBS = (c: string) => c === 'BS' || c === 'VES';

    if ((e === 'COP' && isBS(p)) || (isBS(e) && p === 'COP')) {
        return Number(rateRecord.rate_cop_bs) || 1;
    }

    if (p === 'USD') {
        if (e === 'COP') {
            return Number(rateRecord.rate_cop) || 1;
        }

        if (isBS(e)) {
            return Number(rateRecord.rate_usd) || 1;
        }

        if (e === 'EUR') {
            return Number(rateRecord.rate_eur) || 1;
        }

        return Number(rateRecord.rate_usd) || 1;
    }

    if (p === 'COP') {
        return Number(rateRecord.rate_cop) || 1;
    }

    if (isBS(p)) {
        return Number(rateRecord.rate_usd) || 1;
    }

    if (p === 'EUR') {
        return Number(rateRecord.rate_eur) || 1;
    }

    return Number(rateRecord.rate_usd) || 1;
};

const updateExchangeRate = () => {
    const expenseCoin = props.currency;
    const payCoin = props.form.payment_currency;
    const date = props.form.payment_date;

    const rateRecord =
        props.rates && props.rates.length > 0 && date
            ? getBestRateForDate(date) || props.latestRate
            : props.latestRate;

    if (!rateRecord) {
        return;
    }

    props.form.payment_rate = String(
        resolvePaymentRate(expenseCoin, payCoin, rateRecord),
    );
};

watch(
    () => props.form.payment_currency,
    () => updateExchangeRate(),
);
watch(
    () => props.form.payment_date,
    () => updateExchangeRate(),
);
</script>

<template>
    <div class="space-y-5">
        <!-- Error Banner -->
        <div
            v-if="Object.keys(errors).length > 0"
            class="flex items-start gap-3 rounded-xl border border-red-500/20 bg-red-500/10 p-4"
        >
            <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
            <div>
                <h4 class="text-sm font-bold text-red-500">
                    Corrige los siguientes errores:
                </h4>
                <ul
                    class="mt-1 list-inside list-disc space-y-1 text-xs text-red-400"
                >
                    <li v-for="(error, key) in errors" :key="key">
                        {{ error }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Entity Selector Slot -->
        <slot name="entity-selector" />

        <div v-if="showDate || showRate" class="grid grid-cols-2 gap-4">
            <div v-if="showDate" class="space-y-2">
                <Label class="text-xs font-semibold text-zinc-400"
                    >Fecha de Pago</Label
                >
                <div class="relative">
                    <Calendar
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                    />
                    <Input
                        type="date"
                        v-model="form.payment_date"
                        :disabled="disableFields"
                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50 pl-10 text-sm focus:ring-indigo-600"
                    />
                </div>
                <span
                    v-if="errors.payment_date"
                    class="mt-1 block text-xs font-bold text-red-500"
                    >{{ errors.payment_date }}</span
                >
            </div>

            <div v-if="showRate" class="space-y-2">
                <Label class="text-xs font-semibold text-zinc-400"
                    >Moneda de Pago</Label
                >
                <Select
                    v-model="form.payment_currency"
                    :disabled="disableFields"
                >
                    <SelectTrigger
                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50"
                    >
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
                    class="mt-1 block text-xs font-bold text-red-500"
                    >{{ errors.payment_currency }}</span
                >
            </div>
        </div>

        <!-- Rate -->
        <div
            v-if="showRate"
            class="space-y-2 rounded-2xl border border-indigo-500/10 bg-indigo-500/5 p-4"
        >
            <div class="mb-1 flex items-center justify-between">
                <Label
                    class="text-[10px] font-black tracking-wider text-indigo-400 uppercase"
                    >Tasa de Cambio</Label
                >
            </div>
            <div class="relative">
                <DollarSign
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-indigo-400/50"
                />
                <Input
                    type="number"
                    step="0.0001"
                    v-model="form.payment_rate"
                    :disabled="disableFields"
                    class="h-11 rounded-xl border-zinc-800 bg-zinc-950 pl-10 font-mono text-sm font-bold text-indigo-400 focus:ring-indigo-600 disabled:opacity-50"
                />
            </div>
            <span
                v-if="errors.payment_rate"
                class="mt-1 block text-xs font-bold text-red-500"
                >{{ errors.payment_rate }}</span
            >
        </div>

        <!-- Conversion Notice -->
        <div
            v-if="showRate && currency !== form.payment_currency"
            class="space-y-3 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4"
        >
            <div
                class="flex items-center gap-2 text-[10px] font-black tracking-wider text-amber-400 uppercase"
            >
                <Repeat class="h-4 w-4" /> Conversión de Moneda
            </div>
            <div class="flex items-baseline justify-between">
                <span class="font-semibold text-zinc-300">Total a pagar</span>
                <span class="text-lg font-black text-amber-400"
                    >{{
                        maxAmount.toLocaleString('en-US', {
                            minimumFractionDigits: 4,
                            maximumFractionDigits: 4,
                        })
                    }}
                    {{ form.payment_currency }}</span
                >
            </div>
            <div class="text-right text-xs text-zinc-500">
                (equivalente a
                {{
                    Number(remainingBalance).toLocaleString('en-US', {
                        minimumFractionDigits: 4,
                    })
                }}
                {{ currency }})
            </div>
        </div>

        <!-- Bank Account -->
        <div class="space-y-2">
            <Label class="text-xs font-semibold tracking-wide text-zinc-400"
                >Banco y Cuenta</Label
            >
            <div class="flex items-center gap-2">
                <Select
                    v-model="form.bank_account_id"
                    :disabled="disableFields"
                    class="flex-1"
                >
                    <SelectTrigger
                        class="h-12 rounded-xl border-zinc-800 bg-zinc-950/50 focus:ring-indigo-600"
                    >
                        <SelectValue placeholder="Seleccione cuenta bancaria" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="ba in filteredBankAccounts"
                            :key="ba.id"
                            :value="ba.id.toString()"
                        >
                            <div class="flex flex-col items-start py-0.5">
                                <span class="font-bold text-zinc-200">{{
                                    ba.bank_name
                                }}</span>
                                <span
                                    class="font-mono text-[10px] text-zinc-500"
                                    >{{ ba.bank_currency }}</span
                                >
                            </div>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    :disabled="disableFields"
                    @click="openBankModal"
                    class="h-12 w-12 shrink-0 border-zinc-800 bg-zinc-950/50 text-zinc-400 transition-all hover:border-indigo-500/40 hover:bg-indigo-500/10 hover:text-indigo-400"
                    title="Crear nueva cuenta bancaria"
                >
                    <Plus class="h-4 w-4" />
                </Button>
            </div>
            <span
                v-if="errors.bank_account_id"
                class="mt-1 block text-xs font-bold text-red-500"
                >{{ errors.bank_account_id }}</span
            >
        </div>

        <!-- Amount -->
        <div class="space-y-2">
            <Label class="text-xs font-semibold tracking-wide text-zinc-400">
                Monto a Pagar
                <span class="font-normal text-zinc-600"
                    >(en moneda del banco)</span
                >
            </Label>
            <Input
                type="number"
                step="0.0001"
                min="0.0001"
                v-model="form.amount"
                :disabled="disableFields"
                class="h-12 rounded-xl border-zinc-800 bg-zinc-950/50 text-right text-lg font-bold tabular-nums focus:ring-indigo-600"
                :placeholder="remainingBalance.toString()"
            />
            <div class="flex justify-between text-xs">
                <span class="text-zinc-500"
                    >Saldo pendiente:
                    <strong class="text-zinc-300"
                        >{{
                            Number(remainingBalance).toLocaleString('en-US', {
                                minimumFractionDigits: 4,
                                maximumFractionDigits: 4,
                            })
                        }}
                        {{ currency }}</strong
                    ></span
                >
                <button
                    type="button"
                    @click="form.amount = maxAmount"
                    class="text-[10px] font-black tracking-wider text-indigo-500 uppercase hover:text-indigo-400"
                >
                    Máximo
                </button>
            </div>
            <span
                v-if="errors.amount"
                class="mt-1 block text-xs font-bold text-red-500"
                >{{ errors.amount }}</span
            >
        </div>

        <!-- Method -->
        <div v-if="showMethod" class="space-y-2">
            <Label class="text-xs font-semibold tracking-wide text-zinc-400"
                >Tipo de Transacción</Label
            >
            <Select v-model="form.payment_method" :disabled="disableFields">
                <SelectTrigger
                    class="h-12 rounded-xl border-zinc-800 bg-zinc-950/50"
                >
                    <SelectValue placeholder="Método de pago" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="m in methods" :key="m" :value="m">{{
                        m
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <span
                v-if="errors.payment_method"
                class="mt-1 block text-xs font-bold text-red-500"
                >{{ errors.payment_method }}</span
            >
        </div>

        <!-- Items Table Slot -->
        <slot name="items-table" />

        <!-- Reference -->
        <div class="space-y-2">
            <Label class="text-xs font-semibold tracking-wide text-zinc-400"
                >Referencia / Voucher</Label
            >
            <Input
                v-model="form.reference"
                :disabled="disableFields"
                placeholder="N° de transacción o referencia"
                class="h-12 rounded-xl border-zinc-800 bg-zinc-950/50"
                maxlength="50"
            />
            <span
                v-if="errors.reference"
                class="mt-1 block text-xs font-bold text-red-500"
                >{{ errors.reference }}</span
            >
        </div>

        <!-- Description -->
        <div class="space-y-2">
            <Label class="text-xs font-semibold tracking-wide text-zinc-400"
                >Observaciones</Label
            >
            <Input
                v-model="form.description"
                :disabled="disableFields"
                placeholder="Opcional"
                class="h-12 rounded-xl border-zinc-800 bg-zinc-950/50"
                maxlength="150"
            />
            <span
                v-if="errors.description"
                class="mt-1 block text-xs font-bold text-red-500"
                >{{ errors.description }}</span
            >
        </div>

        <!-- Extra Fields Slot -->
        <slot name="after-fields" />

        <!-- Bank Balance Info -->
        <div
            v-if="selectedBankAccount"
            class="mt-4 flex items-center gap-4 rounded-xl border p-4"
            :class="
                Number(selectedBankAccount.bank_balance) >=
                Number(form.amount || 0)
                    ? 'border-green-500/10 bg-green-500/5'
                    : 'border-red-500/10 bg-red-500/5'
            "
        >
            <div
                :class="
                    Number(selectedBankAccount.bank_balance) >=
                    Number(form.amount || 0)
                        ? 'bg-green-500/20 text-green-500'
                        : 'bg-red-500/20 text-red-500'
                "
                class="rounded-lg p-2.5"
            >
                <Landmark class="h-5 w-5" />
            </div>
            <div class="flex-1">
                <p class="text-[10px] font-black text-zinc-500 uppercase">
                    Saldo Disponible en Banco
                </p>
                <p class="text-sm font-bold text-zinc-200 antialiased">
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
                class="flex items-center gap-1.5 rounded-full bg-red-500/10 px-3 py-1 text-[10px] font-black text-red-500 uppercase"
            >
                <AlertCircle class="h-3 w-3" /> Fondos Insuficientes
            </div>
        </div>

        <!-- Submit Button (slot override or default) -->
        <div v-if="$slots.actions" class="pt-2">
            <slot name="actions" />
        </div>

        <BankAccountCreateModal
            ref="bankModalRef"
            :currency="form.payment_currency"
            @created="onBankCreated"
        />
    </div>
</template>

<style scoped>
input[type='number']::-webkit-outer-spin-button,
input[type='number']::-webkit-inner-spin-button {
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
