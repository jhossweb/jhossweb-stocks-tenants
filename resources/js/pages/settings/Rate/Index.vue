<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { 
    Plus, 
    Pencil, 
    Trash2, 
    TrendingUp, 
    Calendar, 
    ArrowLeft,
    Loader2,
    Save
} from 'lucide-vue-next';
import RateController from '@/actions/App/Http/Controllers/Settings/RateController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Rate {
    id: number;
    rate_date: string;
    rate_usd: string | number;
    rate_cop: string | number;
    rate_eur: string | number;
    created_at: string;
}

interface Props {
    rates: {
        data: Rate[];
        links: any[];
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Configuración de Tasas',
                href: '/settings/rates',
            },
        ],
    },
});

// --- MODAL STATE ---
const isModalOpen = ref(false);
const isEditing = ref(false);
const currentRate = ref<Rate | null>(null);

const openCreateModal = () => {
    isEditing.value = false;
    currentRate.value = null;
    isModalOpen.value = true;
};

const openEditModal = (rate: Rate) => {
    isEditing.value = true;
    currentRate.value = rate;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const deleteRate = (id: number) => {
    if (confirm('¿Estás seguro de eliminar esta tasa?')) {
        // Usando el helper de acción para destruir
        const { url, method } = RateController.destroy({ rate: id });
        form.submit(method, url);
    }
};

// --- HELPERS ---
const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
};

// Inicializamos el formulario con useForm de Inertia

const form = useForm({
    rate_date: new Date().toISOString().split('T')[0],
    rate_usd: 1,
    rate_cop: 0,
    rate_eur: 0,
});

// Cuando el modal se abre para editar, cargamos los datos en el form
watch(() => currentRate.value, (newRate: Rate | null) => {
    if (newRate) {
        form.rate_date = newRate.rate_date;
        form.rate_usd = Number(newRate.rate_usd);
        form.rate_cop = Number(newRate.rate_cop);
        form.rate_eur = Number(newRate.rate_eur);
    } else {
        form.reset();
    }
    form.clearErrors();
});

const submit = () => {
    const action = isEditing.value && currentRate.value
        ? RateController.update({ rate: currentRate.value.id })
        : RateController.store();
    
    form.submit(action.method, action.url, {
        onSuccess: () => closeModal(),
    });
};

</script>

<template>
    <Head title="Gestión de Tasas" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Histórico de Tasas de Cambio"
                description="Administra los valores de conversión de divisas para el sistema."
            />
            <Button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/20">
                <Plus class="w-4 h-4 mr-2" /> Nueva Tasa
            </Button>
        </div>

        <Card class="bg-zinc-950 border-zinc-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-900/50 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                        <tr>
                            <th class="px-6 py-4 text-left">Fecha</th>
                            <th class="px-6 py-4 text-right text-indigo-400">USD ($)</th>
                            <th class="px-6 py-4 text-right">COP ($)</th>
                            <th class="px-6 py-4 text-right">EUR (€)</th>
                            <th class="px-6 py-4 text-center w-24">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/50">
                        <tr v-for="rate in rates.data" :key="rate.id" class="hover:bg-zinc-900/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-zinc-500" />
                                    <span class="font-medium text-zinc-200">{{ formatDate(rate.rate_date) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-indigo-400">
                                {{ Number(rate.rate_usd).toFixed(4) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-zinc-300">
                                {{ Number(rate.rate_cop).toLocaleString('es-CO') }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-zinc-300">
                                {{ Number(rate.rate_eur).toFixed(4) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button 
                                        @click="openEditModal(rate)" 
                                        class="p-1.5 text-zinc-500 hover:text-indigo-400 hover:bg-indigo-400/10 rounded-md transition-all"
                                        title="Editar"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="deleteRate(rate.id)" 
                                        class="p-1.5 text-zinc-500 hover:text-red-500 hover:bg-red-500/10 rounded-md transition-all"
                                        title="Eliminar"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="rates.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500 italic">
                                No se han registrado tasas aún.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación Simple si fuera necesario, o scroll infinito -->
            <div v-if="rates.links && rates.links.length > 3" class="p-4 border-t border-zinc-800 bg-zinc-900/20 flex justify-center">
                <!-- Aquí iría un componente de paginación si se prefiere -->
            </div>
        </Card>

        <!-- --- MODAL PARA CREAR / EDITAR --- -->
        <Dialog v-model:open="isModalOpen">
            <DialogContent class="sm:max-w-[425px] bg-zinc-950 border-zinc-800 text-zinc-100 shadow-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-xl font-bold">
                        <TrendingUp class="w-5 h-5 text-indigo-500" />
                        {{ isEditing ? 'Actualizar Tasa' : 'Nueva Tasa de Cambio' }}
                    </DialogTitle>
                    <DialogDescription class="text-zinc-500">
                        Ingresa los valores de referencia para la fecha seleccionada.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-5 py-4">
                    <div class="space-y-2">
                        <Label for="rate_date" class="text-zinc-400">Fecha de la Tasa</Label>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                            <Input 
                                id="rate_date" 
                                type="date" 
                                v-model="form.rate_date" 
                                class="pl-10 bg-zinc-900 border-zinc-800 focus:ring-indigo-600 transition-all"
                                :disabled="isEditing"
                                required
                            />
                        </div>
                        <InputError :message="form.errors.rate_date" />
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div class="space-y-2">
                            <Label for="rate_usd" class="text-zinc-400">Tasa USD ($)</Label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-indigo-500 font-bold">$</span>
                                <Input 
                                    id="rate_usd" 
                                    type="number" 
                                    step="0.0001" 
                                    v-model="form.rate_usd" 
                                    class="pl-8 bg-zinc-900 border-zinc-800 focus:ring-indigo-600 font-mono font-bold text-indigo-400"
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.rate_usd" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="rate_cop" class="text-zinc-400">Tasa COP ($)</Label>
                                <Input 
                                    id="rate_cop" 
                                    type="number" 
                                    step="0.01" 
                                    v-model="form.rate_cop" 
                                    class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 font-mono"
                                    required
                                />
                                <InputError :message="form.errors.rate_cop" />
                            </div>

                            <div class="space-y-2">
                                <Label for="rate_eur" class="text-zinc-400">Tasa EUR (€)</Label>
                                <Input 
                                    id="rate_eur" 
                                    type="number" 
                                    step="0.0001" 
                                    v-model="form.rate_eur" 
                                    class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 font-mono"
                                    required
                                />
                                <InputError :message="form.errors.rate_eur" />
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button 
                            type="button" 
                            variant="ghost" 
                            @click="closeModal" 
                            class="text-zinc-500 hover:text-white"
                        >
                            Cancelar
                        </Button>
                        <Button 
                            type="submit" 
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <Save v-else class="w-4 h-4 mr-2" />
                            {{ isEditing ? 'Guardar Cambios' : 'Registrar Tasa' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
/* Calendario en dark mode */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
