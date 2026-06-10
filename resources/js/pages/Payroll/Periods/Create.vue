<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, CalendarRange, Calendar, AlertCircle, Loader2 } from 'lucide-vue-next';
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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Períodos de Nómina', href: '/payroll-periods' },
            { title: 'Nuevo', href: '/payroll-periods/create' },
        ],
    },
});

defineProps<{
    statusOptions: string[];
}>();

const form = useForm({
    pp_start_date: '',
    pp_end_date: '',
    pp_payment_date: '',
    pp_status: 'pendiente',
});

const submit = () => {
    form.post('/payroll-periods', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo Período de Nómina" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between bg-zinc-900/50 backdrop-blur-xl p-6 rounded-2xl border border-zinc-800 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <CalendarRange class="w-32 h-32" />
                </div>

                <div class="flex items-center gap-4 relative z-10">
                    <Link href="/payroll-periods" class="p-2.5 bg-zinc-800/50 hover:bg-zinc-800 rounded-xl transition-all text-zinc-400 hover:text-white border border-zinc-700/50">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight flex items-center gap-2">
                            Nuevo Período
                        </h1>
                        <p class="text-xs text-zinc-500 font-medium tracking-wide first-letter:uppercase">
                            Registra un nuevo período de nómina
                        </p>
                    </div>
                </div>

                <Button
                    @click="submit"
                    :disabled="form.processing"
                    size="lg"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold h-12 px-8 rounded-xl shadow-lg shadow-indigo-500/20 active:scale-95 transition-all relative z-10"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                    <Save v-else class="w-4 h-4 mr-2" />
                    {{ form.processing ? 'Guardando...' : 'Guardar' }}
                </Button>
            </div>

            <!-- Errors -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                <div>
                    <h4 class="text-sm font-bold text-red-500">Por favor, corrige los siguientes errores:</h4>
                    <ul class="text-xs text-red-400 list-disc list-inside mt-1 space-y-1">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-zinc-900 rounded-2xl border border-zinc-800 p-6 space-y-6 shadow-sm">
                <div class="flex items-center gap-2 text-indigo-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2">
                    <CalendarRange class="w-4 h-4" /> Información del Período
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Start Date -->
                    <div class="space-y-2">
                        <Label class="text-zinc-400 text-xs font-semibold">Fecha Inicio</Label>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                            <Input
                                type="date"
                                v-model="form.pp_start_date"
                                class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl"
                            />
                        </div>
                        <span v-if="form.errors.pp_start_date" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pp_start_date }}</span>
                    </div>

                    <!-- End Date -->
                    <div class="space-y-2">
                        <Label class="text-zinc-400 text-xs font-semibold">Fecha Fin</Label>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                            <Input
                                type="date"
                                v-model="form.pp_end_date"
                                class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl"
                            />
                        </div>
                        <span v-if="form.errors.pp_end_date" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pp_end_date }}</span>
                    </div>

                    <!-- Payment Date -->
                    <div class="space-y-2">
                        <Label class="text-zinc-400 text-xs font-semibold">Fecha de Pago</Label>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                            <Input
                                type="date"
                                v-model="form.pp_payment_date"
                                class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl"
                            />
                        </div>
                        <span v-if="form.errors.pp_payment_date" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pp_payment_date }}</span>
                    </div>

                    <!-- Status -->
                    <div class="space-y-2">
                        <Label class="text-zinc-400 text-xs font-semibold">Estado</Label>
                        <Select v-model="form.pp_status">
                            <SelectTrigger class="h-11 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                <SelectValue placeholder="Seleccione estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="status in statusOptions" :key="status" :value="status">
                                    <span class="capitalize">{{ status }}</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span v-if="form.errors.pp_status" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.pp_status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
