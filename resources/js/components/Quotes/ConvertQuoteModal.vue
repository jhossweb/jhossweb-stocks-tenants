<script setup lang="ts">
import { useForm, usePage, router } from '@inertiajs/vue3';
import { Loader2, FileText } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import PayModal from '@/components/payments/PayModal.vue';
import StockAvailabilityAlert from '@/components/Quotes/StockAvailabilityAlert.vue';
import { Button } from '@/components/ui/button';
import type { QuoteAvailability } from '@/types/Quotes';

const props = defineProps<{
    open: boolean;
    quote: {
        id: number;
        sls_number: string;
        sls_total: string;
        sls_coin: string;
        sls_date: string;
        customer?: { clt_name?: string; clt_credit_days?: number };
    } | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    converted: [];
}>();

const page = usePage();

const availability = computed<QuoteAvailability | undefined>(
    () => (page.props.flash as any)?.quote_availability,
);

const form = useForm({
    sls_number: '',
    sls_date: '',
    sls_payment_condition: 'cash',
    sls_credit_days: 0,
    sls_due_date: '',
});

const serverError = computed(
    () => (form.errors as Record<string, string>).error,
);

const isCredit = computed(
    () => form.sls_payment_condition === 'credit',
);

const updateDueDate = () => {
    const base =
        props.quote?.sls_date || new Date().toISOString().split('T')[0];

    if (!base) {
        return;
    }

    const [year, month, day] = base
        .substring(0, 10)
        .split('-')
        .map(Number);

    if (!year || !month || !day) {
        return;
    }

    const date = new Date(year, month - 1, day);
    date.setDate(date.getDate() + Number(form.sls_credit_days || 0));
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    form.sls_due_date = `${y}-${m}-${d}`;
};

const selectCondition = (cond: 'cash' | 'credit') => {
    form.sls_payment_condition = cond;

    if (cond === 'cash') {
        form.sls_credit_days = 0;
    }

    updateDueDate();
};

const conditionClasses = (cond: 'cash' | 'credit') => {
    const base =
        'flex flex-col items-start rounded-lg border px-4 py-3 text-left transition-colors';

    return form.sls_payment_condition === cond
        ? `${base} border-indigo-500 bg-indigo-500/15 text-white`
        : `${base} border-zinc-700 bg-zinc-950 text-zinc-400 hover:border-zinc-500`;
};

const initialize = () => {
    form.reset();

    if (!props.quote) {
        return;
    }

    const creditDays = Number(props.quote.customer?.clt_credit_days ?? 0);
    form.sls_date = props.quote.sls_date || new Date().toISOString().split('T')[0];
    form.sls_payment_condition = creditDays > 0 ? 'credit' : 'cash';
    form.sls_credit_days = creditDays > 0 ? creditDays : 0;
    updateDueDate();
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            initialize();
        }
    },
);

const submit = () => {
    if (!props.quote) {
        return;
    }

    form.post(`/quotes/${props.quote.id}/convert`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('update:open', false);
            emit('converted');
        },
    });
};

const goContado = () => {
    if (!props.quote) {
        return;
    }

    const params = new URLSearchParams({
        from_quote: String(props.quote.id),
    });

    if (form.sls_number.trim()) {
        params.set('sls_number', form.sls_number.trim());
    }

    router.get(`/sales/create?${params.toString()}`);
};

const close = () => {
    form.reset();
    emit('update:open', false);
};
</script>

<template>
    <PayModal
        :open="open"
        title="Convertir Cotización a Factura"
        @close="close"
        @update:open="emit('update:open', $event)"
    >
        <template #summary>
            <div v-if="quote" class="flex items-center gap-3">
                <div
                    class="grid size-11 place-items-center rounded-lg bg-indigo-500/15 text-indigo-400"
                >
                    <FileText class="size-5" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-zinc-100">
                        {{ quote.sls_number }}
                    </p>
                    <p class="text-xs text-zinc-500">
                        Total: {{ quote.sls_total }} {{ quote.sls_coin }}
                    </p>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-4">
            <StockAvailabilityAlert
                v-if="availability && !availability.ok"
                :availability="availability"
            />

            <p
                v-if="serverError"
                class="rounded-lg border border-red-900/50 bg-red-950/40 px-3 py-2 text-sm text-red-400"
            >
                {{ serverError }}
            </p>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-200">
                    N° Factura
                </label>
                <input
                    v-model="form.sls_number"
                    type="text"
                    placeholder="Ej: FACV-0001"
                    class="flex h-10 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                />
                <p
                    v-if="form.errors.sls_number"
                    class="mt-1 text-xs text-red-400"
                >
                    {{ form.errors.sls_number }}
                </p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-200">
                    Condición de Pago
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        :class="conditionClasses('cash')"
                        @click="selectCondition('cash')"
                    >
                        <span class="block text-sm font-semibold"
                            >Contado</span
                        >
                        <span class="mt-0.5 block text-[11px] opacity-70"
                            >Pago al momento</span
                        >
                    </button>
                    <button
                        type="button"
                        :class="conditionClasses('credit')"
                        @click="selectCondition('credit')"
                    >
                        <span class="block text-sm font-semibold"
                            >Crédito</span
                        >
                        <span class="mt-0.5 block text-[11px] opacity-70"
                            >Cuenta por cobrar</span
                        >
                    </button>
                </div>
            </div>

            <template v-if="isCredit">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-200"
                            >Días Crédito</label
                        >
                        <input
                            v-model.number="form.sls_credit_days"
                            type="number"
                            min="0"
                            max="365"
                            @input="updateDueDate"
                            class="flex h-10 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-200"
                            >Vencimiento</label
                        >
                        <input
                            :value="form.sls_due_date"
                            type="date"
                            disabled
                            class="flex h-10 w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-500"
                        />
                    </div>
                </div>
                <p
                    v-if="form.errors.sls_due_date"
                    class="text-xs text-red-400"
                >
                    {{ form.errors.sls_due_date }}
                </p>
                <p class="text-sm text-zinc-400">
                    Al convertir, la cotización generará la factura de venta,
                    descontará inventario y creará la cuenta por cobrar con el
                    vencimiento indicado.
                </p>
            </template>

            <div
                v-else
                class="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3 text-sm text-amber-300"
            >
                Serás redirigido a una nueva factura de contado para registrar
                el pago. La venta solo se guardará cuando el pago sea
                procesado.
            </div>
        </div>

        <template #actions>
            <Button
                type="button"
                variant="outline"
                class="border-zinc-700 text-zinc-300 hover:bg-zinc-800"
                @click="close"
            >
                Cancelar
            </Button>
            <Button
                v-if="isCredit"
                type="button"
                class="bg-indigo-600 text-white hover:bg-indigo-500"
                :disabled="form.processing"
                @click="submit"
            >
                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                <span v-else>Convertir</span>
            </Button>
            <Button
                v-else
                type="button"
                class="bg-indigo-600 text-white hover:bg-indigo-500"
                @click="goContado"
            >
                Ir a Registrar Pago
            </Button>
        </template>
    </PayModal>
</template>
