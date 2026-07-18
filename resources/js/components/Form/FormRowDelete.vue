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
        class="text-muted-foreground/40 hover:text-destructive rounded-md p-1.5 transition-colors disabled:opacity-20"
        title="Eliminar fila"
        @click="emit('remove')"
    >
        <Trash2 class="size-4" />
    </button>
</template>
