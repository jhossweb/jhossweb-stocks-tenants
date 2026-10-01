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
    pay_valorcam:
        props.payroll.pay_valorcam ||
        (props.latestRate ? Number(props.latestRate.rate_usd) : 1),
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

watch(
    () => form.pay_employee_id,
    (id) => {
        if (!id) {
            return;
        }

        const emp = props.employees.find((e) => e.id == id);

        if (emp) {
            form.pay_base_salary = Number(emp.emp_base_salary);
            form.pay_coin = emp.emp_salary_coin;
            updateExchangeRate(emp.emp_salary_coin);
        }
    },
);

watch(
    () => form.pay_coin,
    (val) => {
        updateExchangeRate(val);
    },
);

const submit = () => {
    form.pay_net_salary = netSalary.value;
    form.put(`/payrolls/${props.payroll.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Nómina" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header -->
            <div
                class="relative flex items-center justify-between overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/50 p-6 shadow-2xl backdrop-blur-xl"
            >
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <Wallet class="h-32 w-32" />
                </div>

                <div class="relative z-10 flex items-center gap-4">
                    <Link
                        href="/payrolls"
                        class="rounded-xl border border-zinc-700/50 bg-zinc-800/50 p-2.5 text-zinc-400 transition-all hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1
                            class="flex items-center gap-2 text-2xl font-black tracking-tight"
                        >
                            Editar Nómina
                        </h1>
                        <p
                            class="text-xs font-medium tracking-wide text-zinc-500 first-letter:uppercase"
                        >
                            Modifica los datos de la nómina
                        </p>
                    </div>
                </div>

                <Button
                    @click="submit"
                    :disabled="form.processing"
                    size="lg"
                    class="relative z-10 h-12 rounded-xl bg-indigo-600 px-8 font-bold text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-500 active:scale-95"
                >
                    <Loader2
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Save v-else class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </Button>
            </div>

            <!-- Errors -->
            <div
                v-if="Object.keys(form.errors).length > 0"
                class="flex items-start gap-3 rounded-2xl border border-red-500/20 bg-red-500/10 p-4"
            >
                <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
                <div>
                    <h4 class="text-sm font-bold text-red-500">
                        Por favor, corrige los siguientes errores:
                    </h4>
                    <ul
                        class="mt-1 list-inside list-disc space-y-1 text-xs text-red-400"
                    >
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left Sidebar: Form -->
                <div class="space-y-6 lg:col-span-7">
                    <div
                        class="space-y-6 rounded-2xl border border-zinc-800 bg-zinc-900 p-6 shadow-sm"
                    >
                        <div
                            class="mb-2 flex items-center gap-2 text-[10px] font-black tracking-[0.2em] text-indigo-400 uppercase"
                        >
                            <Wallet class="h-4 w-4" /> Datos de la Nómina
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Employee -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold text-zinc-400"
                                    >Empleado</Label
                                >
                                <Select v-model="form.pay_employee_id">
                                    <SelectTrigger
                                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50"
                                    >
                                        <SelectValue
                                            placeholder="Seleccione empleado"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="emp in employees"
                                            :key="emp.id"
                                            :value="emp.id.toString()"
                                        >
                                            <div
                                                class="flex flex-col items-start py-0.5"
                                            >
                                                <span
                                                    class="font-bold text-zinc-200"
                                                    >{{ emp.emp_name }}</span
                                                >
                                                <span
                                                    class="font-mono text-[10px] text-zinc-500"
                                                    >{{
                                                        Number(
                                                            emp.emp_base_salary,
                                                        ).toLocaleString()
                                                    }}
                                                    {{
                                                        emp.emp_salary_coin
                                                    }}</span
                                                >
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.pay_employee_id"
                                    class="mt-1 block text-xs font-bold text-red-500"
                                    >{{ form.errors.pay_employee_id }}</span
                                >
                            </div>

                            <!-- Period -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold text-zinc-400"
                                    >Período</Label
                                >
                                <Select v-model="form.pay_period_id">
                                    <SelectTrigger
                                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50"
                                    >
                                        <SelectValue
                                            placeholder="Seleccione período"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="period in periods"
                                            :key="period.id"
                                            :value="period.id.toString()"
                                        >
                                            {{
                                                formatDate(period.pp_start_date)
                                            }}
                                            al
                                            {{ formatDate(period.pp_end_date) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.pay_period_id"
                                    class="mt-1 block text-xs font-bold text-red-500"
                                    >{{ form.errors.pay_period_id }}</span
                                >
                            </div>

                            <!-- Base Salary -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold text-zinc-400"
                                    >Salario Base</Label
                                >
                                <div class="relative">
                                    <DollarSign
                                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                                    />
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        v-model="form.pay_base_salary"
                                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50 pl-10 text-sm focus:ring-indigo-600"
                                        placeholder="0.0000"
                                    />
                                </div>
                                <span
                                    v-if="form.errors.pay_base_salary"
                                    class="mt-1 block text-xs font-bold text-red-500"
                                    >{{ form.errors.pay_base_salary }}</span
                                >
                            </div>

                            <!-- Deductions -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold text-zinc-400"
                                    >Deducciones</Label
                                >
                                <div class="relative">
                                    <DollarSign
                                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                                    />
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        v-model="form.pay_total_deductions"
                                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50 pl-10 text-sm focus:ring-indigo-600"
                                        placeholder="0.0000"
                                    />
                                </div>
                                <span
                                    v-if="form.errors.pay_total_deductions"
                                    class="mt-1 block text-xs font-bold text-red-500"
                                    >{{
                                        form.errors.pay_total_deductions
                                    }}</span
                                >
                            </div>

                            <!-- Currency -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold text-zinc-400"
                                    >Moneda</Label
                                >
                                <Select v-model="form.pay_coin">
                                    <SelectTrigger
                                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50"
                                    >
                                        <SelectValue placeholder="Moneda" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="coin in coinOptions"
                                            :key="coin"
                                            :value="coin"
                                            >{{ coin }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.pay_coin"
                                    class="mt-1 block text-xs font-bold text-red-500"
                                    >{{ form.errors.pay_coin }}</span
                                >
                            </div>

                            <!-- Exchange Rate -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold text-zinc-400"
                                    >Tasa de Cambio</Label
                                >
                                <div class="relative">
                                    <DollarSign
                                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                                    />
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        v-model="form.pay_valorcam"
                                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50 pl-10 font-mono text-sm font-bold text-indigo-400 focus:ring-indigo-600"
                                    />
                                </div>
                                <span
                                    v-if="form.errors.pay_valorcam"
                                    class="mt-1 block text-xs font-bold text-red-500"
                                    >{{ form.errors.pay_valorcam }}</span
                                >
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold text-zinc-400"
                                    >Estado</Label
                                >
                                <Select v-model="form.pay_status">
                                    <SelectTrigger
                                        class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50"
                                    >
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="status in statusOptions"
                                            :key="status"
                                            :value="status"
                                        >
                                            <span class="capitalize">{{
                                                status
                                            }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.pay_status"
                                    class="mt-1 block text-xs font-bold text-red-500"
                                    >{{ form.errors.pay_status }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Summary -->
                <div class="space-y-6 lg:col-span-5">
                    <div
                        class="group relative overflow-hidden rounded-3xl bg-indigo-600 p-8 text-white shadow-2xl"
                    >
                        <div
                            class="absolute -right-6 -bottom-6 opacity-10 transition-transform duration-700 group-hover:scale-110"
                        >
                            <Calculator class="h-48 w-48" />
                        </div>

                        <div class="relative z-10 space-y-6">
                            <div class="space-y-1">
                                <p
                                    class="text-[10px] font-black tracking-widest text-indigo-200 uppercase"
                                >
                                    Resumen
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-indigo-200"
                                        >Salario Base</span
                                    >
                                    <span
                                        class="text-lg font-black tabular-nums"
                                    >
                                        {{ form.pay_coin }}
                                        {{
                                            Number(
                                                form.pay_base_salary || 0,
                                            ).toLocaleString('en-US', {
                                                minimumFractionDigits: 4,
                                            })
                                        }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-indigo-200"
                                        >Deducciones</span
                                    >
                                    <span
                                        class="text-lg font-black tabular-nums"
                                    >
                                        {{ form.pay_coin }}
                                        {{
                                            Number(
                                                form.pay_total_deductions || 0,
                                            ).toLocaleString('en-US', {
                                                minimumFractionDigits: 4,
                                            })
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between border-t border-white/10 pt-4"
                                >
                                    <span
                                        class="text-sm font-black tracking-wider uppercase"
                                        >Sueldo Neto</span
                                    >
                                    <span
                                        class="text-3xl font-black tracking-tighter tabular-nums"
                                    >
                                        {{ form.pay_coin }}
                                        {{
                                            netSalary.toLocaleString('en-US', {
                                                minimumFractionDigits: 4,
                                                maximumFractionDigits: 4,
                                            })
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between border-t border-white/10 pt-4"
                            >
                                <div
                                    class="text-[10px] font-bold tracking-wider text-indigo-100 uppercase"
                                >
                                    Tasa Aplicada
                                </div>
                                <div class="font-mono text-sm font-bold">
                                    {{
                                        Number(form.pay_valorcam || 1).toFixed(
                                            4,
                                        )
                                    }}
                                </div>
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
input[type='number'] {
    appearance: textfield;
    -moz-appearance: textfield;
}
</style>
