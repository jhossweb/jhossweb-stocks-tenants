<script setup lang="ts">
import { Repeat, X } from 'lucide-vue-next';
import { computed, watch } from 'vue';
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
import type { ConversionRates } from '@/composables/useCurrencyConversion';
import type { VueltoEntry } from '@/types/Finance/Payment';

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
    currencies: string[];
    bankAccounts: BankAccount[];
    docCurrency: string;
    errors: Record<string, string>;
    removable?: boolean;
    latestRate: any;
    rates: RateRecord[];
    paymentDate: string;
    remaining: number;
}>();

const emit = defineEmits<{
    remove: [];
    currencyChange: [];
    amountInput: [];
}>();

const vuelto = defineModel<VueltoEntry>({ required: true });

const { convertCurrency } = useCurrencyConversion();

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

const rateForDate = (date: string): ConversionRates => {
    const best = getBestRateForDate(date) ?? props.latestRate;

    return {
        rate_usd: best ? Number(best.rate_usd) : undefined,
        rate_cop: best ? Number(best.rate_cop) : undefined,
        rate_eur: best ? Number(best.rate_eur) : undefined,
        rate_cop_bs:
            best?.rate_cop_bs != null ? Number(best.rate_cop_bs) : undefined,
    };
};

const filteredBankAccounts = computed(() =>
    props.bankAccounts.filter(
        (ba) => ba.bank_currency === vuelto.value.vuelto_currency,
    ),
);

const equivalentInDoc = computed(() => {
    const amount = Number(vuelto.value.vuelto_amount) || 0;

    if (!amount || vuelto.value.vuelto_currency === props.docCurrency) {
        return amount;
    }

    return convertCurrency(
        amount,
        vuelto.value.vuelto_currency,
        props.docCurrency,
        rateForDate(props.paymentDate),
    );
});

const remainingInRowCurrency = computed(() =>
    convertCurrency(
        props.remaining,
        props.docCurrency,
        vuelto.value.vuelto_currency,
        rateForDate(props.paymentDate),
    ),
);

watch(
    () => vuelto.value.vuelto_currency,
    () => {
        if (
            vuelto.value.vuelto_bank_account_id &&
            !filteredBankAccounts.value.some(
                (ba) =>
                    String(ba.id) ===
                    String(vuelto.value.vuelto_bank_account_id),
            )
        ) {
            vuelto.value.vuelto_bank_account_id = '';
        }

        emit('currencyChange');
    },
);
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <span
                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
            >
                Vuelto
            </span>
            <button
                v-if="removable"
                type="button"
                class="rounded-md p-1.5 text-muted-foreground/40 transition-colors hover:text-destructive"
                title="Eliminar vuelto"
                @click="emit('remove')"
            >
                <X class="size-4" />
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Monto del vuelto
                </Label>
                <Input
                    v-model="vuelto.vuelto_amount"
                    type="number"
                    step="0.0001"
                    min="0"
                    placeholder="0.0000"
                    @input="emit('amountInput')"
                />
                <p
                    v-if="errors.vuelto_amount"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.vuelto_amount }}
                </p>
            </div>
            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Moneda
                </Label>
                <Select v-model="vuelto.vuelto_currency">
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
                    v-if="errors.vuelto_currency"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.vuelto_currency }}
                </p>
            </div>
            <div>
                <Label
                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                >
                    Banco y Cuenta
                </Label>
                <Select v-model="vuelto.vuelto_bank_account_id">
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
                    v-if="errors.vuelto_bank_account_id"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.vuelto_bank_account_id }}
                </p>
            </div>
        </div>

        <p v-if="remaining > 0" class="mt-3 text-xs text-muted-foreground">
            Restante del exceso para este vuelto:
            <strong>
                {{
                    remainingInRowCurrency.toLocaleString('en-US', {
                        minimumFractionDigits: 4,
                        maximumFractionDigits: 4,
                    })
                }}
                {{ vuelto.vuelto_currency }}
            </strong>
        </p>

        <div
            v-if="equivalentInDoc > 0 && vuelto.vuelto_currency !== docCurrency"
            class="mt-3 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3"
        >
            <div
                class="flex items-center gap-2 text-[10px] font-black tracking-wider text-amber-600 uppercase"
            >
                <Repeat class="size-4" />
                Conversión de Moneda
            </div>
            <div class="mt-1 text-xs text-muted-foreground">
                Equivalente en {{ docCurrency }}:
                <strong>
                    {{
                        equivalentInDoc.toLocaleString('en-US', {
                            minimumFractionDigits: 4,
                            maximumFractionDigits: 4,
                        })
                    }}
                </strong>
            </div>
        </div>
    </div>
</template>
