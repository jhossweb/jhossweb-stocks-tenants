<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Loader2,
    Wallet,
    Calculator,
    DollarSign,
    AlertCircle,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/composables/useDate';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Nóminas', href: '/payrolls' },
            { title: 'Editar', href: '' },
        ],
    },
});

const props = defineProps<{
    payroll: any;
    employees: any[];
    periods: any[];
    statusOptions: string[];
    coinOptions: string[];
    latestRate: any;
    rates: any[];
}>();

const form = useForm({
    pay_employee_id: props.payroll.pay_employee_id?.toString() || '',
    pay_period_id: props.payroll.pay_period_id?.toString() || '',
    pay_base_salary: props.payroll.pay_base_salary || '',
    pay_coin: props.payroll.pay_coin || 'USD',
    pay_valorcam: props.payroll.pay_valorcam || (props.latestRate ? Number(props.latestRate.rate_usd) : 1),
    pay_total_deductions: props.payroll.pay_total_deductions || 0,
    pay_net_salary: props.payroll.pay_net_salary || 0,
    pay_status: props.payroll.pay_status || 'pendiente',
});

const netSalary = computed(() => {
    const base = Number(form.pay_base_salary) || 0;
    const deductions = Number(form.pay_total_deductions) || 0;

    return base - deductions;
});

const updateExchangeRate = (coin?: string) => {
    if (!props.latestRate) {
return;
}

    const c = coin || form.pay_coin;

    if (c === 'USD' || c === 'BS' || c === 'VES') {
        form.pay_valorcam = Number(props.latestRate.rate_usd);
    } else if (c === 'COP') {
        form.pay_valorcam = Number(props.latestRate.rate_cop);
    } else if (c === 'EUR') {
        form.pay_valorcam = Number(props.latestRate.rate_eur);
    }
};

watch(() => form.pay_employee_id, (id) => {
    if (!id) {
return;
}

    const emp = props.employees.find(e => e.id == id);

    if (emp) {
        form.pay_base_salary = Number(emp.emp_base_salary);
        form.pay_coin = emp.emp_salary_coin;
        updateExchangeRate(emp.emp_salary_coin);
    }
});

watch(() => form.pay_coin, (val) => {
    updateExchangeRate(val);
});

