<script setup lang="ts">
import { AlertCircle, Calendar, DollarSign, Building2, Landmark, Repeat } from 'lucide-vue-next'
import { watch, computed } from 'vue'
import { Button } from '@/components/ui/button'
import Input from '@/components/ui/input/Input.vue'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { useCurrencyConversion } from '@/composables/useCurrencyConversion'
import { usePayment } from '@/composables/usePayment'

interface BankAccount {
    id: number
    bank_name: string
    bank_currency: string
    bank_balance: string
    formatted_balance?: string
}

interface RateRecord {
    rate_date: string
    rate_usd: number
    rate_cop: number
    rate_eur: number
}

const props = defineProps<{
    bankAccounts: BankAccount[]
    latestRate: any
    rates?: RateRecord[]
    currency: string
    remainingBalance: number
    form: any
    errors: Record<string, string>
    processing: boolean
    submitLabel?: string
    showRate?: boolean
    showDate?: boolean
    showMethod?: boolean
    methods?: string[]
    currencies?: string[]
    disableFields?: boolean
}>()

defineEmits<{
    submit: []
}>()

const { resolveRateForStorage } = usePayment()
const { convertCurrency } = useCurrencyConversion()

const filteredBankAccounts = computed(() =>
    props.bankAccounts.filter(ba => ba.bank_currency === props.form.payment_currency)
)

const selectedBankAccount = computed(() => {
    return filteredBankAccounts.value.find(ba => ba.id == props.form.bank_account_id)
})

watch(() => props.form.payment_currency, () => {
    const isInFiltered = filteredBankAccounts.value.some(ba => ba.id == props.form.bank_account_id)

    if (!isInFiltered) {
        props.form.bank_account_id = ''
    }
})

const getBestRateForDate = (date: string): RateRecord | null => {
    if (!props.rates || props.rates.length === 0) {
        return props.latestRate || null
    }

    const normalized = date.substring(0, 10)
    const exactRate = props.rates.find(r => r.rate_date.substring(0, 10) === normalized)

    if (exactRate) {
        return exactRate
    }

    const pastRates = props.rates
        .filter(r => r.rate_date.substring(0, 10) < normalized)
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date))

    return pastRates.length > 0 ? pastRates[0] : props.rates[0]
}

const maxAmount = computed(() => {
    const balance = props.remainingBalance

    if (!balance) {
        return 0
    }

    const fromCurrency = props.currency
    const toCurrency = props.form.payment_currency

    if (!toCurrency || fromCurrency === toCurrency) {
        return balance
    }

    const currentRate = getBestRateForDate(props.form.payment_date) || props.latestRate || {}

    return convertCurrency(balance, fromCurrency, toCurrency, currentRate)
})

const updateExchangeRate = () => {
    const coin = props.form.payment_currency || props.currency
    const date = props.form.payment_date

    if (props.rates && props.rates.length > 0 && date) {
        const rate = getBestRateForDate(date)

        if (rate) {
            props.form.payment_rate = resolveRateForStorage(coin, rate)

            return
        }
    }

    props.form.payment_rate = resolveRateForStorage(coin, props.latestRate)
}

watch(() => props.form.payment_currency, () => updateExchangeRate())
watch(() => props.form.payment_date, () => updateExchangeRate())
</script>

