<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { 
    Building2, 
    PlusCircle, 
    Pencil, 
    Trash2, 
    MoreHorizontal,
    CreditCard,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
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
            { title: 'Finanzas', href: '#' },
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

const props = defineProps<{
    bankAccounts: BankAccount[];
    types: string[];
    currencies: string[];
}>();

const isDialogOpen = ref(false);
const editingAccount = ref<BankAccount | null>(null);

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

const deleteAccount = (id: number) => {
    if (confirm('¿Estás seguro de eliminar esta cuenta bancaria?')) {
        form.delete(bankAccountRoutes.destroy.url(id));
    }
};

const getStatusBadgeClass = (status: boolean) => {
    return status 
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
};
</script>

<template>
    <Head title="Cuentas Bancarias" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <Building2 class="h-8 w-8 text-primary" />
                    Cuentas Bancarias
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Administra tus bancos y cuentas para transacciones financieras.
                </p>
            </div>
            
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button @click="openCreateModal" class="shadow-md hover:shadow-lg transition-all">
                        <PlusCircle class="mr-2 h-4 w-4" />
                        Añadir Banco
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>{{ editingAccount ? 'Editar Banco' : 'Añadir Nuevo Banco' }}</DialogTitle>
                        <DialogDescription>
                            Ingresa los detalles de la cuenta bancaria. Haz clic en guardar cuando termines.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="bank_name" class="text-right">Nombre Banco</Label>
                            <div class="col-span-3">
                                <Input id="bank_name" v-model="form.bank_name" placeholder="Ej: Banesco, Banco de Venezuela" :class="{ 'border-red-500': form.errors.bank_name }" />
                                <p v-if="form.errors.bank_name" class="text-xs text-red-500 mt-1">{{ form.errors.bank_name }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="bank_account_number" class="text-right">Nro. Cuenta</Label>
                            <div class="col-span-3">
                                <Input id="bank_account_number" v-model="form.bank_account_number" placeholder="0102XXXXXXXXXXXX" :class="{ 'border-red-500': form.errors.bank_account_number }" />
                                <p v-if="form.errors.bank_account_number" class="text-xs text-red-500 mt-1">{{ form.errors.bank_account_number }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Tipo</Label>
                            <div class="col-span-3">
                                <Select v-model="form.bank_type">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.bank_type }">
                                        <SelectValue placeholder="Selecciona tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in types" :key="type" :value="type">
                                            {{ type }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.bank_type" class="text-xs text-red-500 mt-1">{{ form.errors.bank_type }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Moneda</Label>
                            <div class="col-span-3">
                                <Select v-model="form.bank_currency">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.bank_currency }">
                                        <SelectValue placeholder="Selecciona moneda" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="currency in currencies" :key="currency" :value="currency">
                                            {{ currency }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.bank_currency" class="text-xs text-red-500 mt-1">{{ form.errors.bank_currency }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="bank_description" class="text-right">Descripción</Label>
                            <div class="col-span-3">
                                <Input id="bank_description" v-model="form.bank_description" placeholder="Opcional" :class="{ 'border-red-500': form.errors.bank_description }" />
                                <p v-if="form.errors.bank_description" class="text-xs text-red-500 mt-1">{{ form.errors.bank_description }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Estado</Label>
                            <div class="flex items-center space-x-2 col-span-3">
                                <input type="checkbox" id="bank_status" v-model="form.bank_status" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" />
                                <Label for="bank_status">Activa</Label>
                            </div>
                        </div>
                    </div>
                    
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isDialogOpen = false">Cancelar</Button>
                        <Button type="submit" @click="saveAccount" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-if="bankAccounts.length === 0" class="col-span-full py-20 bg-white dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-700 rounded-2xl flex flex-col items-center justify-center text-gray-500">
                <Building2 class="h-16 w-16 mb-4 opacity-20" />
                <p class="text-lg font-medium">No hay bancos registrados</p>
                <p class="text-sm">Comienza añadiendo tu primera cuenta bancaria.</p>
            </div>
            
            <div v-for="account in bankAccounts" :key="account.id" 
                class="group relative bg-white dark:bg-gray-900 border border-sidebar-border rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <!-- Decorative element -->
                <div class="absolute top-0 right-0 -m-4 h-24 w-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
                
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <CreditCard class="h-6 w-6 text-primary" />
                    </div>
                    
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-full">
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-[160px]">
                            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="openEditModal(account)">
                                <Pencil class="mr-2 h-4 w-4" />
                                Editar
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteAccount(account.id)" class="text-red-600 focus:text-red-600">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Eliminar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ account.bank_name }}</h3>
                        <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-1">{{ account.bank_account_number }}</p>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        <Badge variant="outline" class="bg-gray-50 dark:bg-gray-800">{{ account.bank_type }}</Badge>
                        <Badge :class="getStatusBadgeClass(account.bank_status)">
                            {{ account.bank_status ? 'Activa' : 'Inactiva' }}
                        </Badge>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-end">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Saldo Actual
                        </div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">
                            {{ account.bank_balance }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
