<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PermissionGroup } from '@/types/Settings';

defineProps<{
    permission_groups: PermissionGroup[];
}>();
</script>

<template>
    <div>
        <Head title="Permisos" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Permisos</h1>
                <p class="text-sm text-muted-foreground">
                    Catálogo fijo del sistema. Los roles asignan estos permisos;
                    no se crean ni editan desde aquí.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Catálogo</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div
                        v-for="group in permission_groups"
                        :key="group.key"
                        class="space-y-2"
                    >
                        <p class="text-sm font-medium">{{ group.label }}</p>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="permission in group.permissions"
                                :key="permission.key"
                                variant="outline"
                            >
                                {{ permission.key }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
