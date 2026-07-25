<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CheckCircle, XCircle, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage();

const visible = ref(false);
const message = ref('');
const type = ref<'success' | 'error'>('success');

const flash = computed(() => (page.props.flash as any) ?? {});

watch(() => flash.value.success, (val) => {
    if (val) {
        message.value = val;
        type.value = 'success';
        visible.value = true;
        setTimeout(() => {
 visible.value = false; 
}, 4000);
    }
});

watch(() => flash.value.error, (val) => {
    if (val) {
        message.value = val;
        type.value = 'error';
        visible.value = true;
        setTimeout(() => {
 visible.value = false; 
}, 5000);
    }
});

const close = () => {
 visible.value = false; 
};
</script>

<template>
    <Transition name="flash">
        <div
            v-if="visible"
            class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border max-w-md"
            :class="type === 'success'
                ? 'bg-green-500/10 border-green-500/20 text-green-400'
                : 'bg-red-500/10 border-red-500/20 text-red-400'"
        >
            <CheckCircle v-if="type === 'success'" class="w-5 h-5 shrink-0" />
            <XCircle v-else class="w-5 h-5 shrink-0" />
            <span class="text-sm font-medium flex-1">{{ message }}</span>
            <button @click="close" class="p-0.5 hover:bg-white/10 rounded transition-colors">
                <X class="w-4 h-4" />
            </button>
        </div>
    </Transition>
</template>

<style scoped>
.flash-enter-active,
.flash-leave-active {
    transition: all 0.3s ease;
}
.flash-enter-from,
.flash-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
</style>
