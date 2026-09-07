<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    ArrowLeft as ArrowPrev,
    Save,
    Loader2,
    Landmark,
    CreditCard,
    Building2,
    CheckCircle2,
    Calculator,
    Plus,
    Trash2,
    CircleDot,
    Check,
    Receipt,
    Info,
} from 'lucide-vue-next';
import { ref } from 'vue';
import BankAccountCreateModal from '@/components/payments/BankAccountCreateModal.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useReceiptForm } from '@/composables/Finance/useReceiptForm';

const props = defineProps<{
    payment?: any;
    customers: any[];
    bankAccounts: any[];
    latestRate: any;
    rates: any[];
    currencies: string[];
    methods: string[];
}>();

const {
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
    selectCustomer,
    getCustomerName,
    addBankMovement,
    removeBankMovement,
    changeMovementCurrency,
    nextStep,
    prevStep,
    goToStep,
    submit,
} = useReceiptForm(props);

const bankModalRef = ref<InstanceType<typeof BankAccountCreateModal> | null>(
    null,
);
const activeMovementIndex = ref(0);

const openBankModalForMovement = (index: number) => {
    activeMovementIndex.value = index;
    bankModalRef.value?.open();
};

const onBankCreated = (bank: { id: number }) => {
    form.bank_movements[activeMovementIndex.value].bank_account_id = String(
        bank.id,
    );
};

const getMovementFilteredAccounts = (currency: string) =>
    filteredBankAccounts(currency);

const isInvoiceDisabled = (currency: string) =>
    selectedInvoiceCurrency.value !== null &&
    selectedInvoiceCurrency.value !== currency;

const steps = [
    { num: 1, label: 'Datos', icon: Landmark },
    { num: 2, label: 'Facturas', icon: CreditCard },
    { num: 3, label: 'Fondos', icon: Building2 },
];
</script>

