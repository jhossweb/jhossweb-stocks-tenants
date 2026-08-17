<script setup lang="ts">
import { Calendar, Plus } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import PaymentRow from '@/components/payments/PaymentRow.vue';
import VueltoRow from '@/components/payments/VueltoRow.vue';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import type { ConversionRates } from '@/composables/useCurrencyConversion';
import type { PaymentSectionModel, VueltoEntry } from '@/types/Finance/Payment';

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
    docTotal: number;
    allowExcess?: boolean;
    disabled?: boolean;
}>();

const model = defineModel<PaymentSectionModel>({ required: true });

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

const paymentDate = computed(() => model.value.payment_date);

const payments = computed(() => model.value.payments ?? []);

const rowErrors = (index: number): Record<string, string> => {
    const mapped: Record<string, string> = {};

    for (const [key, message] of Object.entries(props.errors ?? {})) {
        const prefix = `payment.payments.${index}.`;

        if (key.startsWith(prefix)) {
            mapped[key.slice(prefix.length)] = message;
        }
    }

    return mapped;
};

const globalError = computed(() => props.errors?.['payment.payments']);

const totalPaidInDoc = computed(() =>
    payments.value.reduce((acc, payment) => {
        if (!payment.payment_amount || Number(payment.payment_amount) <= 0) {
            return acc;
        }

        const rate = rateForDate(paymentDate.value);

        return (
            acc +
            convertCurrency(
                Number(payment.payment_amount),
                payment.payment_currency,
                props.docCurrency,
                rate,
            )
        );
    }, 0),
);

const remaining = computed(() =>
    Math.max(0, props.docTotal - totalPaidInDoc.value),
);

const isCovered = computed(() => {
    if (!props.docTotal) {
        return false;
    }

    if (props.allowExcess) {
        return (
            totalPaidInDoc.value - totalVueltoInCurrency.value + 0.005 >=
            props.docTotal
        );
    }

    return (
        totalPaidInDoc.value + 0.005 >= props.docTotal &&
        totalPaidInDoc.value - 0.005 <= props.docTotal
    );
});

const vueltoInDoc = computed(() =>
    props.allowExcess ? Math.max(0, totalPaidInDoc.value - props.docTotal) : 0,
);

const vueltos = computed<VueltoEntry[]>(() => model.value.vueltos ?? []);

const totalVueltoInCurrency = computed(() =>
    vueltos.value.reduce((acc, vuelto) => {
        const amount = Number(vuelto.vuelto_amount) || 0;

        if (!amount || vuelto.vuelto_currency === props.docCurrency) {
            return acc + amount;
        }

        return (
            acc +
            convertCurrency(
                amount,
                vuelto.vuelto_currency,
                props.docCurrency,
                rateForDate(paymentDate.value),
            )
        );
    }, 0),
);

const roundTo4 = (value: number): number =>
    Math.round(Number(value) * 10000) / 10000;

const autoPrefilled = ref<boolean[]>([]);

const createVueltoRow = (): VueltoEntry => ({
    vuelto_amount: '',
    vuelto_currency: props.docCurrency,
    vuelto_bank_account_id: '',
});

const ensureVueltos = () => {
    if (!model.value.vueltos || model.value.vueltos.length === 0) {
        model.value.vueltos = [createVueltoRow()];
        autoPrefilled.value = [false];
    }
};

const addVuelto = () => {
    ensureVueltos();

    model.value.vueltos?.push(createVueltoRow());
    autoPrefilled.value.push(false);
};

const removeVuelto = (index: number) => {
    if ((model.value.vueltos?.length ?? 0) <= 1) {
        return;
    }

    model.value.vueltos?.splice(index, 1);
    autoPrefilled.value.splice(index, 1);
};

const remainingFor = (index: number): number => {
    const rows = model.value.vueltos ?? [];
    const rate = rateForDate(paymentDate.value);

    const allocatedByOthers = rows.reduce((acc, vuelto, i) => {
        if (i === index) {
            return acc;
        }

        const amount = Number(vuelto.vuelto_amount) || 0;

        if (!amount) {
            return acc;
        }

        return (
            acc +
            convertCurrency(
                amount,
                vuelto.vuelto_currency,
                props.docCurrency,
                rate,
            )
        );
    }, 0);

    return Math.max(0, vueltoInDoc.value - allocatedByOthers);
};

const handleCurrencyChange = (index: number) => {
    const row = model.value.vueltos?.[index];

    if (!row) {
        return;
    }

    const isEmpty =
        row.vuelto_amount === '' ||
        row.vuelto_amount === null ||
        row.vuelto_amount === undefined;

    if (!autoPrefilled.value[index] && !isEmpty) {
        return;
    }

    row.vuelto_amount = roundTo4(
        convertCurrency(
            remainingFor(index),
            props.docCurrency,
            row.vuelto_currency,
            rateForDate(paymentDate.value),
        ),
    );
    autoPrefilled.value[index] = true;
};

const handleAmountInput = (index: number) => {
    autoPrefilled.value[index] = false;
};

const vueltoRowErrors = (index: number): Record<string, string> => {
    const mapped: Record<string, string> = {};

    for (const [key, message] of Object.entries(props.errors ?? {})) {
        const prefix = `payment.vueltos.${index}.`;

        if (key.startsWith(prefix)) {
            mapped[key.slice(prefix.length)] = message;
        }
    }

    return mapped;
};

onMounted(() => {
    if (props.allowExcess) {
        ensureVueltos();
    }
});

const maxAllowedFor = (index: number): number => {
    const current = payments.value[index];

    if (!current) {
        return 0;
    }

    const rate = rateForDate(paymentDate.value);
    const contribution = convertCurrency(
        Number(current.payment_amount) || 0,
        current.payment_currency,
        props.docCurrency,
        rate,
    );

    const remainingWithoutThis = Math.max(
        0,
        props.docTotal - (totalPaidInDoc.value - contribution),
    );

    return convertCurrency(
        remainingWithoutThis,
        props.docCurrency,
        current.payment_currency,
        rate,
    );
};

