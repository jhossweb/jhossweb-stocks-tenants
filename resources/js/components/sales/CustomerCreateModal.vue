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
import type { Customer } from '@/types/Sales/Customer';

const emit = defineEmits<{
    created: [customer: Customer];
}>();

const { isOpen, processing, errors, open, close, submit } =
    useInlineCreate<Customer>({
        endpoint: '/api/customers',
        refreshProp: 'customers',
    });

const form = ref({
    clt_name: '',
    clt_cedula: '',
    clt_phone: '',
    clt_email: '',
    clt_address: '',
});

const resetForm = () => {
    form.value = {
        clt_name: '',
        clt_cedula: '',
        clt_phone: '',
        clt_email: '',
        clt_address: '',
    };
};

const handleCreate = async () => {
    const customer = await submit(form.value);

    if (customer) {
        emit('created', customer);
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
                    Nuevo Cliente
                </DialogTitle>
                <DialogDescription class="text-zinc-500">
                    Registre el cliente sin salir de este formulario.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleCreate" class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="qc-clt-name" class="text-zinc-400"
                        >Nombre / Razón Social *</Label
                    >
                    <Input
                        id="qc-clt-name"
                        v-model="form.clt_name"
                        type="text"
                        placeholder="Juan Pérez"
                        class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                        required
                    />
                    <InputError :message="errors.clt_name" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="qc-clt-cedula" class="text-zinc-400"
                            >Cédula / RIF</Label
                        >
                        <Input
                            id="qc-clt-cedula"
                            v-model="form.clt_cedula"
                            type="text"
                            placeholder="V-12345678"
                            class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                        />
                        <InputError :message="errors.clt_cedula" />
                    </div>

                    <div class="space-y-2">
                        <Label for="qc-clt-phone" class="text-zinc-400"
                            >Teléfono *</Label
                        >
                        <Input
                            id="qc-clt-phone"
                            v-model="form.clt_phone"
                            type="tel"
                            placeholder="04141234567"
                            class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                            required
                        />
                        <InputError :message="errors.clt_phone" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="qc-clt-email" class="text-zinc-400"
                        >Email *</Label
                    >
                    <Input
                        id="qc-clt-email"
                        v-model="form.clt_email"
                        type="email"
                        placeholder="cliente@correo.com"
                        class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                        required
                    />
                    <InputError :message="errors.clt_email" />
                </div>

                <div class="space-y-2">
                    <Label for="qc-clt-address" class="text-zinc-400"
                        >Dirección *</Label
                    >
                    <Input
                        id="qc-clt-address"
                        v-model="form.clt_address"
                        type="text"
                        placeholder="Av. Principal, Ciudad"
                        class="border-zinc-800 bg-zinc-900 transition-all focus:ring-indigo-600"
                        required
                    />
                    <InputError :message="errors.clt_address" />
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
                        Crear Cliente
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
