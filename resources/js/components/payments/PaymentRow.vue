<script setup lang="ts">
import { DollarSign, Landmark, Repeat, X } from 'lucide-vue-next';
import { computed, onMounted, watch } from 'vue';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
}

interface RateRecord {
    rate_date: string;
    rate_usd: number | string;
    rate_cop: number | string;
    rate_eur: number | string;
    rate_cop_bs?: number | string;
}

const props = defineProps<{
    bankAccounts: BankAccount[];
    latestRate: any;
    rates: RateRecord[];
    methods: string[];
    currencies: string[];
    errors: Record<string, string>;
    docCurrency: string;
    maxAllowed: number;
    paymentDate: string;
    removable?: boolean;
}>();

const emit = defineEmits<{
    remove: [];
}>();

const payment = defineModel<{
    payment_currency: string;
    payment_rate: string | number;
    bank_account_id: string;
    payment_method: string;
    payment_reference: string;
    payment_description: string;
    payment_amount: number | string;
}>({ required: true });

const getBestRateForDate = (date: string): RateRecord | null => {
    if (!props.rates || props.rates.length === 0) {
        return props.latestRate || null;
    }

    const normalized = date.substring(0, 10);
    const exact = props.rates.find(
        (r) => String(r.rate_date).substring(0, 10) === normalized,
    );

    if (exact) {
        return exact;
    }

    const past = props.rates
        .filter((r) => String(r.rate_date).substring(0, 10) < normalized)
        .sort((a, b) => String(b.rate_date).localeCompare(String(a.rate_date)));

    return past.length > 0 ? past[0] : props.rates[0];
};

const filteredBankAccounts = computed(() =>
    props.bankAccounts.filter(
        (ba) => ba.bank_currency === payment.value.payment_currency,
    ),
);

const isBS = (c: string) => c === 'BS' || c === 'VES';

const resolvePaymentRate = (
    docCoin: string,
    payCoin: string,
    rate: any,
): number => {
    const e = docCoin?.toUpperCase() || '';
    const p = payCoin?.toUpperCase() || '';

    if (!p || e === p) {
        if (e === 'USD' || isBS(e)) {
            return Number(rate.rate_usd) || 1;
        }

        if (e === 'COP') {
            return Number(rate.rate_cop) || 1;
        }

        if (e === 'EUR') {
            return Number(rate.rate_eur) || 1;
        }

        return Number(rate.rate_usd) || 1;
    }

    if ((e === 'COP' && isBS(p)) || (isBS(e) && p === 'COP')) {
        return Number(rate.rate_cop_bs) || 1;
    }

    if (p === 'USD') {
        if (e === 'COP') {
            return Number(rate.rate_cop) || 1;
        }

        if (isBS(e)) {
            return Number(rate.rate_usd) || 1;
        }

        if (e === 'EUR') {
            return Number(rate.rate_eur) || 1;
        }

        return Number(rate.rate_usd) || 1;
    }

    if (p === 'COP') {
        return Number(rate.rate_cop) || 1;
    }

    if (isBS(p)) {
        return Number(rate.rate_usd) || 1;
    }

    if (p === 'EUR') {
        return Number(rate.rate_eur) || 1;
    }

    return Number(rate.rate_usd) || 1;
};

const updateExchangeRate = () => {
    const rate = getBestRateForDate(props.paymentDate) || props.latestRate;

    if (!rate) {
        return;
    }

    payment.value.payment_rate = String(
        resolvePaymentRate(
            props.docCurrency,
            payment.value.payment_currency,
            rate,
        ),
    );
};

watch(
    () => payment.value.payment_currency,
    () => {
        if (
            !filteredBankAccounts.value.some(
                (ba) => String(ba.id) === String(payment.value.bank_account_id),
            )
        ) {
            payment.value.bank_account_id = '';
        }

        updateExchangeRate();
    },
);

watch(
    () => props.paymentDate,
    () => updateExchangeRate(),
);

onMounted(() => {
    updateExchangeRate();
});

const showsConversion = computed(
    () => props.docCurrency !== payment.value.payment_currency,
);

