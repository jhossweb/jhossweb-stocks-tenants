<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import PaymentForm from '@/components/payments/PaymentForm.vue';
import PayModal from '@/components/payments/PayModal.vue';
import { useDocumentPayment } from '@/composables/Finance/useDocumentPayment';
import type { PaymentPending } from '@/types/Finance/DocumentPayment';

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
}

const props = defineProps<{
    open: boolean;
    pending: PaymentPending | null;
    bankAccounts: BankAccount[];
    latestRate: any;
    rates?: any[];
    methods: string[];
    currencies: string[];
    entityName?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    close: [];
    paid: [];
}>();

const {
    payForm,
    estimatedDiferencial,
    openPayment,
    closePayment,
    submitPayment,
} = useDocumentPayment();

const isSale = computed(() => props.pending?.entity === 'sale');

const title = computed(() =>
    isSale.value ? 'Registrar Cobro' : 'Registrar Pago',
);

const entityLabel = computed(() => (isSale.value ? 'Cliente:' : 'Proveedor:'));

watch(
    () => props.open && props.pending,
    (active) => {
        if (active && props.pending) {
            openPayment(props.pending, props.methods, props.latestRate);
        } else if (!props.open) {
            closePayment();
        }
    },
);

const close = () => {
    closePayment();
    emit('update:open', false);
    emit('close');
};

const submit = () => {
    submitPayment({
        onSuccess: () => {
            emit('update:open', false);
            emit('paid');
        },
    });
};
</script>

<template>
    <PayModal :open="open" :title="title" @close="close">
        <template #summary>
            <div v-if="pending" class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Documento:</span>
                    <span class="text-right font-medium text-zinc-100">{{
                        pending.document_number
                    }}</span>
                </div>
                <div v-if="entityName" class="flex justify-between text-sm">
                    <span class="text-zinc-400">{{ entityLabel }}</span>
                    <span class="text-right font-medium text-zinc-100">{{
                        entityName
                    }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Monto total:</span>
                    <span class="font-medium text-zinc-100">
                        {{
                            Number(pending.amount).toLocaleString('en-US', {
                                minimumFractionDigits: 4,
                                maximumFractionDigits: 4,
                            })
                        }}
                        {{ pending.currency }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-zinc-800 pt-1 text-sm"
                >
                    <span class="text-zinc-400">Saldo pendiente:</span>
                    <span class="font-semibold text-amber-400">
                        {{
                            Number(pending.amount).toLocaleString('en-US', {
                                minimumFractionDigits: 4,
                                maximumFractionDigits: 4,
                            })
                        }}
                        {{ pending.currency }}
                    </span>
                </div>
                <div
                    v-if="
                        estimatedDiferencial !== 0 &&
                        payForm.payment_currency !== pending.currency
                    "
                    class="flex justify-between border-t border-zinc-800 pt-1 text-sm"
                >
                    <span class="text-zinc-400">Diferencial estimado:</span>
                    <span
                        class="font-semibold"
                        :class="
                            estimatedDiferencial > 0
                                ? 'text-green-400'
                                : 'text-orange-400'
                        "
                    >
                        {{ estimatedDiferencial > 0 ? '+' : '' }}${{
                            Math.abs(estimatedDiferencial).toFixed(4)
                        }}
                        USD
                        <span class="text-[10px] font-normal opacity-60">
                            ({{
                                estimatedDiferencial > 0
                                    ? 'Ganancia'
                                    : 'Pérdida'
                            }})
                        </span>
                    </span>
                </div>
            </div>
        </template>

        <PaymentForm
            :bank-accounts="bankAccounts"
            :latest-rate="latestRate"
            :rates="rates"
            :currency="pending?.currency ?? 'USD'"
            :remaining-balance="Number(pending?.amount ?? 0)"
            :form="payForm"
            :errors="payForm.errors"
            :processing="payForm.processing"
            :methods="methods"
            :currencies="currencies"
            show-rate
            show-date
            show-method
        />

        <template #actions>
            <button
                type="button"
                :disabled="payForm.processing"
                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-indigo-500 disabled:opacity-50"
                @click="submit"
            >
                <Loader2
                    v-if="payForm.processing"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                {{ payForm.processing ? 'Procesando...' : title }}
            </button>
        </template>
    </PayModal>
</template>
