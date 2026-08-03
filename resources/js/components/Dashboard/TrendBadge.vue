<script setup lang="ts">
import { TrendingDown, TrendingUp, Minus } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { TrendData } from '@/types/Dashboard';

const props = defineProps<{
    trend?: TrendData;
}>();

const badgeVariant = computed(() => {
    if (props.trend?.direction === 'up') {
        return 'default';
    }

    if (props.trend?.direction === 'down') {
        return 'destructive';
    }

    return 'secondary';
});
</script>

<template>
    <Badge v-if="trend" :variant="badgeVariant" class="shrink-0 tabular-nums">
        <TrendingUp
            v-if="trend.direction === 'up'"
            class="size-3"
            aria-hidden="true"
        />
        <TrendingDown
            v-else-if="trend.direction === 'down'"
            class="size-3"
            aria-hidden="true"
        />
        <Minus v-else class="size-3" aria-hidden="true" />
        {{ trend.direction === 'up' ? '+' : '' }}{{ trend.percentage }}%
    </Badge>
</template>