const setMax = () => {
    payment.value.payment_amount = String(
        Math.round(Number(props.maxAllowed) * 10000) / 10000,
    );
};
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <span
                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
            >
                Pago
            </span>
            <button
                v-if="removable"
                type="button"
                class="rounded-md p-1.5 text-muted-foreground/40 transition-colors hover:text-destructive"
                title="Eliminar pago"
                @click="emit('remove')"
            >
                <X class="size-4" />
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Moneda de Pago
                </Label>
                <Select v-model="payment.payment_currency">
                    <SelectTrigger>
                        <SelectValue placeholder="Moneda" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="c in currencies" :key="c" :value="c">
                            {{ c }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p
                    v-if="errors.payment_currency"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.payment_currency }}
                </p>
            </div>

            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Tasa de Cambio
                </Label>
                <div class="relative">
                    <DollarSign
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="payment.payment_rate"
                        type="number"
                        step="0.0001"
                        class="pl-10 font-mono text-sm font-bold"
                    />
                </div>
                <p
                    v-if="errors.payment_rate"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.payment_rate }}
                </p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Banco y Cuenta
                </Label>
                <Select v-model="payment.bank_account_id">
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccione cuenta bancaria" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="ba in filteredBankAccounts"
                            :key="ba.id"
                            :value="String(ba.id)"
                        >
                            <div class="flex flex-col items-start py-0.5">
                                <span class="font-bold">{{
                                    ba.bank_name
                                }}</span>
                                <span
                                    class="font-mono text-[10px] text-muted-foreground"
                                >
                                    {{ ba.bank_currency }}
                                </span>
                            </div>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p
                    v-if="errors.bank_account_id"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.bank_account_id }}
                </p>
            </div>

            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Monto a Pagar
                    <span class="font-normal text-muted-foreground/60">
                        (en moneda del banco)
                    </span>
                </Label>
                <div class="relative">
                    <Landmark
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="payment.payment_amount"
                        type="number"
                        step="0.0001"
                        min="0.0001"
                        class="pl-10 text-right text-lg font-bold tabular-nums"
                        :placeholder="String(maxAllowed)"
                    />
                </div>
                <div class="mt-1 flex justify-between text-xs">
                    <span class="text-muted-foreground">
                        Total {{ docCurrency }}:
                        <strong>
                            {{
                                Number(maxAllowed).toLocaleString('en-US', {
                                    minimumFractionDigits: 4,
                                    maximumFractionDigits: 4,
                                })
                            }}
                        </strong>
                    </span>
                    <button
                        type="button"
                        class="text-xs font-bold text-primary uppercase"
                        @click="setMax"
                    >
                        Máximo
                    </button>
                </div>
                <p
                    v-if="errors.payment_amount"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.payment_amount }}
                </p>
            </div>
        </div>

        <div
            v-if="showsConversion"
            class="mt-3 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3"
        >
            <div
                class="flex items-center gap-2 text-[10px] font-black tracking-wider text-amber-600 uppercase"
            >
                <Repeat class="size-4" />
                Conversión de Moneda
            </div>
            <div class="mt-1 text-xs text-muted-foreground">
                Pagando en {{ payment.payment_currency }} para un documento en
                {{ docCurrency }}. Tasa aplicada: {{ payment.payment_rate }}
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Método de Pago
                </Label>
                <Select v-model="payment.payment_method">
                    <SelectTrigger>
                        <SelectValue placeholder="Método de pago" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="m in methods" :key="m" :value="m">
                            {{ m }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p
                    v-if="errors.payment_method"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.payment_method }}
                </p>
            </div>

            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Referencia / Voucher
                </Label>
                <Input
                    v-model="payment.payment_reference"
                    type="text"
                    maxlength="50"
                    placeholder="N° de transacción o referencia"
                />
                <p
                    v-if="errors.payment_reference"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.payment_reference }}
                </p>
            </div>
        </div>

        <div class="mt-4">
            <Label
                class="mb-1.5 block text-sm font-medium text-muted-foreground"
            >
                Observaciones
            </Label>
            <Input
                v-model="payment.payment_description"
                type="text"
                maxlength="150"
                placeholder="Opcional"
            />
            <p
                v-if="errors.payment_description"
                class="mt-1 text-xs text-destructive"
            >
                {{ errors.payment_description }}
            </p>
        </div>
    </div>
</template>
