<script setup lang="ts">
import { Calendar, ChevronDown } from 'lucide-vue-next';
import type { MonthOption } from '@/composables/Dashboard/useDashboardMonth';

defineProps<{
    title: string;
    description?: string;
    month: string;
    monthOptions: MonthOption[];
}>();

const emit = defineEmits<{
    'change-month': [month: string];
}>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                {{ title }}
            </h1>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <label class="flex min-h-8 items-center gap-2">
            <span class="sr-only">Mes de análisis</span>
            <div class="relative">
                <Calendar
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <select
                    :value="month"
                    class="h-8 cursor-pointer appearance-none rounded-lg border border-input bg-background py-1.5 pr-9 pl-8 text-sm text-foreground shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    @change="
                        emit(
                            'change-month',
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="opt in monthOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
                <ChevronDown
                    class="pointer-events-none absolute top-1/2 right-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
            </div>
        </label>
    </div>
</template>
