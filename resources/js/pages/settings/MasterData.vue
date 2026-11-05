<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type MasterItem = {
    id: number;
    name: string;
    is_active: boolean;
};

type MasterResource = {
    key: string;
    label: string;
    items: MasterItem[];
};

const props = defineProps<{
    resources: MasterResource[];
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(
        '/settings/master-data',
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function toggle(resource: string, item: MasterItem) {
    router.patch(
        `/settings/master-data/${resource}/${item.id}/toggle`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <div>
        <Head title="Datos maestros" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Estados de datos maestros
                </h1>
                <p class="text-sm text-muted-foreground">
                    Activa o inactiva registros. Los inactivos no aparecen en
                    los formularios de documentos nuevos.
                </p>
            </div>

            <form class="flex gap-2" @submit.prevent="applySearch">
                <Input
                    v-model="search"
                    placeholder="Buscar por nombre, código o email..."
                    class="max-w-md"
                />
                <Button type="submit" variant="outline">Buscar</Button>
            </form>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <Card v-for="resource in props.resources" :key="resource.key">
                    <CardHeader>
                        <CardTitle>{{ resource.label }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="resource.items.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Sin registros.
                        </div>
                        <ul v-else class="divide-y">
                            <li
                                v-for="item in resource.items"
                                :key="item.id"
                                class="flex items-center justify-between py-2 text-sm"
                            >
                                <span>{{ item.name }}</span>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :variant="
                                            item.is_active
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            item.is_active
                                                ? 'Activo'
                                                : 'Inactivo'
                                        }}
                                    </Badge>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="toggle(resource.key, item)"
                                    >
                                        {{
                                            item.is_active
                                                ? 'Inactivar'
                                                : 'Activar'
                                        }}
                                    </Button>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
