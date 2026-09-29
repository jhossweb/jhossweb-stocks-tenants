import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import { generateUid } from '@/lib/utils';
import receiptRoutes from '@/routes/receipts';

interface RateRecord {
    rate_date: string;
    rate_usd: number | string;
    rate_cop: number | string;
    rate_eur: number | string;
    rate_cop_bs?: number | string;
    [key: string]: unknown;
}

interface BankAccount {
    id: number;
    bank_name: string;
    bank_account_number: string;
    bank_currency: string;
    bank_balance: number | string;
    formatted_balance?: string;
    [key: string]: unknown;
}

interface InvoicePayment {
    acr_id: number;
    invoice_number: string;
    balance: number;
    currency: string;
    amount: number;
}

interface BankMovement {
    uid?: string;
    bank_account_id: string;
    currency: string;
    amount: number;
    method: string;
    reference: string;
    description: string;
}

interface ReceiptFormProps {
    payment?: any;
    customers: any[];
    bankAccounts: BankAccount[];
    latestRate: RateRecord | null;
    rates: RateRecord[];
    currencies: string[];
    methods: string[];
}

export function useReceiptForm(props: ReceiptFormProps) {
    const { convertCurrency } = useCurrencyConversion();

    const isEditMode = computed(() => !!props.payment);

    const currentStep = ref(1);
    const stepErrors = ref<string[]>([]);

    const form = useForm({
        payment_date: props.payment?.bank_transaction?.created_at
            ? props.payment.bank_transaction.created_at.split('T')[0]
            : new Date().toISOString().split('T')[0],
        customer_id:
            props.payment?.accounts_receivable?.sale_order?.sls_customer_id?.toString() ||
            props.payment?.accounts_receivable?.sale_order?.customer?.id?.toString() ||
            '',
        payments: [] as InvoicePayment[],
        bank_movements: [createBlankMovement()] as BankMovement[],
        apply_vuelto: false,
        vueltos: [] as any[],
    });

    function createBlankMovement(): BankMovement {
        return {
            uid: generateUid(),
            bank_account_id: '',
            currency: 'USD',
            amount: 0,
            method: 'TRANSFERENCIA',
            reference: '',
            description: '',
        };
    }

    const payment_rate = ref(
        props.latestRate ? Number(props.latestRate.rate_usd) : 1,
    );

    const pendingReceivables = ref<any[]>([]);
    const isLoadingInvoices = ref(false);
    const selectedInvoiceCurrency = ref<string | null>(null);

    const canProceedStep1 = computed(() => !!form.customer_id);
    const canProceedStep2 = computed(
        () => totalToPay.value > 0 && !!selectedInvoiceCurrency.value,
    );
    const canSubmit = computed(
        () => totalToPay.value > 0 && totalBankMovementsInDoc.value > 0,
    );

    const totalToPay = computed(() =>
        form.payments.reduce((acc, p) => acc + Number(p.amount), 0),
    );

    const totalInPaymentCurrency = computed(() => totalToPay.value);

    const originalTotal = computed(() =>
        form.payments.reduce((acc, p) => acc + Number(p.balance), 0),
    );

    const originalCurrency = computed(() => {
        const currencies = [...new Set(form.payments.map((p) => p.currency))];

        return currencies.length === 1 ? currencies[0] : undefined;
    });

    const firstMovementCurrency = computed(
        () => form.bank_movements[0]?.currency || 'USD',
    );

    const docCurrency = computed(() => selectedInvoiceCurrency.value || 'USD');

    const movementDocEquivalent = (movement: BankMovement): number => {
        if (!movement.amount) {
            return 0;
        }

        if (movement.currency === docCurrency.value) {
            return Number(movement.amount);
        }

        const currentRate =
            getBestRateForDate(form.payment_date) || props.latestRate || {};

        return convertCurrency(
            Number(movement.amount),
            movement.currency,
            docCurrency.value,
            currentRate,
        );
    };

    const totalBankMovements = computed(() =>
        form.bank_movements.reduce((acc, m) => acc + Number(m.amount || 0), 0),
    );

    const totalBankMovementsInDoc = computed(() =>
        form.bank_movements.reduce(
            (acc, m) => acc + movementDocEquivalent(m),
            0,
        ),
    );

    const vuelto = computed(() => {
        const diff = totalBankMovementsInDoc.value - totalToPay.value;

        return diff > 0 ? diff : 0;
    });

    const remainingInDocForMovement = (index: number): number => {
        const others = form.bank_movements
            .filter((_, idx) => idx !== index)
            .reduce((acc, m) => acc + movementDocEquivalent(m), 0);

        return Math.max(0, totalToPay.value - others);
    };

    const maxAllowedForMovement = (index: number): number => {
        const movement = form.bank_movements[index];

        if (!movement) {
            return 0;
        }

        if (form.apply_vuelto) {
            return Number.MAX_SAFE_INTEGER;
        }

        const remaining = remainingInDocForMovement(index);

        if (remaining <= 0) {
            return 0;
        }

        if (movement.currency === docCurrency.value) {
            return remaining;
        }

        const currentRate =
            getBestRateForDate(form.payment_date) || props.latestRate || {};

        return convertCurrency(
            remaining,
            docCurrency.value,
            movement.currency,
            currentRate,
        );
    };

    const remainingForMovement = (index: number): number =>
        maxAllowedForMovement(index);

    const setMovementAmount = (index: number, value: number) => {
        const movement = form.bank_movements[index];

        if (!movement) {
            return;
        }

        const cap = maxAllowedForMovement(index);

        movement.amount =
            cap >= Number.MAX_SAFE_INTEGER
                ? Math.max(0, value)
                : Math.min(Math.max(0, value), cap);
    };

    const filteredBankAccounts = (currency: string) =>
        props.bankAccounts.filter((ba) => ba.bank_currency === currency);

    const getBestRateForDate = (date: string): RateRecord | null => {
        if (!props.rates || props.rates.length === 0) {
            return props.latestRate || null;
        }

        const normalized = date.substring(0, 10);
        const exact = props.rates.find(
            (r) => r.rate_date.substring(0, 10) === normalized,
        );

        if (exact) {
            return exact;
        }

        const past = props.rates
            .filter((r) => r.rate_date.substring(0, 10) < normalized)
            .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

        return past.length > 0 ? past[0] : props.rates[0];
    };

    const resolveRateValue = (coin: string, rate: RateRecord): number => {
        const c = coin?.toUpperCase() || '';

        if (c === 'USD' || c === 'BS' || c === 'VES') {
            return Number(rate.rate_usd) || 1;
        }

        if (c === 'COP') {
            return Number(rate.rate_cop) || 1;
        }

        if (c === 'EUR') {
            return Number(rate.rate_eur) || 1;
        }

        return Number(rate.rate_usd) || 1;
    };

    const updateExchangeRate = () => {
        const selected = getBestRateForDate(form.payment_date);

        if (!selected) {
            return;
        }

        const payCurrency =
            form.bank_movements.find((m) => m.currency)?.currency ||
            docCurrency.value;

        payment_rate.value = resolveRateValue(payCurrency, selected);
    };

    const fetchPendingReceivables = async (customerId: string | number) => {
        if (!customerId) {
            pendingReceivables.value = [];
            form.payments = [];
            selectedInvoiceCurrency.value = null;

            return;
        }

        isLoadingInvoices.value = true;
        selectedInvoiceCurrency.value = null;

        try {
            const response = await fetch(
                `/api/customers/${customerId}/pending-receivables`,
            );

            if (!response.ok) {
                throw new Error('Error al obtener facturas');
            }

            const data = await response.json();
            pendingReceivables.value = data;

            form.payments = pendingReceivables.value.map((inv: any) => ({
                acr_id: inv.id,
                invoice_number: inv.acr_sls_number,
                balance: Number(inv.acr_balance),
                currency: inv.sale_order?.sls_coin || 'USD',
                amount: 0,
            }));
        } catch (error) {
            console.error('Error fetching receivables:', error);
        } finally {
            isLoadingInvoices.value = false;
        }
    };

    const selectCustomer = (customer: any) => {
        form.customer_id = customer.id;
        fetchPendingReceivables(customer.id);
        currentStep.value = 2;
    };

    const getCustomerName = (id: string | number) => {
        const c = props.customers.find((c) => c.id == id);

        return c ? c.clt_name : 'Seleccione cliente';
    };

    const setInvoiceAmount = (invoice: InvoicePayment, value: number) => {
        const amount = Math.max(0, Math.min(Number(value), invoice.balance));

        if (amount > 0) {
            if (
                selectedInvoiceCurrency.value &&
                selectedInvoiceCurrency.value !== invoice.currency
            ) {
                return false;
            }

            selectedInvoiceCurrency.value = invoice.currency;
        }

        invoice.amount = amount;

        if (totalToPay.value <= 0) {
            selectedInvoiceCurrency.value = null;
        }

        return true;
    };

    const calculateConvertedBalance = (invoicePayment: InvoicePayment) =>
        Number(invoicePayment.balance);

    const addBankMovement = (currency: unknown = docCurrency.value) => {
        const safeCurrency =
            typeof currency === 'string' && currency
                ? currency
                : docCurrency.value;

        form.bank_movements.push({
            ...createBlankMovement(),
            currency: safeCurrency,
        });
    };

    const removeBankMovement = (index: number) => {
        if (form.bank_movements.length > 1) {
            form.bank_movements.splice(index, 1);
        }
    };

    const changeMovementCurrency = (
        movementIndex: number,
        newCurrency: unknown,
    ) => {
        if (typeof newCurrency !== 'string' || !newCurrency) {
            return;
        }

        const movement = form.bank_movements[movementIndex];

        if (!movement || movement.currency === newCurrency) {
            return;
        }

        const oldCurrency = movement.currency;

        if (Number(movement.amount) > 0) {
            const currentRate =
                getBestRateForDate(form.payment_date) || props.latestRate || {};

            const inDoc = convertCurrency(
                Number(movement.amount),
                oldCurrency,
                docCurrency.value,
                currentRate,
            );

            const newInPay = convertCurrency(
                inDoc,
                docCurrency.value,
                newCurrency,
                currentRate,
            );

            movement.amount = Number(newInPay.toFixed(4));

            const cap = maxAllowedForMovement(movementIndex);

            if (cap < Number.MAX_SAFE_INTEGER && movement.amount > cap) {
                movement.amount = cap;
            }
        }

        movement.currency = newCurrency;
        updateExchangeRate();
    };

    const syncBankMovementsToInvoices = () => {
        const activeInvoices = form.payments.filter(
            (p) => Number(p.amount) > 0,
        );

        if (activeInvoices.length === 0) {
            form.bank_movements = [createBlankMovement()];

            return;
        }

        const docTotal = activeInvoices.reduce(
            (acc, p) => acc + Number(p.amount),
            0,
        );

        const existing = form.bank_movements[0] ?? createBlankMovement();

        form.bank_movements = [
            {
                ...existing,
                currency: docCurrency.value,
                amount: Number(docTotal.toFixed(4)),
            },
        ];
    };

    const nextStep = () => {
        stepErrors.value = [];

        if (currentStep.value === 1 && !form.customer_id) {
            stepErrors.value = ['Seleccione un cliente'];

            return;
        }

        if (currentStep.value === 2 && totalToPay.value <= 0) {
            stepErrors.value = ['Ingrese al menos un monto a cobrar'];

            return;
        }

        if (currentStep.value === 2) {
            syncBankMovementsToInvoices();
        }

        if (currentStep.value < 3) {
            currentStep.value++;
        }
    };

    const prevStep = () => {
        stepErrors.value = [];

        if (currentStep.value > 1) {
            currentStep.value--;
        }
    };

    const goToStep = (step: number) => {
        if (step < currentStep.value) {
            currentStep.value = step;
            stepErrors.value = [];
        }
    };

    const submit = () => {
        stepErrors.value = [];

        if (totalToPay.value <= 0) {
            stepErrors.value = ['Ingrese al menos un monto a cobrar'];

            return;
        }

        if (totalBankMovementsInDoc.value <= 0) {
            stepErrors.value = ['Registre al menos un movimiento bancario'];

            return;
        }

        if (isEditMode.value) {
            form.transform((data) => ({
                payment_date: data.payment_date,
                bank_account_id: data.bank_movements[0]?.bank_account_id || '',
                payment_reference: data.bank_movements[0]?.reference || '',
                payment_description: data.bank_movements[0]?.description || '',
                payment_currency: data.bank_movements[0]?.currency || 'USD',
                amount: data.payments[0]?.amount || 0,
            })).put(`/receipts/${props.payment.id}`, {
                onError: (errors) => {
                    stepErrors.value = Object.values(errors).flat().map(String);
                },
            });

            return;
        }

        form.transform((data) => ({
            ...data,
            payments: data.payments.filter((p: any) => Number(p.amount) > 0),
        })).post(receiptRoutes.store.url(), {
            onError: (errors) => {
                stepErrors.value = Object.values(errors).flat().map(String);
            },
        });
    };

    watch(
        () => form.payment_date,
        () => updateExchangeRate(),
    );

    return {
        form,
        isEditMode,
        currentStep,
        stepErrors,
        payment_rate,
        pendingReceivables,
        isLoadingInvoices,
        canProceedStep1,
        canProceedStep2,
        canSubmit,
        totalToPay,
        totalInPaymentCurrency,
        originalTotal,
        originalCurrency,
        totalBankMovements,
        totalBankMovementsInDoc,
        vuelto,
        firstMovementCurrency,
        docCurrency,
        movementDocEquivalent,
        remainingForMovement,
        maxAllowedForMovement,
        setMovementAmount,
        selectedInvoiceCurrency,
        setInvoiceAmount,
        filteredBankAccounts,
        getBestRateForDate,
        resolveRateValue,
        updateExchangeRate,
        fetchPendingReceivables,
        selectCustomer,
        getCustomerName,
        calculateConvertedBalance,
        addBankMovement,
        syncBankMovementsToInvoices,
        removeBankMovement,
        changeMovementCurrency,
        nextStep,
        prevStep,
        goToStep,
        submit,
    };
}
