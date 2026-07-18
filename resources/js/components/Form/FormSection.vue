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
            'bg-card text-card-foreground rounded-xl border shadow-sm',
            $props.class,
        ]"
    >
        <div
            v-if="title || $slots.header"
            class="flex items-center gap-2 border-b px-6 py-4"
        >
            <slot name="header">
                <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                    {{ title }}
                </span>
            </slot>
        </div>
        <div class="p-6">
            <slot />
        </div>
    </div>
</template>
