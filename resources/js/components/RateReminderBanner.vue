<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { AlertTriangle, TrendingDown, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { create } from '@/routes/rates';
import type { RateNotice } from '@/types';

const page = usePage();

const notice = computed<RateNotice | null>(
    () => (page.props.rateNotice as RateNotice | null) ?? null,
);

function storageKey(): string {
    return `rate-reminder-dismissed-${new Date().toISOString().slice(0, 10)}`;
}

function alreadyDismissed(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.localStorage.getItem(storageKey()) === '1';
}

const dismissed = ref(alreadyDismissed());

function dismiss(): void {
    dismissed.value = true;

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(storageKey(), '1');
    }
}

const isCritical = computed(() => notice.value?.severity === 'critical');
</script>

<template>
    <Alert
        v-if="notice && !dismissed"
        class="mx-4 mt-4 md:mx-6"
        :class="
            isCritical
                ? 'border-red-500/40 bg-red-500/10 text-red-600 dark:text-red-400'
                : 'border-amber-500/40 bg-amber-500/10 text-amber-600 dark:text-amber-400'
        "
    >
        <AlertTriangle
            :class="isCritical ? 'text-red-500' : 'text-amber-500'"
        />

        <AlertTitle class="flex items-center gap-2">
            <span>
                {{
                    isCritical
                        ? 'Tasa de cambio desactualizada'
                        : 'Actualizá la tasa de cambio'
                }}
            </span>

            <Link
                :href="create.url()"
                class="inline-flex items-center gap-1 rounded-md border border-current/30 px-2 py-0.5 text-xs font-medium hover:bg-current/10"
            >
                <TrendingDown class="size-3" />
                Registrar tasa
            </Link>

            <button
                type="button"
                aria-label="Cerrar aviso de tasa"
                class="ml-auto rounded p-0.5 transition-colors hover:bg-current/10"
                @click="dismiss"
            >
                <X class="size-4" />
            </button>
        </AlertTitle>

        <AlertDescription
            :class="
                isCritical
                    ? 'text-red-600/80 dark:text-red-400/80'
                    : 'text-amber-600/80 dark:text-amber-400/80'
            "
        >
            {{ notice.message }}
            <span v-if="notice.last_rate_date" class="block opacity-80">
                Última tasa registrada: {{ notice.last_rate_date }}
            </span>
        </AlertDescription>
    </Alert>
</template>
