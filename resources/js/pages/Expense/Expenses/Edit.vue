<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, DollarSign, Loader2, ChevronDown, ChevronUp, Banknote } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gastos', href: '/expenses' },
            { title: 'Editar', href: '' },
        ],
    },
});

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
}

interface BankTransaction {
    id: number;
    btr_amount: string;
    btr_coin: string;
    btr_valorcam: string;
    btr_reference: string;
    btr_description: string;
    btr_balance: string;
    created_at: string;
    bank_account: BankAccount;
}

const props = defineProps<{
    expense: any;
    categories: any[];
    statusOptions: string[];
    coinOptions: string[];
    bankAccounts: BankAccount[];
    latestRate: any;
}>();

const isPaid = props.expense.exp_status === 'pagado';
const showPayments = ref(false);

const form = useForm({
    exp_category_id: props.expense.exp_category_id || '',
    exp_description: props.expense.exp_description || '',
    exp_amount: props.expense.exp_amount || '',
    exp_date: props.expense.exp_date || new Date().toISOString().split('T')[0],
    exp_status: props.expense.exp_status || 'pendiente',
    exp_coin: props.expense.exp_coin || 'USD',
    exp_valorcam: props.expense.exp_valorcam || 1,
});

const updateExchangeRate = () => {
    if (!props.latestRate) {
return;
}

    switch (form.exp_coin) {
        case 'USD':
            form.exp_valorcam = 1;
            break;
        case 'COP':
            form.exp_valorcam = props.latestRate.rate_cop ?? 1;
            break;
        case 'EUR':
            form.exp_valorcam = props.latestRate.rate_eur ?? 1;
            break;
        default:
            form.exp_valorcam = props.latestRate.rate_usd ?? 1;
    }
};

const submit = () => {
    form.put(`/expenses/${props.expense.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Gasto" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/expenses" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <DollarSign class="h-8 w-8 text-primary" />
                        Editar Gasto
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        {{ isPaid ? 'Este gasto ya fue pagado — solo vista informativa.' : 'Modifica los datos del gasto.' }}
                    </p>
                </div>
            </div>
            <button
                v-if="!isPaid"
                @click="submit"
                :disabled="form.processing"
                class="inline-flex items-center px-5 py-2.5 bg-primary text-white rounded-lg font-semibold transition-all disabled:opacity-50 shadow-lg"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                <Save v-else class="w-4 h-4 mr-2" />
                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
        </div>

        <div v-if="isPaid" class="bg-green-900/20 border border-green-500/50 p-4 rounded-xl">
            <p class="text-sm text-green-400 font-semibold">Este gasto está pagado. Los campos son de solo lectura.</p>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="bg-red-900/20 border border-red-500/50 p-4 rounded-xl">
            <p class="text-sm text-red-400 font-bold mb-1">Hay problemas con el envío:</p>
            <ul class="list-disc list-inside text-xs text-red-300">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Categoría</label>
                    <select v-model="form.exp_category_id" :disabled="isPaid" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" :class="{ 'opacity-60 cursor-not-allowed': isPaid }">
                        <option value="">Seleccione categoría</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.exc_name }} ({{ cat.exc_type }})</option>
                    </select>
                    <InputError :message="form.errors.exp_category_id" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Monto</label>
                    <Input type="number" step="0.01" min="0" v-model="form.exp_amount" class="w-full" :disabled="isPaid" />
                    <InputError :message="form.errors.exp_amount" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Moneda</label>
                    <select v-model="form.exp_coin" :disabled="isPaid" @change="updateExchangeRate" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" :class="{ 'opacity-60 cursor-not-allowed': isPaid }">
                        <option v-for="coin in coinOptions" :key="coin" :value="coin">{{ coin }}</option>
                    </select>
                    <InputError :message="form.errors.exp_coin" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tasa de Cambio</label>
                    <Input type="number" step="0.0001" min="0" v-model="form.exp_valorcam" class="w-full" :disabled="isPaid" />
                    <InputError :message="form.errors.exp_valorcam" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Fecha del Gasto</label>
                    <Input type="date" v-model="form.exp_date" class="w-full" :disabled="isPaid" />
                    <InputError :message="form.errors.exp_date" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Estado</label>
                    <select v-model="form.exp_status" :disabled="isPaid" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" :class="{ 'opacity-60 cursor-not-allowed': isPaid }">
                        <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                    </select>
                    <InputError :message="form.errors.exp_status" />
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Descripción</label>
                    <textarea v-model="form.exp_description" rows="3" :disabled="isPaid" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" :class="{ 'opacity-60 cursor-not-allowed': isPaid }"></textarea>
                    <InputError :message="form.errors.exp_description" />
                </div>
            </div>
        </div>

        <div v-if="expense.payments?.length" class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
            <button @click="showPayments = !showPayments" class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                <Banknote class="w-4 h-4" />
                Historial de Pagos ({{ expense.payments.length }})
                <ChevronDown v-if="!showPayments" class="w-4 h-4" />
                <ChevronUp v-else class="w-4 h-4" />
            </button>
            <div v-if="showPayments" class="mt-3 overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase">
                        <tr class="border-b dark:border-gray-700">
                            <th class="px-4 py-2">Fecha</th>
                            <th class="px-4 py-2">Monto</th>
                            <th class="px-4 py-2">Moneda</th>
                            <th class="px-4 py-2">Tasa</th>
                            <th class="px-4 py-2">Referencia</th>
                            <th class="px-4 py-2">Banco</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pmt in expense.payments" :key="pmt.id" class="border-b dark:border-gray-800">
                            <td class="px-4 py-2">{{ pmt.created_at }}</td>
                            <td class="px-4 py-2 font-medium">{{ Number(pmt.btr_amount).toLocaleString() }}</td>
                            <td class="px-4 py-2">{{ pmt.btr_coin }}</td>
                            <td class="px-4 py-2">{{ pmt.btr_valorcam }}</td>
                            <td class="px-4 py-2">{{ pmt.btr_reference }}</td>
                            <td class="px-4 py-2">{{ pmt.bank_account?.bank_name ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="isPaid" class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                    <span class="ml-2 font-semibold text-gray-900 dark:text-white">{{ Number(expense.exp_amount).toLocaleString() }} {{ expense.exp_coin }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Pagado:</span>
                    <span class="ml-2 font-semibold text-green-600 dark:text-green-400">{{ Number(expense.exp_paid_amount).toLocaleString() }} {{ expense.exp_coin }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Fecha de pago:</span>
                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ expense.exp_payment_date }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Saldo pendiente:</span>
                    <span class="ml-2 font-semibold text-green-600 dark:text-green-400">0 {{ expense.exp_coin }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
