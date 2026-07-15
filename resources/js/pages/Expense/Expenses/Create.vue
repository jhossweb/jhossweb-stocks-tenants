<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, DollarSign, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import FlashMessage from '@/components/FlashMessage.vue';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';
import { today } from '@/composables/useDate';
import { usePayment } from '@/composables/usePayment';
import type { ExpenseCategory, BankAccount, LatestRate } from '@/types/Expense';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gastos', href: '/expenses' },
            { title: 'Nuevo', href: '/expenses/create' },
        ],
    },
});

const props = defineProps<{
    categories: ExpenseCategory[];
    coinOptions: string[];
    bankAccounts: BankAccount[];
    latestRate: LatestRate;
}>();

const { resolveRate } = usePayment();

const form = useForm({
    exp_category_id: '',
    exp_description: '',
    exp_amount: '',
    exp_date: today(),
    exp_coin: 'USD',
});

const updateExchangeRate = () => {
    if (!props.latestRate) return;
    form.exp_coin = form.exp_coin;
};

const submit = () => {
    form.post('/expenses', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo Gasto" />
    <FlashMessage />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/expenses" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <DollarSign class="h-8 w-8 text-primary" />
                        Nuevo Gasto
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Registra un nuevo gasto operativo.
                    </p>
                </div>
            </div>
            <button
                @click="submit"
                :disabled="form.processing"
                class="inline-flex items-center px-5 py-2.5 bg-primary text-white rounded-lg font-semibold transition-all disabled:opacity-50 shadow-lg"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                <Save v-else class="w-4 h-4 mr-2" />
                {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </button>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4">
            <p class="text-sm text-red-400 font-bold mb-1">Hay problemas con el envío:</p>
            <ul class="list-disc list-inside text-xs text-red-300">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Categoría</label>
                    <select v-model="form.exp_category_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">Seleccione categoría</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.exc_name }} ({{ cat.exc_type }})</option>
                    </select>
                    <InputError :message="form.errors.exp_category_id" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Monto</label>
                    <Input type="number" step="0.01" min="0.01" v-model="form.exp_amount" class="w-full" placeholder="0.00" />
                    <InputError :message="form.errors.exp_amount" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Moneda</label>
                    <select v-model="form.exp_coin" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="coin in coinOptions" :key="coin" :value="coin">{{ coin }}</option>
                    </select>
                    <InputError :message="form.errors.exp_coin" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Fecha del Gasto</label>
                    <Input type="date" v-model="form.exp_date" class="w-full" />
                    <InputError :message="form.errors.exp_date" />
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Descripción</label>
                    <textarea v-model="form.exp_description" rows="3" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="Describa el gasto"></textarea>
                    <InputError :message="form.errors.exp_description" />
                </div>
            </div>
        </div>
    </div>
</template>