<template>
    <div class="space-y-5">
        <!-- Error Banner -->
        <div
            v-if="Object.keys(errors).length > 0"
            class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 flex items-start gap-3"
        >
            <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
            <div>
                <h4 class="text-sm font-bold text-red-500">Corrige los siguientes errores:</h4>
                <ul class="text-xs text-red-400 list-disc list-inside mt-1 space-y-1">
                    <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                </ul>
            </div>
        </div>

        <!-- Entity Selector Slot -->
        <slot name="entity-selector" />

        <div v-if="showDate || showRate" class="grid grid-cols-2 gap-4">
            <div v-if="showDate" class="space-y-2">
                <Label class="text-zinc-400 text-xs font-semibold">Fecha de Pago</Label>
                <div class="relative">
                    <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                    <Input
                        type="date"
                        v-model="form.payment_date"
                        :disabled="disableFields"
                        class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl"
                    />
                </div>
                <span v-if="errors.payment_date" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_date }}</span>
            </div>

            <div v-if="showRate" class="space-y-2">
                <Label class="text-zinc-400 text-xs font-semibold">Moneda de Pago</Label>
                <Select v-model="form.payment_currency" :disabled="disableFields">
                    <SelectTrigger class="h-11 bg-zinc-950/50 border-zinc-800 rounded-xl">
                        <SelectValue placeholder="Moneda" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="c in currencies" :key="c" :value="c">{{ c }}</SelectItem>
                    </SelectContent>
                </Select>
                <span v-if="errors.payment_currency" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_currency }}</span>
            </div>
        </div>

        <!-- Rate -->
        <div v-if="showRate" class="p-4 bg-indigo-500/5 rounded-2xl border border-indigo-500/10 space-y-2">
            <div class="flex justify-between items-center mb-1">
                <Label class="text-indigo-400 text-[10px] font-black uppercase tracking-wider">Tasa de Cambio</Label>
            </div>
            <div class="relative">
                <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-400/50" />
                <Input
                    type="number"
                    step="0.0001"
                    v-model="form.payment_rate"
                    :disabled="disableFields"
                    class="pl-10 h-11 bg-zinc-950 border-zinc-800 text-sm font-mono font-bold text-indigo-400 focus:ring-indigo-600 rounded-xl disabled:opacity-50"
                />
            </div>
            <span v-if="errors.payment_rate" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_rate }}</span>
        </div>

        <!-- Conversion Notice -->
        <div
            v-if="showRate && currency !== form.payment_currency"
            class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl space-y-3"
        >
            <div class="flex items-center gap-2 text-amber-400 text-[10px] font-black uppercase tracking-wider">
                <Repeat class="w-4 h-4" /> Conversión de Moneda
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-zinc-300 font-semibold">Total a pagar</span>
                <span class="text-amber-400 font-black text-lg">{{ maxAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} {{ form.payment_currency }}</span>
            </div>
            <div class="text-xs text-zinc-500 text-right">
                (equivalente a {{ Number(remainingBalance).toLocaleString('en-US', { minimumFractionDigits: 2 }) }} {{ currency }})
            </div>
        </div>

        <!-- Bank Account -->
        <div class="space-y-2">
            <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Banco y Cuenta</Label>
            <Select v-model="form.bank_account_id" :disabled="disableFields">
                <SelectTrigger class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl focus:ring-indigo-600">
                    <SelectValue placeholder="Seleccione cuenta bancaria" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="ba in filteredBankAccounts" :key="ba.id" :value="ba.id.toString()">
                        <div class="flex flex-col items-start py-0.5">
                            <span class="font-bold text-zinc-200">{{ ba.bank_name }}</span>
                            <span class="text-[10px] font-mono text-zinc-500">{{ ba.bank_currency }}</span>
                        </div>
                    </SelectItem>
                </SelectContent>
            </Select>
            <span v-if="errors.bank_account_id" class="text-xs text-red-500 font-bold block mt-1">{{ errors.bank_account_id }}</span>
        </div>

        <!-- Amount -->
        <div class="space-y-2">
            <Label class="text-zinc-400 text-xs font-semibold tracking-wide">
                Monto a Pagar
                <span class="text-zinc-600 font-normal">(en moneda del banco)</span>
            </Label>
            <Input
                type="number"
                step="0.01"
                min="0.01"
                v-model="form.amount"
                :disabled="disableFields"
                class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl text-right text-lg font-bold tabular-nums focus:ring-indigo-600"
                :placeholder="remainingBalance.toString()"
            />
            <div class="flex justify-between text-xs">
                <span class="text-zinc-500">Saldo pendiente: <strong class="text-zinc-300">{{ Number(remainingBalance).toLocaleString() }} {{ currency }}</strong></span>
                <button
                    type="button"
                    @click="form.amount = maxAmount"
                    class="text-indigo-500 hover:text-indigo-400 font-black text-[10px] uppercase tracking-wider"
                >
                    Máximo
                </button>
            </div>
            <span v-if="errors.amount" class="text-xs text-red-500 font-bold block mt-1">{{ errors.amount }}</span>
        </div>

        <!-- Method -->
        <div v-if="showMethod" class="space-y-2">
            <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Tipo de Transacción</Label>
            <Select v-model="form.payment_method" :disabled="disableFields">
                <SelectTrigger class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl">
                    <SelectValue placeholder="Método de pago" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="m in methods" :key="m" :value="m">{{ m }}</SelectItem>
                </SelectContent>
            </Select>
            <span v-if="errors.payment_method" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_method }}</span>
        </div>

        <!-- Items Table Slot -->
        <slot name="items-table" />

        <!-- Reference -->
        <div class="space-y-2">
            <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Referencia / Voucher</Label>
            <Input
                v-model="form.reference"
                :disabled="disableFields"
                placeholder="N° de transacción o referencia"
                class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl"
                maxlength="50"
            />
            <span v-if="errors.reference" class="text-xs text-red-500 font-bold block mt-1">{{ errors.reference }}</span>
        </div>

        <!-- Description -->
        <div class="space-y-2">
            <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Observaciones</Label>
            <Input
                v-model="form.description"
                :disabled="disableFields"
                placeholder="Opcional"
                class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl"
                maxlength="150"
            />
            <span v-if="errors.description" class="text-xs text-red-500 font-bold block mt-1">{{ errors.description }}</span>
        </div>

        <!-- Extra Fields Slot -->
        <slot name="after-fields" />

        <!-- Bank Balance Info -->
        <div
            v-if="selectedBankAccount"
            class="mt-4 p-4 rounded-xl flex items-center gap-4 border"
            :class="Number(selectedBankAccount.bank_balance) >= Number(form.amount || 0) ? 'bg-green-500/5 border-green-500/10' : 'bg-red-500/5 border-red-500/10'"
        >
            <div
                :class="Number(selectedBankAccount.bank_balance) >= Number(form.amount || 0) ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'"
                class="p-2.5 rounded-lg"
            >
                <Landmark class="w-5 h-5" />
            </div>
            <div class="flex-1">
                <p class="text-[10px] font-black uppercase text-zinc-500">Saldo Disponible en Banco</p>
                <p class="text-sm font-bold antialiased text-zinc-200">
                    {{ Number(selectedBankAccount.bank_balance).toLocaleString() }} {{ selectedBankAccount.bank_currency }}
                </p>
            </div>
            <div
                v-if="Number(selectedBankAccount.bank_balance) < Number(form.amount || 0)"
                class="flex items-center gap-1.5 text-red-500 text-[10px] font-black bg-red-500/10 px-3 py-1 rounded-full uppercase"
            >
                <AlertCircle class="w-3 h-3" /> Fondos Insuficientes
            </div>
        </div>

        <!-- Submit Button (slot override or default) -->
        <div v-if="$slots.actions" class="pt-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<style scoped>
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button {
    appearance: none;
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    appearance: textfield;
    -moz-appearance: textfield;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
