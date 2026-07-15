<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PlusCircle,
    DollarSign,
    FileText,
    Filter,
    Eye,
    Banknote,
    Loader2,
    Trash2,
    XCircle,
    Calendar,
    Hash,
    Landmark,
    Repeat,
} from 'lucide-vue-next';
import { watch } from 'vue';
import FlashMessage from '@/components/FlashMessage.vue';
import PaymentForm from '@/components/payments/PaymentForm.vue';
import PayModal from '@/components/payments/PayModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { useExpenses } from '@/composables/Expense/useExpenses';
import { formatDate } from '@/composables/useDate';
import type { Expense, ExpenseCategory, BankAccount, LatestRate, PaginatedData } from '@/types/Expense';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gastos', href: '/expenses' },
        ],
    },
});

const props = defineProps<{
    expenses: PaginatedData<Expense>;
    categories: ExpenseCategory[];
    statusOptions: string[];
    coinOptions: string[];
    bankAccounts: BankAccount[];
    latestRate: LatestRate;
    rates: LatestRate[];
    methods: string[];
}>();

const {
    filterCategory,
    filterStatus,
    showPayModal,
    payingExpense,
    showDeleteConfirm,
    deletingExpense,
    showPaymentsModal,
    viewingExpensePayments,
    payForm,
    estimatedDiferencial,
    openPayModal,
    closePayModal,
    submitPayment,
    confirmDelete,
    cancelDelete,
    openViewPayments,
    closeViewPayments,
} = useExpenses();

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (filterCategory.value) {
        params.category = String(filterCategory.value);
    }

    if (filterStatus.value) {
        params.status = filterStatus.value;
    }

    router.get('/expenses', params, { preserveState: true, preserveScroll: true });
};

watch(filterCategory, applyFilters);
watch(filterStatus, applyFilters);

