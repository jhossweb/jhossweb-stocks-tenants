<script setup lang="ts">
import { CheckCircle2, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { CashClosingStatus } from '@/types/Finance/CashClosing';

const props = defineProps<{
    status: CashClosingStatus;
}>();

const label = computed(() => {
    return props.status === 'balanced' ? 'Cuadrado' : 'Con diferencia';
});
</script>

<template>
    <Badge
        :variant="status === 'balanced' ? 'default' : 'destructive'"
        class="gap-1.5 px-3 py-1 text-xs font-semibold tracking-wide uppercase"
    >
        <CheckCircle2
            v-if="status === 'balanced'"
            class="size-4"
            aria-hidden="true"
        />
        <TriangleAlert v-else class="size-4" aria-hidden="true" />
        {{ label }}
    </Badge>
</template>
