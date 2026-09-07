import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import { today } from '@/composables/useDate';
import { usePayment } from '@/composables/usePayment';
import type { PaymentPending } from '@/types/Finance/DocumentPayment';

export function useDocumentPayment() {
    const { resolveRateForStorage } = usePayment();
    const { convertCurrency } = useCurrencyConversion();

    const pending = ref<PaymentPending | null>(null);
    const docAmount = ref(0);
    const docCurrency = ref('');
    const latestRateRef = ref<{
        rate_usd: number;
        rate_cop: number;
        rate_eur: number;
    } | null>(null);

    const roundTo4 = (value: number): number =>
        Math.round(value * 10000) / 10000;

    const payForm = useForm({
        payment_date: '',
        payment_currency: '',
        payment_rate: '',
        bank_account_id: '',
        amount: '',
        payment_method: '',
        reference: '',
        description: '',
        customer_id: '',
        supplier_id: '',
        payments: [] as { acr_id?: number; acp_id?: number; amount: number }[],
    });

    const openPayment = (
        data: PaymentPending,
        methods: string[],
        latestRate: {
            rate_usd: number;
            rate_cop: number;
            rate_eur: number;
        } | null,
    ) => {
        pending.value = data;
        docAmount.value = data.amount;
        docCurrency.value = data.currency;
        latestRateRef.value = latestRate;
        payForm.payment_date = data.date || today();
        payForm.payment_currency = data.currency;
        payForm.payment_rate = String(
            resolveRateForStorage(data.currency, latestRate),
        );
        payForm.bank_account_id = '';
        payForm.amount = String(data.amount);
        payForm.payment_method = methods.length > 0 ? methods[0] : '';
        payForm.reference = '';
        payForm.description = '';
        payForm.customer_id =
            data.customer_id != null ? String(data.customer_id) : '';
        payForm.supplier_id =
            data.supplier_id != null ? String(data.supplier_id) : '';
        payForm.payments =
            data.acr_id != null
                ? [{ acr_id: data.acr_id, amount: data.amount }]
                : [{ acp_id: data.acp_id, amount: data.amount }];
        payForm.clearErrors();
    };

    const closePayment = () => {
        pending.value = null;
        docAmount.value = 0;
        docCurrency.value = '';
        latestRateRef.value = null;
        payForm.clearErrors();
        payForm.reset();
    };

    watch(
        () => payForm.payment_currency,
        (newCurrency) => {
            if (
                !pending.value ||
                !newCurrency ||
                newCurrency === docCurrency.value ||
                !latestRateRef.value
            ) {
                return;
            }

            payForm.amount = String(
                roundTo4(
                    convertCurrency(
                        docAmount.value,
                        docCurrency.value,
                        newCurrency,
                        latestRateRef.value,
                    ),
                ),
            );
        },
    );

    const estimatedDiferencial = computed(() => {
        if (!pending.value || !payForm.amount || Number(payForm.amount) <= 0) {
            return 0;
        }

        const payCoin = payForm.payment_currency;
        const docCoin = pending.value.currency;

        if (!payCoin || payCoin === docCoin) {
            return 0;
        }

        const payRate = Number(payForm.payment_rate) || 1;
        const docRate = Number(pending.value.valorcam) || 1;

        if (payRate <= 0 || docRate <= 0) {
            return 0;
        }

        const paidInUsd =
            payCoin === 'USD'
                ? Number(payForm.amount)
                : Number(payForm.amount) / payRate;
        const docInUsd =
            docCoin === 'USD'
                ? Number(payForm.amount)
                : Number(payForm.amount) / docRate;

        return docInUsd - paidInUsd;
    });

    const submitPayment = (options?: { onSuccess?: () => void }) => {
        const entity = pending.value?.entity;

        if (!entity) {
            return;
        }

        const target = entity === 'sale' ? '/receipts' : '/payments';
        const next = entity === 'sale' ? '/sales/create' : '/purchases/create';

        payForm
            .transform((data: any) => ({
                payment_date: data.payment_date,
                customer_id: data.customer_id,
                supplier_id: data.supplier_id,
                bank_account_id: data.bank_account_id,
                payment_currency: data.payment_currency,
                payment_rate: data.payment_rate,
                payment_method: data.payment_method,
                payment_reference: data.reference,
                payment_description: data.description,
                payments: (data.payments ?? []).map(
                    (p: { acr_id?: number; acp_id?: number }) => ({
                        ...p,
                        amount: roundTo4(Number(data.amount) || 0),
                    }),
                ),
            }))
            .post(target, {
                preserveScroll: true,
                onSuccess: () => {
                    closePayment();
                    options?.onSuccess?.();
                    router.visit(next);
                },
            });
    };

    return {
        pending,
        payForm,
        estimatedDiferencial,
        openPayment,
        closePayment,
        submitPayment,
    };
}