const addPayment = () => {
    const template = payments.value[0] ?? {
        payment_currency: props.docCurrency,
        payment_rate: '1',
        bank_account_id: '',
        payment_method: props.methods[0] || '',
        payment_reference: '',
        payment_description: '',
        payment_amount: 0,
    };

    model.value.payments.push({
        ...template,
        payment_amount: 0,
        payment_reference: '',
    });
};

const removePayment = (index: number) => {
    if (payments.value.length > 1) {
        model.value.payments.splice(index, 1);
    }
};
</script>

<template>
    <div class="flex flex-col gap-5">
        <div>
            <Label
                class="mb-1.5 block text-sm font-medium text-muted-foreground"
            >
                Fecha de Pago
            </Label>
            <div class="relative">
                <Calendar
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="model.payment_date"
                    type="date"
                    class="pl-10"
                    :disabled="disabled"
                />
            </div>
        </div>

        <div
            v-if="globalError"
            class="rounded-lg border border-destructive/50 bg-destructive/10 p-3 text-xs text-destructive"
        >
            {{ globalError }}
        </div>

        <div class="flex flex-col gap-4">
            <PaymentRow
                v-for="(payment, index) in payments"
                :key="index"
                v-model="model.payments[index]"
                :bank-accounts="bankAccounts"
                :latest-rate="latestRate"
                :rates="rates"
                :methods="methods"
                :currencies="currencies"
                :errors="rowErrors(index)"
                :doc-currency="docCurrency"
                :max-allowed="maxAllowedFor(index)"
                :payment-date="paymentDate"
                :removable="payments.length > 1"
                @remove="removePayment(index)"
            />
        </div>

        <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-dashed border-primary/40 px-4 py-2.5 text-sm font-semibold text-primary transition-colors hover:border-primary hover:bg-primary/5"
            :disabled="disabled"
            @click="addPayment"
        >
            <Plus class="size-4" />
            Agregar pago
        </button>

        <div
            v-if="allowExcess"
            class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4"
        >
            <div class="mb-3 flex items-center gap-2">
                <span
                    class="text-xs font-bold tracking-wider text-amber-600 uppercase"
                >
                    Vuelto a entregar (exceso: {{ vueltoInDoc.toFixed(4) }}
                    {{ docCurrency }})
                </span>
            </div>

            <div class="flex flex-col gap-4">
                <VueltoRow
                    v-for="(vuelto, index) in vueltos"
                    :key="index"
                    v-model="model.vueltos![index]"
                    :bank-accounts="bankAccounts"
                    :latest-rate="latestRate"
                    :rates="rates"
                    :currencies="currencies"
                    :doc-currency="docCurrency"
                    :errors="vueltoRowErrors(index)"
                    :payment-date="paymentDate"
                    :remaining="remainingFor(index)"
                    :removable="vueltos.length > 1"
                    @remove="removeVuelto(index)"
                    @currency-change="handleCurrencyChange(index)"
                    @amount-input="handleAmountInput(index)"
                />
            </div>

            <button
                type="button"
                class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg border border-dashed border-amber-500/40 px-4 py-2 text-sm font-semibold text-amber-600 transition-colors hover:border-amber-500 hover:bg-amber-500/5"
                :disabled="disabled"
                @click="addVuelto"
            >
                <Plus class="size-4" />
                Agregar vuelto en otra moneda
            </button>

            <p
                v-if="totalVueltoInCurrency > 0"
                class="mt-3 text-xs text-muted-foreground"
            >
                Total a devolver en {{ docCurrency }}:
                <strong>
                    {{
                        totalVueltoInCurrency.toLocaleString('en-US', {
                            minimumFractionDigits: 4,
                            maximumFractionDigits: 4,
                        })
                    }}
                </strong>
            </p>
        </div>

        <div class="rounded-xl border bg-muted/30 p-4">
            <div class="flex flex-col gap-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground"
                        >Total del documento</span
                    >
                    <span class="font-bold tabular-nums">
                        {{
                            Number(docTotal).toLocaleString('en-US', {
                                minimumFractionDigits: 4,
                                maximumFractionDigits: 4,
                            })
                        }}
                        {{ docCurrency }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Total pagado</span>
                    <span class="font-bold tabular-nums">
                        {{
                            totalPaidInDoc.toLocaleString('en-US', {
                                minimumFractionDigits: 4,
                                maximumFractionDigits: 4,
                            })
                        }}
                        {{ docCurrency }}
                    </span>
                </div>
                <div v-if="allowExcess" class="flex justify-between">
                    <span class="text-muted-foreground"
                        >Aplicado a la factura</span
                    >
                    <span
                        class="font-bold tabular-nums"
                        :class="isCovered ? 'text-green-600' : 'text-amber-600'"
                    >
                        {{
                            Math.max(
                                0,
                                totalPaidInDoc - totalVueltoInCurrency,
                            ).toLocaleString('en-US', {
                                minimumFractionDigits: 4,
                                maximumFractionDigits: 4,
                            })
                        }}
                        {{ docCurrency }}
                    </span>
                </div>
                <div v-else class="flex justify-between">
                    <span class="text-muted-foreground">Restante</span>
                    <span
                        class="font-bold tabular-nums"
                        :class="isCovered ? 'text-green-600' : 'text-amber-600'"
                    >
                        {{
                            remaining.toLocaleString('en-US', {
                                minimumFractionDigits: 4,
                                maximumFractionDigits: 4,
                            })
                        }}
                        {{ docCurrency }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
