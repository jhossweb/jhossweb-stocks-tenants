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
import { useInlineCreate } from '@/composables/useInlineCreate';

interface Supplier {
    id: number;
    supplier_name: string;
}

const emit = defineEmits<{
    created: [supplier: Supplier];
}>();

const { isOpen, processing, errors, open, close, submit } =
    useInlineCreate<Supplier>({
        endpoint: '/api/suppliers',
        refreshProp: 'suppliers',
    });

const form = ref({
    supplier_name: '',
    supplier_phone: '',
    supplier_email: '',
    supplier_address: '',
});

const resetForm = () => {
    form.value = {
        supplier_name: '',
        supplier_phone: '',
        supplier_email: '',
        supplier_address: '',
    };
};

const handleCreate = async () => {
    const supplier = await submit(form.value);

    if (supplier) {
        emit('created', supplier);
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
                    Nuevo Proveedor
                </DialogTitle>
                <DialogDescription class="text-zinc-500">
                    Registre el proveedor sin salir de este formulario.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleCreate" class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="qc-sup-name" class="text-zinc-400"
                        >Nombre / Razón Social *</Label
                    >
                    <Input
                        id="qc-sup-name"
                        v-model="form.supplier_name"
                        type="text"
                        placeholder="Distribuidora C.A."
                        class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                        required
                    />
                    <InputError :message="errors.supplier_name" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="qc-sup-phone" class="text-zinc-400"
                            >Teléfono *</Label
                        >
                        <Input
                            id="qc-sup-phone"
                            v-model="form.supplier_phone"
                            type="tel"
                            placeholder="04141234567"
                            class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                            required
                        />
                        <InputError :message="errors.supplier_phone" />
                    </div>

                    <div class="space-y-2">
                        <Label for="qc-sup-email" class="text-zinc-400"
                            >Email *</Label
                        >
                        <Input
                            id="qc-sup-email"
                            v-model="form.supplier_email"
                            type="email"
                            placeholder="ventas@proveedor.com"
                            class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                            required
                        />
                        <InputError :message="errors.supplier_email" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="qc-sup-address" class="text-zinc-400"
                        >Dirección *</Label
                    >
                    <Input
                        id="qc-sup-address"
                        v-model="form.supplier_address"
                        type="text"
                        placeholder="Av. Industrial, Ciudad"
                        class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                        required
                    />
                    <InputError :message="errors.supplier_address" />
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
                        Crear Proveedor
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
