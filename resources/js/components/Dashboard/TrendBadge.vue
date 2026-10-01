<script setup lang="ts">
import {
    AlertTriangle,
    TrendingDown,
    TrendingUp,
    Minus,
} from 'lucide-vue-next';
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

/**
 * Sin tasa cargada el porcentaje sale de sumar monedas distintas, así que no
 * describe una variación real. Se marca como estimación en vez de publicarlo
 * como si lo fuera.
 */
const isEstimate = computed(() => props.trend?.reliable === false);
</script>

<template>
    <Badge v-if="trend" :variant="badgeVariant" class="shrink-0 tabular-nums">
        <TrendingUp
            v-if="trend.direction === 'up' && !isEstimate"
            class="size-3"
            aria-hidden="true"
        />
        <TrendingDown
            v-else-if="trend.direction === 'down' && !isEstimate"
            class="size-3"
            aria-hidden="true"
        />
        <AlertTriangle
            v-else-if="isEstimate"
            class="size-3"
            aria-hidden="true"
        />
        <Minus v-else class="size-3" aria-hidden="true" />
        <span :title="isEstimate ? 'Estimado: sin tasa cargada' : undefined">
            {{ trend.direction === 'up' ? '+' : '' }}{{ trend.percentage }}%
        </span>
        <span v-if="isEstimate" class="sr-only">
            (estimado, sin tasa de cambio cargada)
        </span>
    </Badge>
</template>
