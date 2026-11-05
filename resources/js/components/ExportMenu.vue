<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/Auth/usePermissions';

const props = defineProps<{
    url: string;
    permission: string;
    filters?: Record<string, unknown>;
}>();

const { can } = usePermissions();

function href(format: 'csv' | 'pdf'): string {
    const params = new URLSearchParams();

    Object.entries(props.filters ?? {}).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            params.set(key, String(value));
        }
    });

    params.set('format', format);

    return `${props.url}?${params.toString()}`;
}
</script>

<template>
    <div v-if="can(permission)" class="flex gap-2">
        <Button variant="outline" size="sm" as-child>
            <a :href="href('csv')">CSV</a>
        </Button>
        <Button variant="outline" size="sm" as-child>
            <a :href="href('pdf')">PDF</a>
        </Button>
    </div>
</template>
