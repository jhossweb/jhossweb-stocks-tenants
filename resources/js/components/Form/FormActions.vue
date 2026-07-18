<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, Loader2 } from 'lucide-vue-next';

interface Props {
    title: string;
    subtitle?: string;
    backUrl: string;
    processing?: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    save: [];
}>();
</script>

<template>
    <div class="flex items-center justify-between bg-card rounded-xl border px-6 py-4 shadow-sm">
        <div class="flex items-center gap-4">
            <Link
                :href="backUrl"
                class="text-muted-foreground hover:text-foreground rounded-full p-2 transition-colors hover:bg-accent"
            >
                <ArrowLeft class="size-5" />
            </Link>
            <div>
                <h1 class="text-xl font-bold">{{ title }}</h1>
                <p v-if="subtitle" class="text-xs text-muted-foreground">
                    {{ subtitle }}
                </p>
            </div>
        </div>
        <button
            type="button"
            :disabled="processing"
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 disabled:opacity-50"
            @click="emit('save')"
        >
            <Loader2 v-if="processing" class="size-4 animate-spin" />
            <Save v-else class="size-4" />
            {{ processing ? 'Guardando...' : 'Guardar' }}
        </button>
    </div>
</template>
