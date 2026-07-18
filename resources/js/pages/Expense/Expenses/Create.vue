<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Landmark, Calculator } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import FormActions from '@/components/Form/FormActions.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';
import { today } from '@/composables/useDate';
import type { ExpenseCategory, BankAccount, LatestRate } from '@/types/Expense';

interface RateData {
    rate_date: string;
    rate_usd: number;
    rate_cop: number;
    rate_eur: number;
}

const props = defineProps<{
    categories: ExpenseCategory[];
    coinOptions: string[];
    bankAccounts: BankAccount[];
    latestRate: LatestRate;
    rates: RateData[];
    methods: string[];
}>();

const form = useForm({
    exp_category_id: '',
    exp_description: '',
    exp_amount: '',
    exp_date: today(),
    exp_coin: 'USD',
    exp_valorcam: 1,
});

const valorcam = ref(props.latestRate ? Number(props.latestRate.rate_usd) : 1);

const getBestRateForDate = (date: string): RateData | null => {
    if (!props.rates || props.rates.length === 0) return null;

    const exact = props.rates.find(r => r.rate_date === date);
    if (exact) return exact;

    const past = props.rates
        .filter(r => r.rate_date < date)
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

    return past.length > 0 ? past[0] : props.rates[0];
};

const resolveRateValue = (coin: string, rate: RateData | LatestRate): number => {
    if (coin === 'USD' || coin === 'BS' || coin === 'VES') return Number(rate.rate_usd);
    if (coin === 'COP') return Number(rate.rate_cop);
    if (coin === 'EUR') return Number(rate.rate_eur);
    return Number(rate.rate_usd);
};

const handleExchangeUpdate = () => {
    const todayStr = new Date().toISOString().split('T')[0];
    let newRate: number;

    if (form.exp_date === todayStr && props.latestRate) {
        newRate = resolveRateValue(form.exp_coin, props.latestRate);
    } else {
        const selected = getBestRateForDate(form.exp_date);
        newRate = selected ? resolveRateValue(form.exp_coin, selected) : 1;
    }

    valorcam.value = Number(newRate.toFixed(4));
    form.exp_valorcam = valorcam.value;
};

const updatePriceFromRate = () => {
    form.exp_valorcam = valorcam.value;
};

const submit = () => {
    form.exp_valorcam = valorcam.value;
    form.post('/expenses', {
        preserveScroll: true,
    });
};

const amountFormatted = (): number => {
    return Number(form.exp_amount) || 0;
};

onMounted(() => {
    handleExchangeUpdate();
});
</script>

<template>
    <Head title="Nuevo Gasto" />
    <FlashMessage />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <FormActions
            title="Nuevo Gasto"
            subtitle="Registra un nuevo gasto operativo."
            back-url="/expenses"
            :processing="form.processing"
            @save="submit"
        />

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Hay problemas con el envío:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li
                    v-for="(error, key) in form.errors"
                    :key="key"
                >
                    {{ error }}
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-4">
                <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <Landmark class="size-4 text-primary" />
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Configuración General
                        </span>
                    </div>
                    <div class="flex flex-col gap-5 p-6">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Categoría</label>
                            <select v-model="form.exp_category_id" class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                <option value="">Seleccione categoría</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.exc_name }} ({{ cat.exc_type }})</option>
                            </select>
                            <InputError :message="form.errors.exp_category_id" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Monto</label>
                            <Input type="number" step="0.01" min="0.01" v-model="form.exp_amount" class="w-full" placeholder="0.00" />
                            <InputError :message="form.errors.exp_amount" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha</label>
                                <Input type="date" v-model="form.exp_date" class="w-full" @change="handleExchangeUpdate" />
                                <InputError :message="form.errors.exp_date" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Moneda</label>
                                <select v-model="form.exp_coin" class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" @change="handleExchangeUpdate">
                                    <option v-for="coin in coinOptions" :key="coin" :value="coin">{{ coin }}</option>
                                </select>
                                <InputError :message="form.errors.exp_coin" />
                            </div>
                        </div>

                        <div class="rounded-lg border bg-muted/30 p-4">
                            <label class="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Tasa de Cambio</label>
                            <div class="mb-2 text-xs text-muted-foreground">
                                Tasa del día: <span class="font-semibold text-primary">{{ valorcam }}</span>
                            </div>
                            <Input
                                v-model="valorcam"
                                type="number"
                                step="0.0001"
                                class="font-mono text-sm font-bold"
                                @input="updatePriceFromRate"
                            />
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg">
                    <Calculator class="absolute -bottom-4 -right-4 size-24 text-primary-foreground/10" />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>MONTO</span>
                            <span class="font-bold">{{ amountFormatted().toFixed(2) }}</span>
                        </div>
                        <div class="flex items-end justify-between border-t border-primary-foreground/10 pt-3">
                            <span class="text-sm font-bold uppercase">Total {{ form.exp_coin }}</span>
                            <span class="text-3xl font-black tabular-nums">{{ amountFormatted().toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Descripción
                        </span>
                    </div>
                    <div class="p-6">
                        <textarea v-model="form.exp_description" rows="6" class="bg-background border-input flex w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" placeholder="Describa el gasto"></textarea>
                        <InputError :message="form.errors.exp_description" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