<template>
    <Head title="Comprobante de Cobro" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <!-- Header -->
        <div
            class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm"
        >
            <div class="flex items-center gap-4">
                <Link
                    href="/accounts-receivable"
                    class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">
                        {{
                            isEditMode ? 'Editar Cobro' : 'Comprobante de Cobro'
                        }}
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        {{
                            isEditMode
                                ? 'Actualización de cobro registrado'
                                : 'Registro de ingresos y abonos de clientes'
                        }}
                    </p>
                </div>
            </div>

            <!-- Step Indicator (create mode only) -->
            <div v-if="!isEditMode" class="hidden items-center gap-1 md:flex">
                <template v-for="(s, idx) in steps" :key="s.num">
                    <button
                        @click="goToStep(s.num)"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                        :class="
                            currentStep === s.num
                                ? 'bg-primary text-primary-foreground'
                                : currentStep > s.num
                                  ? 'cursor-pointer text-primary hover:bg-primary/10'
                                  : 'cursor-default text-muted-foreground'
                        "
                    >
                        <Check v-if="currentStep > s.num" class="size-3" />
                        <component v-else :is="s.icon" class="size-3" />
                        {{ s.label }}
                    </button>
                    <div
                        v-if="idx < steps.length - 1"
                        class="h-px w-6"
                        :class="
                            currentStep > s.num ? 'bg-primary' : 'bg-border'
                        "
                    />
                </template>
            </div>

            <Button
                v-if="isEditMode"
                @click="submit"
                :disabled="form.processing"
                size="lg"
                class="h-12 rounded-xl bg-primary px-8 font-bold text-primary-foreground shadow-sm transition-all hover:bg-primary/90"
            >
                <Loader2
                    v-if="form.processing"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                <Save v-else class="mr-2 h-4 w-4" />
                Actualizar Cobro
            </Button>
        </div>

        <!-- Errors -->
        <div
            v-if="stepErrors.length > 0 || Object.keys(form.errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Por favor, corrige los siguientes errores:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li v-for="(error, idx) in stepErrors" :key="'step-' + idx">
                    {{ error }}
                </li>
                <li v-for="(error, key) in form.errors" :key="'form-' + key">
                    {{ error }}
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Main Content -->
            <div
                class="flex flex-col gap-6"
                :class="currentStep === 3 ? 'lg:col-span-8' : 'lg:col-span-12'"
            >
                <!-- STEP 1: Datos del Cliente -->
                <template v-if="currentStep === 1 || isEditMode">
                    <div class="rounded-xl border bg-card shadow-sm">
                        <div class="flex items-center gap-2 border-b px-6 py-4">
                            <Landmark class="size-4 text-primary" />
                            <span
                                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                >Datos del Cliente</span
                            >
                        </div>
                        <div class="flex flex-col gap-5 p-6">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Cliente</label
                                >
                                <div
                                    v-if="isEditMode"
                                    class="flex h-10 items-center rounded-lg border bg-muted/30 px-3 text-sm"
                                >
                                    {{ getCustomerName(form.customer_id) }}
                                </div>
                                <SelectorModal
                                    v-else
                                    title="Seleccionar Cliente"
                                    description="Busque y seleccione el cliente a cobrar"
                                    :items="customers"
                                    :searchFields="['clt_name']"
                                    :displayFields="[
                                        { label: 'Nombre', key: 'clt_name' },
                                    ]"
                                    :triggerLabel="
                                        getCustomerName(form.customer_id)
                                    "
                                    :selectedValue="form.customer_id"
                                    @select="selectCustomer"
                                />
                                <span
                                    v-if="form.errors.customer_id"
                                    class="mt-1 text-xs text-destructive"
                                    >{{ form.errors.customer_id }}</span
                                >
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                        >Fecha de Cobro</label
                                    >
                                    <Input
                                        type="date"
                                        v-model="form.payment_date"
                                        :class="{
                                            'border-destructive':
                                                form.errors.payment_date,
                                        }"
                                    />
                                    <span
                                        v-if="form.errors.payment_date"
                                        class="mt-1 text-xs text-destructive"
                                        >{{ form.errors.payment_date }}</span
                                    >
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                        >Tasa Aplicada ({{
                                            firstMovementCurrency
                                        }})</label
                                    >
                                    <div
                                        class="flex h-10 items-center rounded-lg border bg-muted/30 px-3 font-mono text-sm font-bold"
                                    >
                                        {{ Number(payment_rate).toFixed(4) }}
                                    </div>
                                    <p
                                        class="mt-1 text-[11px] text-muted-foreground"
                                    >
                                        Factor usado para convertir el pago de
                                        {{ docCurrency }}
                                        a
                                        {{ firstMovementCurrency }}
                                        según la tasa vigente a la fecha de
                                        cobro.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Next button for step 1 -->
                    <div
                        v-if="!isEditMode && currentStep === 1"
                        class="flex justify-end"
                    >
                        <Button
                            @click="nextStep"
                            :disabled="!canProceedStep1"
                            size="lg"
                            class="h-12 rounded-xl px-8 font-bold"
                        >
                            Siguiente
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </template>

                <!-- STEP 2: Facturas Pendientes -->
                <template
                    v-if="(currentStep === 2 || isEditMode) && form.customer_id"
                >
                    <div
                        class="flex min-h-[400px] flex-col overflow-hidden rounded-xl border bg-card shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b px-6 py-4"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="rounded-lg bg-primary/10 p-2 text-primary"
                                >
                                    <CreditCard class="size-4" />
                                </div>
                                <span
                                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                    >Facturas Pendientes</span
                                >
                            </div>
                            <Badge
                                v-if="pendingReceivables.length"
                                variant="outline"
                                class="bg-muted/30 text-[10px] text-muted-foreground"
                            >
                                {{ pendingReceivables.length }} facturas
                                encontradas
                            </Badge>
                        </div>

                        <div class="relative flex-1 overflow-x-auto">
                            <div
                                v-if="isLoadingInvoices"
                                class="absolute inset-0 z-10 flex items-center justify-center bg-background/20 backdrop-blur-sm"
                            >
                                <Loader2
                                    class="size-8 animate-spin text-primary"
                                />
                            </div>

                            <div
                                v-if="
                                    pendingReceivables.length === 0 &&
                                    !isLoadingInvoices
                                "
                                class="absolute inset-0 flex flex-col items-center justify-center gap-4 text-muted-foreground"
                            >
                                <CheckCircle2
                                    class="size-12 text-green-500/20"
                                />
                                <p class="text-sm font-medium">
                                    El cliente no tiene deudas pendientes
                                </p>
                            </div>

                            <template v-else>
                                <div
                                    v-if="selectedInvoiceCurrency"
                                    class="flex items-center gap-2 border-b bg-primary/5 px-6 py-3 text-xs font-bold text-primary"
                                >
                                    <CircleDot class="size-3.5" />
                                    Cobrando en
                                    <span
                                        class="rounded bg-primary/10 px-1.5 py-0.5 font-black"
                                        >{{ selectedInvoiceCurrency }}</span
                                    >
                                    <span class="font-normal text-primary/70">
                                        Para cobrar otra moneda, borre los
                                        montos ingresados.
                                    </span>
                                </div>

                                <table class="w-full text-left text-sm">
                                    <thead
                                        class="border-b bg-muted/50 text-[10px] font-bold text-muted-foreground uppercase"
                                    >
                                        <tr>
                                            <th class="px-6 py-5">Factura</th>
                                            <th class="px-6 py-5">
                                                Saldo Pendiente
                                            </th>
                                            <th
                                                class="w-48 px-6 py-5 text-center"
                                            >
                                                Monto a Cobrar
                                            </th>
                                            <th
                                                class="w-12 px-6 py-5 text-right"
                                            ></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr
                                            v-for="invoice in form.payments"
                                            :key="invoice.acr_id"
                                            class="transition-colors"
                                            :class="[
                                                selectedInvoiceCurrency &&
                                                selectedInvoiceCurrency !==
                                                    invoice.currency
                                                    ? 'pointer-events-none opacity-40'
                                                    : 'group hover:bg-muted/30',
                                            ]"
                                        >
                                            <td class="px-6 py-5">
                                                <div class="flex flex-col">
                                                    <span class="font-bold"
                                                        >#{{
                                                            invoice.invoice_number
                                                        }}</span
                                                    >
                                                    <span
                                                        class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase"
                                                        >Factura de Venta</span
                                                    >
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div
                                                    class="flex items-center gap-1 font-mono font-bold text-muted-foreground"
                                                >
                                                    <span
                                                        class="text-[10px] text-muted-foreground/60"
                                                        >{{
                                                            invoice.currency
                                                        }}</span
                                                    >
                                                    {{
                                                        Number(
                                                            invoice.balance,
                                                        ).toLocaleString(
                                                            'en-US',
                                                            {
                                                                minimumFractionDigits: 4,
                                                            },
                                                        )
                                                    }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="relative">
                                                    <Input
                                                        type="number"
                                                        step="0.0001"
                                                        :model-value="
                                                            invoice.amount
                                                        "
                                                        :disabled="
                                                            isInvoiceDisabled(
                                                                invoice.currency,
                                                            )
                                                        "
                                                        @update:model-value="
                                                            setInvoiceAmount(
                                                                invoice,
                                                                Number($event),
                                                            )
                                                        "
                                                        class="text-right font-bold tabular-nums transition-all group-hover:border-primary/50"
                                                    />
                                                    <button
                                                        :disabled="
                                                            isInvoiceDisabled(
                                                                invoice.currency,
                                                            )
                                                        "
                                                        @click="
                                                            setInvoiceAmount(
                                                                invoice,
                                                                invoice.balance,
                                                            )
                                                        "
                                                        class="absolute top-1/2 left-2 -translate-y-1/2 text-[10px] font-bold text-primary opacity-0 transition-opacity group-hover:opacity-100 hover:text-primary/80 disabled:opacity-0"
                                                    >
                                                        MÁX
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div
                                                    v-if="invoice.amount > 0"
                                                    class="flex justify-center text-green-500"
                                                >
                                                    <CheckCircle2
                                                        class="size-4"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                        </div>
                    </div>

                    <!-- Navigation buttons for step 2 -->
                    <div
                        v-if="!isEditMode && currentStep === 2"
                        class="flex justify-between"
                    >
                        <Button
                            @click="prevStep"
                            variant="outline"
                            size="lg"
                            class="h-12 rounded-xl px-8 font-bold"
                        >
                            <ArrowPrev class="mr-2 h-4 w-4" />
                            Anterior
                        </Button>
                        <Button
                            @click="nextStep"
                            :disabled="!canProceedStep2"
                            size="lg"
                            class="h-12 rounded-xl px-8 font-bold"
                        >
                            Siguiente
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </template>

                <!-- STEP 3: Origen de Fondos -->
                <template
                    v-if="(currentStep === 3 || isEditMode) && form.customer_id"
                >
                    <div class="rounded-xl border bg-card shadow-sm">
                        <div
                            class="flex items-center justify-between border-b px-6 py-4"
                        >
                            <div class="flex items-center gap-2">
                                <Building2 class="size-4 text-primary" />
                                <span
                                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                    >Origen de Fondos</span
                                >
                            </div>
                            <Button
                                @click="addBankMovement()"
                                variant="outline"
                                size="sm"
                                class="h-8 text-xs font-bold"
                            >
                                <Plus class="mr-1 h-3 w-3" />
                                Agregar Movimiento
                            </Button>
                        </div>

                        <div class="flex flex-col gap-4 p-6">
                            <div
                                v-for="(
                                    movement, mIndex
                                ) in form.bank_movements"
                                :key="movement.uid ?? mIndex"
                                class="space-y-4 rounded-xl border bg-muted/20 p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-bold text-muted-foreground"
                                    >
                                        Movimiento #{{ mIndex + 1 }}
                                    </span>
                                    <button
                                        v-if="form.bank_movements.length > 1"
                                        @click="removeBankMovement(mIndex)"
                                        class="p-1 text-destructive/60 transition-colors hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div class="flex flex-col gap-4">
                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                                >Moneda</label
                                            >
                                            <Select
                                                :model-value="movement.currency"
                                                @update:model-value="
                                                    changeMovementCurrency(
                                                        mIndex,
                                                        String($event ?? ''),
                                                    )
                                                "
                                            >
                                                <SelectTrigger>
                                                    <SelectValue
                                                        placeholder="Moneda"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="c in currencies"
                                                        :key="c"
                                                        :value="c"
                                                        >{{ c }}</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                                >Banco y Cuenta</label
                                            >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Select
                                                    v-model="
                                                        movement.bank_account_id
                                                    "
                                                    class="flex-1"
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue
                                                            placeholder="Seleccione cuenta bancaria"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            v-for="ba in getMovementFilteredAccounts(
                                                                movement.currency,
                                                            )"
                                                            :key="ba.id"
                                                            :value="
                                                                ba.id.toString()
                                                            "
                                                        >
                                                            <div
                                                                class="flex flex-col items-start py-0.5"
                                                            >
                                                                <span
                                                                    class="font-bold"
                                                                    >{{
                                                                        ba.bank_name
                                                                    }}</span
                                                                >
                                                                <span
                                                                    class="font-mono text-[10px] text-muted-foreground"
                                                                    >{{
                                                                        ba.bank_account_number
                                                                    }}
                                                                    ({{
                                                                        ba.bank_currency
                                                                    }})</span
                                                                >
                                                            </div>
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    @click="
                                                        openBankModalForMovement(
                                                            mIndex,
                                                        )
                                                    "
                                                    class="h-10 w-10 shrink-0"
                                                    title="Crear nueva cuenta bancaria"
                                                >
                                                    <Plus class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-4">
                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                            >
                                                Monto ({{ movement.currency }})
                                            </label>
                                            <div class="relative">
                                                <Input
                                                    type="number"
                                                    step="0.0001"
                                                    :model-value="
                                                        movement.amount
                                                    "
                                                    @update:model-value="
                                                        setMovementAmount(
                                                            mIndex,
                                                            Number($event),
                                                        )
                                                    "
                                                    class="text-right font-bold tabular-nums"
                                                />
                                                <button
                                                    v-if="
                                                        !form.apply_vuelto &&
                                                        maxAllowedForMovement(
                                                            mIndex,
                                                        ) > 0
                                                    "
                                                    @click="
                                                        setMovementAmount(
                                                            mIndex,
                                                            maxAllowedForMovement(
                                                                mIndex,
                                                            ),
                                                        )
                                                    "
                                                    class="absolute top-1/2 left-2 -translate-y-1/2 text-[10px] font-bold text-primary opacity-0 transition-opacity group-hover:opacity-100 hover:text-primary/80"
                                                >
                                                    MÁX
                                                </button>
                                            </div>
                                            <p
                                                v-if="!form.apply_vuelto"
                                                class="mt-1 text-[11px] font-bold text-muted-foreground"
                                            >
                                                Saldo restante a cancelar:
                                                {{
                                                    remainingForMovement(
                                                        mIndex,
                                                    ).toLocaleString('en-US', {
                                                        minimumFractionDigits: 4,
                                                    })
                                                }}
                                                {{ movement.currency }}
                                            </p>
                                            <p
                                                v-if="
                                                    movement.amount > 0 &&
                                                    movement.currency !==
                                                        docCurrency
                                                "
                                                class="mt-1 text-[11px] font-bold text-primary"
                                            >
                                                ≈
                                                {{
                                                    movementDocEquivalent(
                                                        movement,
                                                    ).toLocaleString('en-US', {
                                                        minimumFractionDigits: 4,
                                                    })
                                                }}
                                                {{ docCurrency }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                                >Tipo de Transacción</label
                                            >
                                            <Select v-model="movement.method">
                                                <SelectTrigger>
                                                    <SelectValue
                                                        placeholder="Método"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="m in methods"
                                                        :key="m"
                                                        :value="m"
                                                        >{{ m }}</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div>
                                        <label
                                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                            >Referencia / Voucher</label
                                        >
                                        <Input
                                            v-model="movement.reference"
                                            placeholder="N° de transacción o referencia"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                            >Observaciones</label
                                        >
                                        <Input
                                            v-model="movement.description"
                                            placeholder="Opcional"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation buttons for step 3 -->
                    <div
                        v-if="!isEditMode && currentStep === 3"
                        class="flex justify-between"
                    >
                        <Button
                            @click="prevStep"
                            variant="outline"
                            size="lg"
                            class="h-12 rounded-xl px-8 font-bold"
                        >
                            <ArrowPrev class="mr-2 h-4 w-4" />
                            Anterior
                        </Button>
                        <Button
                            @click="submit"
                            :disabled="form.processing || !canSubmit"
                            size="lg"
                            class="h-12 rounded-xl bg-primary px-8 font-bold text-primary-foreground shadow-sm transition-all hover:bg-primary/90"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            <Receipt v-else class="mr-2 h-4 w-4" />
                            Generar Cobro
                        </Button>
                    </div>
                </template>
            </div>

            <!-- Sidebar: Summary (Step 3 only) -->
            <div
                v-if="(currentStep === 3 || isEditMode) && form.customer_id"
                class="flex flex-col gap-6 lg:col-span-4"
            >
                <!-- Summary Card -->
                <div
                    class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg"
                >
                    <Calculator
                        class="absolute -right-4 -bottom-4 size-24 text-primary-foreground/10"
                    />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Total a Liquidar ({{ docCurrency }})</span>
                            <span>{{
                                totalToPay.toLocaleString('en-US', {
                                    minimumFractionDigits: 4,
                                    maximumFractionDigits: 4,
                                })
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>Total Movimientos ({{ docCurrency }})</span>
                            <span class="font-mono">{{
                                totalBankMovementsInDoc.toLocaleString(
                                    'en-US',
                                    {
                                        minimumFractionDigits: 4,
                                        maximumFractionDigits: 4,
                                    },
                                )
                            }}</span>
                        </div>

                        <Label
                            for="apply-vuelto"
                            class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-primary-foreground/15 bg-primary-foreground/5 px-3 py-2.5 text-sm font-semibold"
                        >
                            <Checkbox
                                id="apply-vuelto"
                                :model-value="form.apply_vuelto"
                                @update:model-value="
                                    form.apply_vuelto = Boolean($event)
                                "
                            />
                            <span>
                                Aplicar vuelto (pagar de más para dar cambio)
                            </span>
                        </Label>
                        <p
                            v-if="form.apply_vuelto"
                            class="text-[11px] font-medium text-primary-foreground/70"
                        >
                            Se permitirá exceder el total y se calculará el
                            vuelto en la moneda del último movimiento.
                        </p>

                        <div
                            v-if="vuelto > 0"
                            class="flex items-center justify-between border-t border-primary-foreground/10 pt-3 text-amber-100"
                        >
                            <span class="text-sm font-bold"
                                >Vuelto Estimado</span
                            >
                            <span class="text-sm font-bold">
                                {{
                                    vuelto.toLocaleString('en-US', {
                                        minimumFractionDigits: 4,
                                        maximumFractionDigits: 4,
                                    })
                                }}
                                {{ docCurrency }}
                            </span>
                        </div>

                        <div
                            class="flex items-end justify-between border-t border-primary-foreground/10 pt-3"
                        >
                            <span class="text-sm font-bold uppercase"
                                >Total {{ docCurrency }}</span
                            >
                            <span class="text-3xl font-black tabular-nums">{{
                                totalInPaymentCurrency.toLocaleString('en-US', {
                                    minimumFractionDigits: 4,
                                    maximumFractionDigits: 4,
                                })
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bank Movements Summary -->
                <div class="rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <Building2 class="size-4 text-primary" />
                        <span
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >Movimientos Bancarios</span
                        >
                    </div>
                    <div class="space-y-3 p-6">
                        <div
                            v-for="(movement, mIndex) in form.bank_movements"
                            :key="mIndex"
                            class="flex items-center justify-between rounded-lg border p-3"
                            :class="
                                movement.bank_account_id
                                    ? 'bg-muted/30'
                                    : 'border-destructive/20 bg-destructive/5'
                            "
                        >
                            <div class="flex flex-col">
                                <span class="text-xs font-bold">
                                    {{ movement.currency }} —
                                    {{ movement.method }}
                                </span>
                                <span class="text-[10px] text-muted-foreground">
                                    {{ movement.reference || 'Sin referencia' }}
                                </span>
                            </div>
                            <span class="font-mono text-sm font-bold">
                                {{
                                    Number(movement.amount || 0).toLocaleString(
                                        'en-US',
                                        {
                                            minimumFractionDigits: 4,
                                            maximumFractionDigits: 4,
                                        },
                                    )
                                }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between border-t pt-3"
                        >
                            <span
                                class="text-xs font-bold text-muted-foreground uppercase"
                                >Total Movimientos</span
                            >
                            <span class="font-mono text-sm font-bold">
                                {{
                                    totalBankMovementsInDoc.toLocaleString(
                                        'en-US',
                                        {
                                            minimumFractionDigits: 4,
                                            maximumFractionDigits: 4,
                                        },
                                    )
                                }}
                                {{ docCurrency }}
                            </span>
                        </div>

                        <div
                            v-if="
                                totalToPay > 0 &&
                                totalBankMovementsInDoc < totalToPay
                            "
                            class="flex items-center gap-2 rounded-lg border border-muted bg-muted/30 p-3 text-xs font-medium text-muted-foreground"
                        >
                            <Info class="h-4 w-4" />
                            Abono parcial: quedará saldo pendiente por cobrar
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Account Create Modal -->
        <BankAccountCreateModal
            ref="bankModalRef"
            :currency="
                form.bank_movements[activeMovementIndex]?.currency || 'USD'
            "
            @created="onBankCreated"
        />
    </div>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    margin: 0;
}
input[type='number'] {
    appearance: textfield;
    -moz-appearance: textfield;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
