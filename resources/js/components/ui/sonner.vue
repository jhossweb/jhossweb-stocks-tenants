<script setup lang="ts">
import 'vue-sonner/style.css';
import { Toaster, type ToasterProps } from 'vue-sonner';

const props = withDefaults(defineProps<ToasterProps>(), {
    theme: 'dark',
    position: 'bottom-right',
    richColors: true,
    closeButton: true,
    duration: 4000,
    gap: 8,
    visibleToasts: 3,
});
</script>

<template>
    <Toaster
        v-bind="props"
        class="toaster group"
        :toast-options="{
            style: {
                '--normal-bg': 'hsl(240 3.7% 15.9%)',
                '--normal-text': 'hsl(240 5% 96.1%)',
                '--normal-border': 'hsl(240 3.7% 15.9%)',
                '--border-radius': 'var(--radius)',
            },
        }"
    />
</template>
