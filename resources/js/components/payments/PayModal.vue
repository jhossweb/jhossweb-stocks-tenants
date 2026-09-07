<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    open: boolean;
    title: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    close: [];
}>();

const close = () => {
    emit('update:open', false);
    emit('close');
};

const handleEsc = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.open) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', handleEsc));
onUnmounted(() => document.removeEventListener('keydown', handleEsc));
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/80 p-4 backdrop-blur-sm"
                @click.self="close"
            >
                <div
                    class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-zinc-800 bg-zinc-900 shadow-2xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-zinc-800 p-5"
                    >
                        <h2 class="text-lg font-bold text-zinc-100">
                            {{ title }}
                        </h2>
                        <button
                            type="button"
                            @click="close"
                            class="rounded-lg p-1.5 transition-colors hover:bg-zinc-800"
                        >
                            <X class="h-5 w-5 text-zinc-400" />
                        </button>
                    </div>

                    <div
                        v-if="$slots.summary"
                        class="border-b border-zinc-800/50 p-5"
                    >
                        <slot name="summary" />
                    </div>

                    <div class="p-5">
                        <slot />
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 border-t border-zinc-800 p-5"
                    >
                        <Button type="button" variant="outline" @click="close"
                            >Cancelar</Button
                        >
                        <slot name="actions" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-active > div,
.modal-leave-active > div {
    transition: transform 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.95);
}
</style>
