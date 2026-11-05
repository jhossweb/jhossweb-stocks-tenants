<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermissions } from '@/composables/Auth/usePermissions';

type ExportItem = {
    key: string;
    label: string;
    url: string | null;
};

const props = defineProps<{
    exports: ExportItem[];
}>();

const { can } = usePermissions();

function url(item: ExportItem, format: 'csv' | 'pdf'): string {
    return `${item.url}?format=${format}`;
}
</script>

<template>
    <div>
        <Head title="Exportaciones" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Exportaciones
                </h1>
                <p class="text-sm text-muted-foreground">
                    Descarga los listados en CSV o PDF. Solo se muestran las
                    exportaciones permitidas para tu rol.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Listados disponibles</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="divide-y">
                        <div
                            v-for="item in props.exports"
                            v-show="item.url && can(`${item.key}.export`)"
                            :key="item.key"
                            class="flex items-center justify-between gap-4 py-3"
                        >
                            <span class="text-sm">{{ item.label }}</span>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <a :href="url(item, 'csv')">CSV</a>
                                </Button>
                                <Button variant="outline" size="sm" as-child>
                                    <a :href="url(item, 'pdf')">PDF</a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