const handleDelete = () => {
    if (!deletingExpense.value) {
        return;
    }

    router.delete(`/expenses/${deletingExpense.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            cancelDelete();
        },
    });
};
</script>

<template>
    <Head title="Listado de Gastos" />
    <FlashMessage />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <DollarSign class="h-8 w-8 text-primary" />
                    Gastos
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Registra y administra los gastos operativos.
                </p>
            </div>
            <Link href="/expenses/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Gasto
                </Button>
            </Link>
        </div>

        <div class="flex gap-4 items-center">
            <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-gray-400" />
                <select v-model="filterCategory" class="rounded-md border border-input bg-background px-3 py-1.5 text-sm">
                    <option value="">Todas las categorías</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.exc_name }}</option>
                </select>
            </div>
            <select v-model="filterStatus" class="rounded-md border border-input bg-background px-3 py-1.5 text-sm">
                <option value="">Todos los estados</option>
                <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Categoría</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Descripción</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Monto</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Pagado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Saldo Pendiente</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Moneda</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Fecha</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="expenses.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay gastos registrados</span>
                                </div>
                            </td>
                        </tr>
                        <template v-for="expense in expenses.data" :key="expense.id">
                            <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ expense.category?.exc_name ?? 'Sin categoría' }}
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate">
                                    {{ expense.exp_description }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ Number(expense.exp_amount).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">
                                    {{ Number(expense.exp_paid_amount).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 font-semibold" :class="Number(expense.exp_remaining_balance) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                                    {{ Number(expense.exp_remaining_balance).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ expense.exp_coin }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatDate(expense.exp_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': expense.exp_status === 'pendiente',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': expense.exp_status === 'parcial',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': expense.exp_status === 'pagado',
                                    }">
                                        {{ expense.exp_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link v-if="!expense.exp_status || expense.exp_status !== 'pagado'" :href="`/expenses/${expense.id}/edit`">
                                            <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                                Editar
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="expense.exp_status !== 'pagado' && Number(expense.exp_remaining_balance) > 0"
                                            variant="outline"
                                            size="sm"
                                            @click="openPayModal(expense, methods, latestRate)"
                                            class="text-green-600 border-green-300 hover:bg-green-50 dark:text-green-400 dark:border-green-700 dark:hover:bg-green-900/20"
                                        >
                                            <Banknote class="w-3.5 h-3.5 mr-1" />
                                            Pagar
                                        </Button>
                                        <Button
                                            v-if="expense.exp_status !== 'pagado'"
                                            variant="ghost"
                                            size="sm"
                                            @click="confirmDelete(expense)"
                                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                        <Button
                                            v-if="expense.payments?.length"
                                            variant="ghost"
                                            size="sm"
                                            @click="openViewPayments(expense)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            <Eye class="w-3.5 h-3.5 mr-1" />
                                            Pagos
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                        </template>
                    </tbody>
                </table>
            </div>

            <div v-if="expenses.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in expenses.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <PayModal v-model:open="showPayModal" title="Registrar Pago" @close="closePayModal">
        <template #summary>
            <div v-if="payingExpense" class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Gasto:</span>
                    <span class="font-medium text-zinc-100 text-right">{{ payingExpense.exp_description }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Monto total:</span>
                    <span class="font-medium text-zinc-100">{{ Number(payingExpense.exp_amount).toLocaleString() }} {{ payingExpense.exp_coin }}</span>
                </div>
                <div class="flex justify-between text-sm pt-1 border-t border-zinc-800">
                    <span class="text-zinc-400">Saldo pendiente:</span>
                    <span class="font-semibold text-amber-400">{{ Number(payingExpense.exp_remaining_balance).toLocaleString() }} {{ payingExpense.exp_coin }}</span>
                </div>
                <div v-if="estimatedDiferencial !== 0 && payForm.payment_currency !== payingExpense.exp_coin" class="flex justify-between text-sm pt-1 border-t border-zinc-800">
                    <span class="text-zinc-400">Diferencial estimado:</span>
                    <span class="font-semibold" :class="estimatedDiferencial > 0 ? 'text-green-400' : 'text-orange-400'">
                        {{ estimatedDiferencial > 0 ? '+' : '' }}${{ Math.abs(estimatedDiferencial).toFixed(2) }} USD
                        <span class="text-[10px] font-normal opacity-60">({{ estimatedDiferencial > 0 ? 'Ganancia' : 'Pérdida' }})</span>
                    </span>
                </div>
            </div>
        </template>

        <PaymentForm
            :bank-accounts="bankAccounts"
            :latest-rate="latestRate"
            :rates="rates"
            :currency="payingExpense?.exp_coin ?? 'USD'"
            :remaining-balance="Number(payingExpense?.exp_remaining_balance ?? 0)"
            :form="payForm"
            :errors="payForm.errors"
            :processing="payForm.processing"
            show-rate
            show-date
            show-method
            :currencies="coinOptions"
            :methods="methods"
        />

        <template #actions>
            <Button
                @click="submitPayment(payingExpense?.id ?? 0)"
                :disabled="payForm.processing"
                class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold"
            >
                <Loader2 v-if="payForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ payForm.processing ? 'Procesando...' : 'Registrar Pago' }}
            </Button>
        </template>
    </PayModal>

    <Dialog :open="showPaymentsModal" @update:open="closeViewPayments">
        <DialogContent class="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Historial de Pagos</DialogTitle>
                <DialogDescription>
                    Pagos registrados para: {{ viewingExpensePayments?.exp_description }}
                </DialogDescription>
            </DialogHeader>

            <div class="mt-4">
                <div v-if="!viewingExpensePayments?.payments || viewingExpensePayments.payments.length === 0" class="text-center py-8 text-gray-500">
                    <FileText class="mx-auto h-8 w-8 text-gray-300 mb-2" />
                    No hay pagos registrados para este gasto.
                </div>

                <div v-else class="space-y-4">
                    <div v-for="pmt in viewingExpensePayments.payments" :key="pmt.id" class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <h4 class="font-semibold text-lg text-gray-900 dark:text-white">
                                    {{ Number(pmt.btr_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    <span class="text-xs font-normal text-gray-400 ml-1">{{ pmt.btr_coin }}</span>
                                </h4>

                                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 mt-3 text-sm">
                                    <div class="flex items-center gap-2 text-gray-500">
                                        <Calendar class="h-4 w-4 shrink-0" />
                                        {{ formatDate(pmt.created_at) }}
                                    </div>

                                    <div class="flex items-center gap-2 text-gray-500">
                                        <Repeat class="h-4 w-4 shrink-0" />
                                        Tasa: <span class="font-mono font-medium text-gray-700 dark:text-gray-300">{{ Number(pmt.btr_valorcam).toFixed(4) }}</span>
                                    </div>

                                    <div v-if="pmt.bank_account" class="flex items-center gap-2 text-gray-500">
                                        <Landmark class="h-4 w-4 shrink-0" />
                                        {{ pmt.bank_account.bank_name }}
                                    </div>

                                    <div v-if="pmt.btr_reference" class="flex items-center gap-2 text-gray-500">
                                        <Hash class="h-4 w-4 shrink-0" />
                                        Ref: {{ pmt.btr_reference }}
                                    </div>

                                    <div v-if="pmt.btr_method" class="flex items-center gap-2 text-gray-500 col-span-2">
                                        <Banknote class="h-4 w-4 shrink-0" />
                                        {{ pmt.btr_method }}
                                    </div>
                                </div>

                                <div v-if="pmt.btr_description" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Nota: {{ pmt.btr_description }}
                                </div>

                                <div v-if="pmt.btr_diferencial && Number(pmt.btr_diferencial) !== 0" class="mt-2 text-xs font-semibold" :class="Number(pmt.btr_diferencial) > 0 ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'">
                                    Diferencial: {{ Number(pmt.btr_diferencial) > 0 ? '+' : '' }}${{ Math.abs(Number(pmt.btr_diferencial)).toFixed(2) }} USD
                                    <span class="font-normal text-gray-400">({{ Number(pmt.btr_diferencial) > 0 ? 'Ganancia' : 'Pérdida' }})</span>
                                </div>
                            </div>

                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-red-500 hover:text-red-700 dark:text-red-400 shrink-0 ml-4"
                                @click="router.post(`/expenses/${viewingExpensePayments.id}/cancel-payment`, { bank_transaction_id: pmt.id }, { preserveScroll: true, onSuccess: () => closeViewPayments() })"
                            >
                                <XCircle class="w-3.5 h-3.5 mr-1" />
                                Cancelar
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                <Button variant="outline" @click="closeViewPayments">Cerrar</Button>
            </div>
        </DialogContent>
    </Dialog>

    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showDeleteConfirm"
                class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/80 backdrop-blur-sm p-4"
                @click.self="cancelDelete"
            >
                <div class="bg-zinc-900 rounded-2xl border border-zinc-800 shadow-2xl w-full max-w-md p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-500/10 rounded-lg">
                            <Trash2 class="w-5 h-5 text-red-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-100">Eliminar Gasto</h3>
                    </div>
                    <p class="text-sm text-zinc-400 mb-6">
                        ¿Estás seguro de que deseas eliminar este gasto? Esta acción no se puede deshacer.
                    </p>
                    <div class="flex justify-end gap-3">
                        <Button variant="outline" @click="cancelDelete">Cancelar</Button>
                        <Button
                            @click="handleDelete"
                            class="bg-red-600 hover:bg-red-500 text-white font-bold"
                        >
                            <Loader2 class="w-4 h-4 mr-2 animate-spin" />
                            Eliminar
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
