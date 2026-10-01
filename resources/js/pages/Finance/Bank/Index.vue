<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CreditCard,
    MoreHorizontal,
    Pencil,
    PlusCircle,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import bankAccountRoutes from '@/routes/bank-accounts';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Finanzas', href: '/finanzas' },
            { title: 'Bancos', href: '/bank-accounts' },
        ],
    },
});

interface BankAccount {
    id: number;
    bank_name: string;
    bank_account_number: string;
    bank_type: string;
    bank_currency: string;
    bank_status: boolean;
    bank_description: string | null;
    bank_balance: string;
    formatted_balance: string;
}

const { bankAccounts, types, currencies } = defineProps<{
    bankAccounts: BankAccount[];
    types: string[];
    currencies: string[];
}>();

const isDialogOpen = ref(false);
const editingAccount = ref<BankAccount | null>(null);
const showDeleteConfirm = ref(false);
const deletingAccount = ref<BankAccount | null>(null);

const form = useForm({
    bank_name: '',
    bank_account_number: '',
    bank_type: '',
    bank_currency: '',
    bank_description: '',
    bank_status: true,
});

const openCreateModal = () => {
    editingAccount.value = null;
    form.reset();
    isDialogOpen.value = true;
};

const openEditModal = (account: BankAccount) => {
    editingAccount.value = account;
    form.bank_name = account.bank_name;
    form.bank_account_number = account.bank_account_number;
    form.bank_type = account.bank_type;
    form.bank_currency = account.bank_currency;
    form.bank_description = account.bank_description || '';
    form.bank_status = account.bank_status;
    isDialogOpen.value = true;
};

