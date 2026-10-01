<script setup lang="ts">
import { Calendar, ChevronDown } from 'lucide-vue-next';
import type { CurrencyMode } from '@/composables/Dashboard/useCurrencyMode';
import type { MonthOption } from '@/composables/Dashboard/useDashboardMonth';

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        month: string;
        monthOptions: MonthOption[];
        /** Sólo se pinta el toggle si la página lo provee. */
        currencyMode?: CurrencyMode;
        rateDate?: string | null;
    }>(),
    {
        description: undefined,
        currencyMode: undefined,
        rateDate: null,
    },
);

const emit = defineEmits<{
    'change-month': [month: string];
    'change-currency-mode': [mode: CurrencyMode];
}>();

function selectMode(event: Event): void {
    emit(
        'change-currency-mode',
        (event.target as HTMLSelectElement).value as CurrencyMode,
    );
}
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
            <p
                v-if="props.currencyMode === 'usd' && rateDate"
                class="text-xs text-muted-foreground"
            >
                Tasas del {{ rateDate }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <label
                v-if="props.currencyMode"
                class="flex min-h-8 items-center gap-2"
            >
                <span class="sr-only">Moneda de visualización</span>
                <select
                    :value="props.currencyMode"
                    class="h-8 cursor-pointer appearance-none rounded-lg border border-input bg-background py-1.5 pr-9 pl-3 text-sm text-foreground shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    @change="selectMode"
                >
                    <option value="usd">Todo en USD</option>
                    <option value="multi">Por moneda</option>
                </select>
                <ChevronDown
                    class="pointer-events-none -ml-9 size-3.5 translate-y-px text-muted-foreground"
                    aria-hidden="true"
                />
            </label>

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
    </div>
</template>
