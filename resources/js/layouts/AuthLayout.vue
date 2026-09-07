<script setup lang="ts">
import Toaster from '@/components/ui/sonner.vue';
import { useFlashToast } from '@/composables/useFlashToast';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

useFlashToast();
</script>

<template>
    <Toaster />
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