const saveAccount = () => {
    if (editingAccount.value) {
        form.put(bankAccountRoutes.update.url(editingAccount.value.id), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(bankAccountRoutes.store.url(), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    }
};

const confirmDelete = (account: BankAccount) => {
    deletingAccount.value = account;
    showDeleteConfirm.value = true;
};

const executeDelete = () => {
    if (!deletingAccount.value) {
        return;
    }

    const id = deletingAccount.value.id;
    deletingAccount.value = null;
    showDeleteConfirm.value = false;

    form.delete(bankAccountRoutes.destroy.url(id));
};

const cancelDelete = () => {
    deletingAccount.value = null;
    showDeleteConfirm.value = false;
};
</script>

<template>
    <Head title="Cuentas Bancarias" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/dashboard/finanzas"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Cuentas Bancarias</h1>
                        <p class="text-xs text-zinc-500">
                            Administra tus cuentas bancarias para transacciones
                            financieras.
                        </p>
                    </div>
                </div>
                <Button
                    @click="openCreateModal"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <PlusCircle class="mr-2 h-4 w-4" /> Añadir Banco
                </Button>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-4 text-left">Banco</th>
                                <th class="px-4 py-4 text-left">Nro. Cuenta</th>
                                <th class="px-4 py-4 text-left">Tipo</th>
                                <th class="px-4 py-4 text-center">Moneda</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="w-12 px-4 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="bankAccounts.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <Building2
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay cuentas bancarias registradas</p>
                                </td>
                            </tr>
                            <tr
                                v-for="account in bankAccounts"
                                :key="account.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-lg bg-zinc-800 p-2">
                                            <CreditCard
                                                class="h-4 w-4 text-zinc-400"
                                            />
                                        </div>
                                        <span class="font-medium">{{
                                            account.bank_name
                                        }}</span>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-400"
                                >
                                    {{ account.bank_account_number }}
                                </td>
                                <td class="px-4 py-4 text-zinc-400">
                                    {{ account.bank_type }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="rounded bg-zinc-800 px-2 py-1 text-xs text-zinc-300"
                                    >
                                        {{ account.bank_currency }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-zinc-200"
                                >
                                    {{ account.formatted_balance }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium uppercase"
                                        :class="
                                            account.bank_status
                                                ? 'bg-green-900/30 text-green-400'
                                                : 'bg-red-900/30 text-red-400'
                                        "
                                    >
                                        {{
                                            account.bank_status
                                                ? 'Activa'
                                                : 'Inactiva'
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 text-zinc-400 hover:text-white"
                                            >
                                                <MoreHorizontal
                                                    class="h-4 w-4"
                                                />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="border border-zinc-800 bg-zinc-900 text-zinc-100"
                                        >
                                            <DropdownMenuLabel
                                                >Acciones</DropdownMenuLabel
                                            >
                                            <DropdownMenuSeparator
                                                class="bg-zinc-800"
                                            />
                                            <DropdownMenuItem
                                                @click="openEditModal(account)"
                                                class="cursor-pointer hover:bg-zinc-800"
                                            >
                                                <Pencil class="mr-2 h-4 w-4" />
                                                Editar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="confirmDelete(account)"
                                                class="cursor-pointer text-red-400 hover:bg-zinc-800 hover:text-red-300"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Eliminar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog v-model:open="isDialogOpen">
        <DialogContent
            class="border border-zinc-800 bg-zinc-900 text-zinc-100 sm:max-w-[500px]"
        >
            <DialogHeader>
                <DialogTitle class="text-zinc-100">
                    {{ editingAccount ? 'Editar Banco' : 'Añadir Nuevo Banco' }}
                </DialogTitle>
                <DialogDescription class="text-zinc-400">
                    Ingresa los detalles de la cuenta bancaria. Haz clic en
                    guardar cuando termines.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <div class="space-y-2">
                    <Label for="bank_name" class="text-zinc-300"
                        >Nombre Banco</Label
                    >
                    <Input
                        id="bank_name"
                        v-model="form.bank_name"
                        placeholder="Ej: Banesco, Banco de Venezuela"
                        class="border-zinc-700 bg-zinc-800/50 text-zinc-100 placeholder:text-zinc-500"
                        :class="{ 'border-red-500': form.errors.bank_name }"
                    />
                    <p
                        v-if="form.errors.bank_name"
                        class="text-xs text-red-400"
                    >
                        {{ form.errors.bank_name }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="bank_account_number" class="text-zinc-300"
                        >Nro. Cuenta</Label
                    >
                    <Input
                        id="bank_account_number"
                        v-model="form.bank_account_number"
                        placeholder="0102XXXXXXXXXXXX"
                        class="border-zinc-700 bg-zinc-800/50 text-zinc-100 placeholder:text-zinc-500"
                        :class="{
                            'border-red-500': form.errors.bank_account_number,
                        }"
                    />
                    <p
                        v-if="form.errors.bank_account_number"
                        class="text-xs text-red-400"
                    >
                        {{ form.errors.bank_account_number }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label class="text-zinc-300">Tipo</Label>
                    <Select v-model="form.bank_type">
                        <SelectTrigger
                            class="border-zinc-700 bg-zinc-800/50 text-zinc-100"
                        >
                            <SelectValue placeholder="Selecciona tipo" />
                        </SelectTrigger>
                        <SelectContent
                            class="border-zinc-700 bg-zinc-900 text-zinc-100"
                        >
                            <SelectItem
                                v-for="type in types"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.bank_type"
                        class="text-xs text-red-400"
                    >
                        {{ form.errors.bank_type }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label class="text-zinc-300">Moneda</Label>
                    <Select v-model="form.bank_currency">
                        <SelectTrigger
                            class="border-zinc-700 bg-zinc-800/50 text-zinc-100"
                        >
                            <SelectValue placeholder="Selecciona moneda" />
                        </SelectTrigger>
                        <SelectContent
                            class="border-zinc-700 bg-zinc-900 text-zinc-100"
                        >
                            <SelectItem
                                v-for="currency in currencies"
                                :key="currency"
                                :value="currency"
                            >
                                {{ currency }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.bank_currency"
                        class="text-xs text-red-400"
                    >
                        {{ form.errors.bank_currency }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="bank_description" class="text-zinc-300"
                        >Descripción</Label
                    >
                    <Input
                        id="bank_description"
                        v-model="form.bank_description"
                        placeholder="Opcional"
                        class="border-zinc-700 bg-zinc-800/50 text-zinc-100 placeholder:text-zinc-500"
                        :class="{
                            'border-red-500': form.errors.bank_description,
                        }"
                    />
                    <p
                        v-if="form.errors.bank_description"
                        class="text-xs text-red-400"
                    >
                        {{ form.errors.bank_description }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        id="bank_status"
                        v-model="form.bank_status"
                        type="checkbox"
                        class="h-4 w-4 rounded border-zinc-600 bg-zinc-800 text-indigo-600 focus:ring-indigo-500"
                    />
                    <Label for="bank_status" class="text-zinc-300"
                        >Activa</Label
                    >
                </div>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="isDialogOpen = false"
                    class="border-zinc-700 text-zinc-300 hover:bg-zinc-800"
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-indigo-600 text-white hover:bg-indigo-500"
                    @click="saveAccount"
                >
                    {{ form.processing ? 'Guardando...' : 'Guardar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteConfirm">
        <DialogContent
            class="border border-zinc-800 bg-zinc-900 text-zinc-100 sm:max-w-[400px]"
        >
            <DialogHeader>
                <DialogTitle class="text-zinc-100"
                    >Eliminar Cuenta Bancaria</DialogTitle
                >
                <DialogDescription class="text-zinc-400">
                    ¿Estás seguro de eliminar la cuenta
                    <span class="font-medium text-zinc-200">{{
                        deletingAccount?.bank_name
                    }}</span
                    >? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="cancelDelete"
                    class="border-zinc-700 text-zinc-300 hover:bg-zinc-800"
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    class="bg-red-600 text-white hover:bg-red-500"
                    :disabled="form.processing"
                    @click="executeDelete"
                >
                    {{ form.processing ? 'Eliminando...' : 'Eliminar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
