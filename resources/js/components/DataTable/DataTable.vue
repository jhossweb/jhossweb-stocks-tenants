<script setup lang="ts" generic="TRow extends object">
import type { HTMLAttributes } from 'vue';

export type DataTableAlign = 'left' | 'center' | 'right';

export interface DataTableColumn {
    key: string;
    label: string;
    align?: DataTableAlign;
    class?: HTMLAttributes['class'];
}

defineProps<{
    columns: DataTableColumn[];
    rows: TRow[];
    emptyText?: string;
    loading?: boolean;
}>();

function alignClass(align?: DataTableAlign): string {
    switch (align) {
        case 'center':
            return 'text-center';
        case 'right':
            return 'text-right';
        default:
            return 'text-left';
    }
}

function getValue(row: TRow, key: string): unknown {
    return (row as Record<string, unknown>)[key];
}

function cellText(value: unknown): string {
    if (value === undefined || value === null) {
        return '-';
    }

    return String(value);
}
</script>

<template>
    <div class="overflow-x-auto">
        <slot v-if="loading" name="fallback">
            <div class="flex items-center justify-center py-8">
                <div
                    class="size-6 animate-pulse rounded bg-muted-foreground/20"
                />
            </div>
        </slot>

        <template v-else>
            <div v-if="rows.length === 0">
                <slot name="empty">
                    <div class="py-8 text-center text-muted-foreground">
                        {{ emptyText ?? 'Sin datos para este período' }}
                    </div>
                </slot>
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/40">
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3 text-xs font-semibold text-zinc-400"
                            :class="[alignClass(column.align), column.class]"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <tr
                        v-for="(row, rowIndex) in rows"
                        :key="rowIndex"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3"
                            :class="[alignClass(column.align), column.class]"
                        >
                            <slot
                                :name="column.key"
                                :row="row"
                                :value="getValue(row, column.key)"
                            >
                                {{ cellText(getValue(row, column.key)) }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </template>
    </div>
</template>
