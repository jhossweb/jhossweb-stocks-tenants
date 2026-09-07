<script setup lang="ts">
import { PlusCircle } from 'lucide-vue-next';
import type { ColumnConfig } from '@/types/Transaction';

interface Props {
    columns: ColumnConfig[];
    loading?: boolean;
    loadingText?: string;
    empty?: boolean;
    emptyText?: string;
}

defineProps<Props>();

const emit = defineEmits<{
    add: [];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <span
                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
            >
                <slot name="title">Detalle de Productos</slot>
            </span>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary/10"
                @click="emit('add')"
            >
                <PlusCircle class="size-4" />
                Agregar Item
            </button>
        </div>

        <!-- Loading overlay -->
        <div
            v-if="loading"
            class="flex flex-col items-center justify-center gap-3 py-16"
        >
            <div
                class="size-8 animate-spin rounded-full border-2 border-border border-t-primary"
            />
            <span class="text-sm text-muted-foreground">{{
                loadingText || 'Cargando items...'
            }}</span>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="empty"
            class="flex flex-col items-center justify-center gap-2 py-16 text-muted-foreground"
        >
            <slot name="empty">
                <p class="text-sm">
                    {{ emptyText || 'No hay items agregados' }}
                </p>
            </slot>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead
                    class="border-b bg-muted/50 text-[10px] font-bold text-muted-foreground uppercase"
                >
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-4 py-4',
                                col.align === 'right'
                                    ? 'text-right'
                                    : col.align === 'center'
                                      ? 'text-center'
                                      : 'text-left',
                                col.width ? `w-[${col.width}]` : '',
                                col.class,
                            ]"
                        >
                            {{ col.label }}
                        </th>
                        <th class="w-12 px-4 py-4" />
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <slot />
                </tbody>
            </table>
        </div>
    </div>
</template>
