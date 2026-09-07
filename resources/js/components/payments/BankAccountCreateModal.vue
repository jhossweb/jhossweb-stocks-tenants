<script setup lang="ts">
import { Loader2, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInlineCreate } from '@/composables/useInlineCreate';
import type { BankAccount } from '@/types/Finance/BankAccount';

const props = defineProps<{
    /** Moneda del pago actual: el banco debe coincidir para aparecer en el filtro. */
    currency: string;
}>();

const emit = defineEmits<{
    created: [bank: BankAccount];
}>();

const { isOpen, processing, errors, open, close, submit } =
    useInlineCreate<BankAccount>({
        endpoint: '/api/bank-accounts',
        refreshProp: 'bankAccounts',
    });

const accountTypes = ['CORRIENTE', 'AHORRO'];

const form = ref({
    bank_name: '',
    bank_account_number: '',
    bank_type: 'CORRIENTE',
});

const resetForm = () => {
    form.value = {
        bank_name: '',
        bank_account_number: '',
        bank_type: 'CORRIENTE',
    };
};

const handleCreate = async () => {
    const bank = await submit({
        ...form.value,
        bank_currency: props.currency,
        bank_status: true,
    });

    if (bank) {
        emit('created', bank);
        resetForm();
    }
};

defineExpose({ open });
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="border-zinc-800 bg-zinc-950 text-zinc-100 shadow-2xl sm:max-w-[440px]"
        >
            <DialogHeader>
                <DialogTitle
                    class="flex items-center gap-2 text-lg font-bold text-zinc-50"
                >
                    <Plus class="h-5 w-5 text-indigo-500" />
                    Nueva Cuenta Bancaria
                </DialogTitle>
                <DialogDescription class="text-zinc-500">
                    Cree la cuenta sin salir de este formulario. Se creará en
                    moneda {{ currency }}.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleCreate" class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="qc-bank-name" class="text-zinc-400"
                        >Nombre del Banco *</Label
                    >
                    <Input
                        id="qc-bank-name"
                        v-model="form.bank_name"
                        type="text"
                        placeholder="Banco Nacional"
                        class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                        required
                    />
                    <InputError :message="errors.bank_name" />
                </div>

                <div class="space-y-2">
                    <Label for="qc-bank-number" class="text-zinc-400"
                        >Número de Cuenta *</Label
                    >
                    <Input
                        id="qc-bank-number"
                        v-model="form.bank_account_number"
                        type="text"
                        placeholder="01234567890123456789"
                        maxlength="20"
                        class="border-zinc-800 bg-zinc-900 font-mono transition-all focus:ring-indigo-600"
                        required
                    />
                    <InputError :message="errors.bank_account_number" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="qc-bank-type" class="text-zinc-400"
                            >Tipo *</Label
                        >
                        <Select v-model="form.bank_type">
                            <SelectTrigger
                                id="qc-bank-type"
                                class="w-full border-zinc-800 bg-zinc-900 focus:ring-indigo-600"
                            >
                                <SelectValue placeholder="Tipo" />
                            </SelectTrigger>
                            <SelectContent class="border-zinc-800 bg-zinc-950">
                                <SelectItem
                                    v-for="type in accountTypes"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ type }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.bank_type" />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-zinc-400">Moneda</Label>
                        <Input
                            :model-value="currency"
                            type="text"
                            disabled
                            class="border-zinc-800 bg-zinc-900/60 text-zinc-500"
                        />
                    </div>
                </div>

                <InputError :message="errors.general" />

                <DialogFooter class="pt-2">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="close"
                        class="text-zinc-500 hover:text-white"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing"
                        class="bg-indigo-600 text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500"
                    >
                        <Loader2
                            v-if="processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Plus v-else class="mr-2 h-4 w-4" />
                        Crear Cuenta
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
