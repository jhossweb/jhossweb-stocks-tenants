<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeft, Save, Loader2, Building2, Landmark, Calendar, DollarSign, AlertCircle, CheckCircle2, Calculator, Repeat } from 'lucide-vue-next'
import { computed, watch } from 'vue'
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
import { usePayment } from '@/composables/usePayment'

interface BankAccount {
    id: number
    bank_name: string
    bank_currency: string
    bank_balance: string
    formatted_balance?: string
    bank_account_number?: string
}

const props = defineProps<{
    title: string
    subtitle: string
    backRoute: string
    form: any
    errors: Record<string, string>
    processing: boolean
    submitLabel: string
    bankAccounts: BankAccount[]
    latestRate: any
    currencies: string[]
    methods: string[]
    totalLabel: string
    totalAmount: number
    totalCurrency: string
    originalTotal?: number
    originalCurrency?: string
    estimatedDiferencial?: number
}>()

const emit = defineEmits<{
    submit: []
}>()

const { resolveRate } = usePayment()

const filteredBankAccounts = computed(() =>
    props.bankAccounts.filter(ba => ba.bank_currency === props.form.payment_currency)
)

const selectedBankAccount = computed(() => {
    return filteredBankAccounts.value.find(ba => ba.id == props.form.bank_account_id)
})

const isInsufficientFunds = computed(() => {
    const account = selectedBankAccount.value

    if (!account) {
        return false
    }

    const amount = Number(props.form.amount || 0)

    return amount > 0 && Number(account.bank_balance) < amount
})

watch(() => props.form.payment_currency, () => {
    const isInFiltered = filteredBankAccounts.value.some(ba => ba.id == props.form.bank_account_id)

    if (!isInFiltered) {
        props.form.bank_account_id = ''
    }

    updateExchangeRate()
})

const updateExchangeRate = () => {
    if (!props.latestRate) {
return
}

    props.form.payment_rate = resolveRate(props.form.payment_currency, props.latestRate)
}

watch(() => props.form.payment_currency, () => updateExchangeRate(), { immediate: true })
</script>

