<script setup lang="ts">
interface Props {
    title?: string;
    icon?: string;
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div
        :class="[
            'rounded-xl border bg-card text-card-foreground shadow-sm',
            $props.class,
        ]"
    >
        <div
            v-if="title || $slots.header"
            class="flex items-center gap-2 border-b px-6 py-4"
        >
            <slot name="header">
                <span
                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                >
                    {{ title }}
                </span>
            </slot>
        </div>
        <div class="p-6">
            <slot />
        </div>
        <div
            v-if="$slots.actions"
            class="flex items-center justify-end gap-3 border-t px-6 py-4"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
