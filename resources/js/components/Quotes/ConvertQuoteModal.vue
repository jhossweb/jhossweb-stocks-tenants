<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Loader2, FileText } from 'lucide-vue-next';
import { computed } from 'vue';
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

const serverError = computed(
    () => (form.errors as Record<string, string>).error,
);

const form = useForm({
    sls_number: '',
});

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

            <p class="text-sm text-zinc-400">
                Al convertir, la cotización generará la factura de venta,
                descontará inventario y creará la cuenta por cobrar.
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
                type="button"
                class="bg-indigo-600 text-white hover:bg-indigo-500"
                :disabled="form.processing"
                @click="submit"
            >
                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                <span v-else>Convertir</span>
            </Button>
        </template>
    </PayModal>
</template>
