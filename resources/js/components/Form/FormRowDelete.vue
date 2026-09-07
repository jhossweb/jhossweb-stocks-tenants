<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';

defineProps<{
    disabled?: boolean;
}>();

const emit = defineEmits<{
    remove: [];
}>();
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        class="rounded-md p-1.5 text-muted-foreground/40 transition-colors hover:text-destructive disabled:opacity-20"
        title="Eliminar fila"
        @click="emit('remove')"
    >
        <Trash2 class="size-4" />
    </button>
</template>