const submit = () => {
    form.pay_net_salary = netSalary.value;
    form.put(`/payrolls/${props.payroll.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Nómina" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between bg-zinc-900/50 backdrop-blur-xl p-6 rounded-2xl border border-zinc-800 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <Wallet class="w-32 h-32" />
                </div>

                <div class="flex items-center gap-4 relative z-10">
                    <Link href="/payrolls" class="p-2.5 bg-zinc-800/50 hover:bg-zinc-800 rounded-xl transition-all text-zinc-400 hover:text-white border border-zinc-700/50">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight flex items-center gap-2">
                            Editar Nómina
                        </h1>
                        <p class="text-xs text-zinc-500 font-medium tracking-wide first-letter:uppercase">
                            Modifica los datos de la nómina
                        </p>
                    </div>
                </div>

                <Button
                    @click="submit"
                    :disabled="form.processing"
                    size="lg"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold h-12 px-8 rounded-xl shadow-lg shadow-indigo-500/20 active:scale-95 transition-all relative z-10"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                    <Save v-else class="w-4 h-4 mr-2" />
                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </Button>
            </div>

            <!-- Errors -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                <div>
                    <h4 class="text-sm font-bold text-red-500">Por favor, corrige los siguientes errores:</h4>
                    <ul class="text-xs text-red-400 list-disc list-inside mt-1 space-y-1">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Left Sidebar: Form -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-zinc-900 rounded-2xl border border-zinc-800 p-6 space-y-6 shadow-sm">
                        <div class="flex items-center gap-2 text-indigo-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2">
                            <Wallet class="w-4 h-4" /> Datos de la Nómina
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Employee -->
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Empleado</Label>
                                <Select v-model="form.pay_employee_id">
                                    <SelectTrigger class="h-11 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                        <SelectValue placeholder="Seleccione empleado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="emp in employees" :key="emp.id" :value="emp.id.toString()">
                                            <div class="flex flex-col items-start py-0.5">
                                                <span class="font-bold text-zinc-200">{{ emp.emp_name }}</span>
                                                <span class="text-[10px] font-mono text-zinc-500">{{ Number(emp.emp_base_salary).toLocaleString() }} {{ emp.emp_salary_coin }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.pay_employee_id" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pay_employee_id }}</span>
                            </div>

                            <!-- Period -->
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Período</Label>
                                <Select v-model="form.pay_period_id">
                                    <SelectTrigger class="h-11 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                        <SelectValue placeholder="Seleccione período" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="period in periods" :key="period.id" :value="period.id.toString()">
                                            {{ formatDate(period.pp_start_date) }} al {{ formatDate(period.pp_end_date) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.pay_period_id" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pay_period_id }}</span>
                            </div>

                            <!-- Base Salary -->
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Salario Base</Label>
                                <div class="relative">
                                    <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        v-model="form.pay_base_salary"
                                        class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl"
                                        placeholder="0.0000"
                                    />
                                </div>
                                <span v-if="form.errors.pay_base_salary" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pay_base_salary }}</span>
                            </div>

                            <!-- Deductions -->
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Deducciones</Label>
                                <div class="relative">
                                    <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        v-model="form.pay_total_deductions"
                                        class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl"
                                        placeholder="0.0000"
                                    />
                                </div>
                                <span v-if="form.errors.pay_total_deductions" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pay_total_deductions }}</span>
                            </div>

                            <!-- Currency -->
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Moneda</Label>
                                <Select v-model="form.pay_coin">
                                    <SelectTrigger class="h-11 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                        <SelectValue placeholder="Moneda" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="coin in coinOptions" :key="coin" :value="coin">{{ coin }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.pay_coin" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pay_coin }}</span>
                            </div>

                            <!-- Exchange Rate -->
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Tasa de Cambio</Label>
                                <div class="relative">
                                    <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        v-model="form.pay_valorcam"
                                        class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm font-mono text-indigo-400 font-bold focus:ring-indigo-600 rounded-xl"
                                    />
                                </div>
                                <span v-if="form.errors.pay_valorcam" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pay_valorcam }}</span>
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Estado</Label>
                                <Select v-model="form.pay_status">
                                    <SelectTrigger class="h-11 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="status in statusOptions" :key="status" :value="status">
                                            <span class="capitalize">{{ status }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.pay_status" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pay_status }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Summary -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-indigo-600 text-white rounded-3xl p-8 shadow-2xl relative overflow-hidden group">
                        <div class="absolute -right-6 -bottom-6 opacity-10 group-hover:scale-110 transition-transform duration-700">
                            <Calculator class="w-48 h-48" />
                        </div>

                        <div class="relative z-10 space-y-6">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">Resumen</p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-indigo-200 font-medium">Salario Base</span>
                                    <span class="text-lg font-black tabular-nums">
                                        {{ form.pay_coin }}
                                        {{ Number(form.pay_base_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-indigo-200 font-medium">Deducciones</span>
                                    <span class="text-lg font-black tabular-nums">
                                        {{ form.pay_coin }}
                                        {{ Number(form.pay_total_deductions || 0).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                    </span>
                                </div>

                                <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                                    <span class="text-sm font-black uppercase tracking-wider">Sueldo Neto</span>
                                    <span class="text-3xl font-black tabular-nums tracking-tighter">
                                        {{ form.pay_coin }}
                                        {{ netSalary.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) }}
                                    </span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                                <div class="text-[10px] font-bold text-indigo-100 uppercase tracking-wider">Tasa Aplicada</div>
                                <div class="text-sm font-mono font-bold">{{ Number(form.pay_valorcam || 1).toFixed(4) }}</div>
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
</style>
