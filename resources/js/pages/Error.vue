<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ShieldX, SearchX, ServerCrash, ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{ status: number }>();

const config = computed(() => {
    switch (props.status) {
        case 403:
            return {
                icon: ShieldX,
                title: 'Acceso denegado',
                message: 'No tienes permisos para acceder a esta sección.',
                color: 'text-amber-500',
            };
        case 404:
            return {
                icon: SearchX,
                title: 'No encontrado',
                message: 'El registro que buscas no existe o fue eliminado.',
                color: 'text-blue-500',
            };
        case 500:
        default:
            return {
                icon: ServerCrash,
                title: 'Error del servidor',
                message:
                    'Ocurrió un problema inesperado. Si persiste, contacta al administrador.',
                color: 'text-red-500',
            };
    }
});

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/dashboard');
    }
}
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background px-4 text-center"
    >
        <component
            :is="config.icon"
            :class="['mb-6 size-16 opacity-80', config.color]"
        />

        <h1 class="mb-2 text-6xl font-bold text-zinc-100 tabular-nums">
            {{ status }}
        </h1>

        <h2 class="mb-3 text-xl font-semibold text-zinc-200">
            {{ config.title }}
        </h2>

        <p class="mb-8 max-w-md text-sm leading-relaxed text-zinc-400">
            {{ config.message }}
        </p>

        <Button
            variant="outline"
            class="border-zinc-700 bg-zinc-800/50 text-zinc-300 hover:bg-zinc-700/60 hover:text-zinc-100"
            @click="goBack"
        >
            <ArrowLeft class="mr-2 size-4" />
            Volver
        </Button>
    </div>
</template>
