<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    CalendarRange,
    Calendar,
    AlertCircle,
    Loader2,
} from 'lucide-vue-next';
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
            { title: 'Editar', href: '' },
        ],
    },
});

const props = defineProps<{
    period: any;
    statusOptions: string[];
}>();

const form = useForm({
    pp_start_date: props.period.pp_start_date || '',
    pp_end_date: props.period.pp_end_date || '',
    pp_payment_date: props.period.pp_payment_date || '',
    pp_status: props.period.pp_status || 'pendiente',
});

const submit = () => {
    form.put(`/payroll-periods/${props.period.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Período de Nómina" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div
                class="relative flex items-center justify-between overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/50 p-6 shadow-2xl backdrop-blur-xl"
            >
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <CalendarRange class="h-32 w-32" />
                </div>

                <div class="relative z-10 flex items-center gap-4">
                    <Link
                        href="/payroll-periods"
                        class="rounded-xl border border-zinc-700/50 bg-zinc-800/50 p-2.5 text-zinc-400 transition-all hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1
                            class="flex items-center gap-2 text-2xl font-black tracking-tight"
                        >
                            Editar Período
                        </h1>
                        <p
                            class="text-xs font-medium tracking-wide text-zinc-500 first-letter:uppercase"
                        >
                            Modifica los datos del período de nómina
                        </p>
                    </div>
                </div>

                <Button
                    @click="submit"
                    :disabled="form.processing"
                    size="lg"
                    class="relative z-10 h-12 rounded-xl bg-indigo-600 px-8 font-bold text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-500 active:scale-95"
                >
                    <Loader2
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Save v-else class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </Button>
            </div>

            <!-- Errors -->
            <div
                v-if="Object.keys(form.errors).length > 0"
                class="flex items-start gap-3 rounded-2xl border border-red-500/20 bg-red-500/10 p-4"
            >
                <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
                <div>
                    <h4 class="text-sm font-bold text-red-500">
                        Por favor, corrige los siguientes errores:
                    </h4>
                    <ul
                        class="mt-1 list-inside list-disc space-y-1 text-xs text-red-400"
                    >
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Form -->
            <div
                class="space-y-6 rounded-2xl border border-zinc-800 bg-zinc-900 p-6 shadow-sm"
            >
                <div
                    class="mb-2 flex items-center gap-2 text-[10px] font-black tracking-[0.2em] text-indigo-400 uppercase"
                >
                    <CalendarRange class="h-4 w-4" /> Información del Período
                </div>

                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <!-- Start Date -->
                    <div class="space-y-2">
                        <Label class="text-xs font-semibold text-zinc-400"
                            >Fecha Inicio</Label
                        >
                        <div class="relative">
                            <Calendar
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                            />
                            <Input
                                type="date"
                                v-model="form.pp_start_date"
                                class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50 pl-10 text-sm focus:ring-indigo-600"
                            />
                        </div>
                        <span
                            v-if="form.errors.pp_start_date"
                            class="mt-1 block text-xs font-bold text-red-500"
                            >{{ form.errors.pp_start_date }}</span
                        >
                    </div>

                    <!-- End Date -->
                    <div class="space-y-2">
                        <Label class="text-xs font-semibold text-zinc-400"
                            >Fecha Fin</Label
                        >
                        <div class="relative">
                            <Calendar
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                            />
                            <Input
                                type="date"
                                v-model="form.pp_end_date"
                                class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50 pl-10 text-sm focus:ring-indigo-600"
                            />
                        </div>
                        <span
                            v-if="form.errors.pp_end_date"
                            class="mt-1 block text-xs font-bold text-red-500"
                            >{{ form.errors.pp_end_date }}</span
                        >
                    </div>

                    <!-- Payment Date -->
                    <div class="space-y-2">
                        <Label class="text-xs font-semibold text-zinc-400"
                            >Fecha de Pago</Label
                        >
                        <div class="relative">
                            <Calendar
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                            />
                            <Input
                                type="date"
                                v-model="form.pp_payment_date"
                                class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50 pl-10 text-sm focus:ring-indigo-600"
                            />
                        </div>
                        <span
                            v-if="form.errors.pp_payment_date"
                            class="mt-1 block text-xs font-bold text-red-500"
                            >{{ form.errors.pp_payment_date }}</span
                        >
                    </div>

                    <!-- Status -->
                    <div class="space-y-2">
                        <Label class="text-xs font-semibold text-zinc-400"
                            >Estado</Label
                        >
                        <Select v-model="form.pp_status">
                            <SelectTrigger
                                class="h-11 rounded-xl border-zinc-800 bg-zinc-950/50"
                            >
                                <SelectValue placeholder="Seleccione estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in statusOptions"
                                    :key="status"
                                    :value="status"
                                >
                                    <span class="capitalize">{{ status }}</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span
                            v-if="form.errors.pp_status"
                            class="mt-1 block text-xs font-bold text-red-500"
                            >{{ form.errors.pp_status }}</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