<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between bg-zinc-900/50 backdrop-blur-xl p-6 rounded-2xl border border-zinc-800 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <CheckCircle2 class="w-32 h-32" />
                </div>

                <div class="flex items-center gap-4 relative z-10">
                    <Link :href="backRoute" class="p-2.5 bg-zinc-800/50 hover:bg-zinc-800 rounded-xl transition-all text-zinc-400 hover:text-white border border-zinc-700/50">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight flex items-center gap-2">
                            {{ title }}
                        </h1>
                        <p class="text-xs text-zinc-500 font-medium tracking-wide first-letter:uppercase">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <Button
                    @click="emit('submit')"
                    :disabled="processing || isInsufficientFunds"
                    size="lg"
                    :class="isInsufficientFunds ? 'bg-red-600/50 text-red-300 cursor-not-allowed h-12 px-8 rounded-xl' : 'bg-indigo-600 hover:bg-indigo-500 text-white font-bold h-12 px-8 rounded-xl shadow-lg shadow-indigo-500/20 active:scale-95 transition-all relative z-10'"
                >
                    <Loader2 v-if="processing" class="w-4 h-4 mr-2 animate-spin" />
                    <Save v-else-if="!isInsufficientFunds" class="w-4 h-4 mr-2" />
                    <AlertCircle v-else class="w-4 h-4 mr-2" />
                    {{ isInsufficientFunds ? 'Fondos Insuficientes' : submitLabel }}
                </Button>
            </div>

            <!-- Errors -->
            <div v-if="Object.keys(errors).length > 0" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                <div>
                    <h4 class="text-sm font-bold text-red-500">Por favor, corrige los siguientes errores:</h4>
                    <ul class="text-xs text-red-400 list-disc list-inside mt-1 space-y-1">
                        <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Left Sidebar: Basic Info -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-zinc-900 rounded-2xl border border-zinc-800 p-6 space-y-6 shadow-sm">
                        <div class="flex items-center gap-2 text-indigo-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2">
                            <Landmark class="w-4 h-4" /> Datos del Beneficiario
                        </div>

                        <div class="space-y-4">
                            <slot name="entity-selector" />

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold">Fecha de Pago</Label>
                                    <div class="relative">
                                        <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                                        <Input type="date" v-model="form.payment_date" :class="{'border-red-500': errors.payment_date}" class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl" />
                                    </div>
                                    <span v-if="errors.payment_date" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_date }}</span>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold">Moneda de Pago</Label>
                                    <Select v-model="form.payment_currency">
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

                            <div class="p-4 bg-indigo-500/5 rounded-2xl border border-indigo-500/10 space-y-2">
                                <div class="flex justify-between items-center mb-1">
                                    <Label class="text-indigo-400 text-[10px] font-black uppercase tracking-wider">Tasa de Cambio</Label>
                                </div>
                                <div class="relative">
                                    <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-400/50" />
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        v-model="form.payment_rate"
                                        class="pl-10 h-11 bg-zinc-950 border-zinc-800 text-sm font-mono font-bold text-indigo-400 focus:ring-indigo-600 rounded-xl"
                                    />
                                </div>
                                <span v-if="errors.payment_rate" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_rate }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Summary Box -->
                    <div class="bg-indigo-600 text-white rounded-3xl p-8 shadow-2xl relative overflow-hidden group">
                        <div class="absolute -right-6 -bottom-6 opacity-10 group-hover:scale-110 transition-transform duration-700">
                            <Calculator class="w-48 h-48" />
                        </div>

                        <div class="relative z-10 space-y-6">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">{{ totalLabel }}</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-indigo-200">{{ totalCurrency }}</span>
                                    <span class="text-5xl font-black tabular-nums tracking-tighter">{{ totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                                <div class="text-[10px] font-bold text-indigo-100 uppercase tracking-wider">Tasa Aplicada</div>
                                <div class="text-sm font-mono font-bold">{{ Number(form.payment_rate || 1).toFixed(4) }}</div>
                            </div>

                            <!-- Conversion Notice -->
                            <div
                                v-if="originalTotal !== undefined && originalCurrency && originalCurrency !== form.payment_currency"
                                class="pt-4 border-t border-white/10 space-y-2"
                            >
                                <div class="flex items-center gap-2 text-[10px] font-bold text-indigo-100 uppercase tracking-wider">
                                    <Repeat class="w-3.5 h-3.5" /> Conversión de Moneda
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-indigo-200 text-sm">Total a pagar</span>
                                    <span class="font-black text-white text-lg">{{ totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} {{ form.payment_currency }}</span>
                                </div>
                                <div class="text-xs text-indigo-300/70 text-right">
                                    (equivalente a {{ originalTotal.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} {{ originalCurrency }})
                                </div>
                            </div>

                            <!-- Diferencial Preview -->
                            <div
                                v-if="estimatedDiferencial !== undefined && estimatedDiferencial !== 0 && form.payment_currency !== 'USD'"
                                class="pt-4 border-t border-white/10 space-y-2"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-indigo-100 uppercase tracking-wider">Diferencial Cambiario</span>
                                    <span class="text-sm font-black tabular-nums" :class="estimatedDiferencial > 0 ? 'text-green-300' : 'text-orange-300'">
                                        {{ estimatedDiferencial > 0 ? '+' : '' }}${{ Math.abs(estimatedDiferencial).toFixed(2) }} USD
                                    </span>
                                </div>
                                <div class="text-[10px] text-indigo-300/60 text-right">
                                    {{ estimatedDiferencial > 0 ? 'Ganancia por diferencia de tasas' : 'Pérdida por diferencia de tasas' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Section: Items & Bank Info -->
                <div class="lg:col-span-8 space-y-8">

                    <!-- Items Table -->
                    <slot name="items-table" />

                    <!-- Payment Sources -->
                    <div class="bg-zinc-900 rounded-2xl border border-zinc-800 p-6 space-y-6 shadow-sm">
                        <div class="flex items-center gap-2 text-indigo-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2">
                            <Building2 class="w-4 h-4" /> Origen de Fondos
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Banco y Cuenta</Label>
                                    <Select v-model="form.bank_account_id">
                                        <SelectTrigger class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl focus:ring-indigo-600">
                                            <SelectValue placeholder="Seleccione cuenta bancaria" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="ba in filteredBankAccounts" :key="ba.id" :value="ba.id.toString()">
                                                <div class="flex flex-col items-start py-0.5">
                                                    <span class="font-bold text-zinc-200">{{ ba.bank_name }}</span>
                                                    <span class="text-[10px] font-mono text-zinc-500">{{ ba.bank_account_number || ba.bank_currency }} ({{ ba.bank_currency }})</span>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="errors.bank_account_id" class="text-xs text-red-500 font-bold block mt-1">{{ errors.bank_account_id }}</span>
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Tipo de Transacción</Label>
                                    <Select v-model="form.payment_method">
                                        <SelectTrigger class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                            <SelectValue placeholder="Método de pago" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="m in methods" :key="m" :value="m">{{ m }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="errors.payment_method" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_method }}</span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Referencia / Voucher</Label>
                                    <Input v-model="form.payment_reference" placeholder="N° de transacción o referencia" :class="{'border-red-500': errors.payment_reference}" class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl" />
                                    <span v-if="errors.payment_reference" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_reference }}</span>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Observaciones</Label>
                                    <Input v-model="form.payment_description" placeholder="Opcional" :class="{'border-red-500': errors.payment_description}" class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl" />
                                    <span v-if="errors.payment_description" class="text-xs text-red-500 font-bold block mt-1">{{ errors.payment_description }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Account Balance Info -->
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
                                <p class="text-sm font-bold antialiased">{{ Number(selectedBankAccount.bank_balance).toLocaleString() }} {{ selectedBankAccount.bank_currency }}</p>
                            </div>
                            <div
                                v-if="Number(selectedBankAccount.bank_balance) < Number(form.amount || 0)"
                                class="flex items-center gap-1.5 text-red-500 text-[10px] font-black bg-red-500/10 px-3 py-1 rounded-full uppercase"
                            >
                                <AlertCircle class="w-3 h-3" /> Fondos Insuficientes
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
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
