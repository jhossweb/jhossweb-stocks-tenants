import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { today } from '@/composables/useDate';
import { usePayment } from '@/composables/usePayment';
import type { Expense, LatestRate } from '@/types/Expense';

export function useExpenses() {
    const { resolveRateForStorage } = usePayment();

    const filterCategory = ref('');
    const filterStatus = ref('');
    const showPayModal = ref(false);
    const payingExpense = ref<Expense | null>(null);
    const expandedPayments = ref<Set<number>>(new Set());
    const showDeleteConfirm = ref(false);
    const deletingExpense = ref<Expense | null>(null);
    const latestRateRef = ref<LatestRate | null>(null);

    const payForm = useForm({
        bank_account_id: '',
        amount: '',
        reference: '',
        description: '',
        payment_currency: '',
        payment_rate: '',
        payment_date: '',
        payment_method: '',
    });

    const originalBalance = ref(0);
    const originalCoin = ref('USD');

    const isBS = (c: string) => c === 'BS' || c === 'VES';

    const openPayModal = (expense: Expense, methods: string[], latestRate: LatestRate | null = null) => {
        payingExpense.value = expense;
        latestRateRef.value = latestRate;
        originalBalance.value = Number(expense.exp_remaining_balance) || 0;
        originalCoin.value = expense.exp_coin || 'USD';
        payForm.reference = '';
        payForm.description = '';
        payForm.payment_currency = originalCoin.value;
        payForm.bank_account_id = '';
        payForm.amount = String(originalBalance.value);
        payForm.payment_date = today();
        payForm.payment_method = methods.length > 0 ? methods[0] : '';
        payForm.payment_rate = String(resolveRateForStorage(originalCoin.value, latestRate));
        showPayModal.value = true;
    };

    const closePayModal = () => {
        showPayModal.value = false;
        payingExpense.value = null;
        originalBalance.value = 0;
        originalCoin.value = 'USD';
        latestRateRef.value = null;
        payForm.clearErrors();
        payForm.reset();
    };

    const estimatedDiferencial = computed(() => {
        if (!payingExpense.value || !payForm.amount || Number(payForm.amount) <= 0) {
            return 0;
        }

        const payAmount = Number(payForm.amount) || 0;
        const payCoin = payForm.payment_currency;
        const docCoin = payingExpense.value.exp_coin;

        if (!payCoin || payCoin === docCoin) {
            return 0;
        }

        const payRate = Number(payForm.payment_rate) || 1;
        const docRate = Number(payingExpense.value.exp_valorcam) || 1;

        if (payRate <= 0 || docRate <= 0) {
            return 0;
        }

        let paidInUsd: number;
        let docInUsd: number;

        if (payCoin === 'USD') {
            paidInUsd = payAmount;
            docInUsd = payAmount;
        } else if ((payCoin === 'COP' && isBS(docCoin)) || (isBS(payCoin) && docCoin === 'COP')) {
            const amountInExpenseCoin = payCoin === 'COP'
                ? payAmount / payRate
                : payAmount * payRate;

            const latestExpenseRate = docCoin === 'COP'
                ? Number(latestRateRef.value?.rate_cop || 1)
                : Number(latestRateRef.value?.rate_usd || 1);

            paidInUsd = latestExpenseRate > 0 ? amountInExpenseCoin / latestExpenseRate : 0;
            docInUsd = docRate > 0 ? amountInExpenseCoin / docRate : 0;
        } else {
            paidInUsd = payRate > 0 ? payAmount / payRate : 0;
            docInUsd = docRate > 0 ? payAmount / docRate : 0;
        }

        return docInUsd - paidInUsd;
    });

    const submitPayment = (expenseId: number) => {
        payForm.post(`/expenses/${expenseId}/pay`, {
            preserveScroll: true,
            onSuccess: () => closePayModal(),
        });
    };

    const togglePayments = (expenseId: number) => {
        if (expandedPayments.value.has(expenseId)) {
            expandedPayments.value.delete(expenseId);
        } else {
            expandedPayments.value.add(expenseId);
        }
    };

    const confirmDelete = (expense: Expense) => {
        deletingExpense.value = expense;
        showDeleteConfirm.value = true;
    };

    const cancelDelete = () => {
        showDeleteConfirm.value = false;
        deletingExpense.value = null;
    };

    const cancelPaymentForm = useForm({
        bank_transaction_id: '',
    });

    const cancelPayment = (expenseId: number, bankTransactionId: number) => {
        cancelPaymentForm.bank_transaction_id = String(bankTransactionId);
        cancelPaymentForm.post(`/expenses/${expenseId}/cancel-payment`, {
            preserveScroll: true,
        });
    };

    return {
        filterCategory,
        filterStatus,
        showPayModal,
        payingExpense,
        expandedPayments,
        showDeleteConfirm,
        deletingExpense,
        payForm,
        originalBalance,
        originalCoin,
        estimatedDiferencial,
        openPayModal,
        closePayModal,
        submitPayment,
        togglePayments,
        confirmDelete,
        cancelDelete,
        cancelPayment,
        cancelPaymentForm,
    };
}
